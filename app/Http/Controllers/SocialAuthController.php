<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\CustomerAccounts;
use App\Services\PosTransaction;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Throwable;

class SocialAuthController extends Controller
{
    public function __construct(private CustomerAccounts $accounts) {}

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        return $this->start($request, $provider);
    }

    public function link(Request $request, string $provider): RedirectResponse
    {
        return $this->start($request, $provider, $request->user()->id);
    }

    private function start(Request $request, string $provider, ?int $userId = null): RedirectResponse
    {
        abort_unless(config("services.$provider.enabled"), 404);
        $response = Socialite::driver($provider)->redirect();
        parse_str((string) parse_url($response->getTargetUrl(), PHP_URL_QUERY), $parameters);
        $request->session()->put('oauth_flow', [
            'provider' => $provider, 'user_id' => $userId, 'state' => $parameters['state'],
            'nonce' => $parameters['nonce'] ?? null, 'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        return $response;
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(config("services.$provider.enabled"), 404);
        $destination = $request->user() ? 'cuenta.conexiones' : 'login';
        $state = $request->input('state');
        $flow = $request->session()->get('oauth_flow');
        if (! $flow || $flow['provider'] !== $provider || $flow['user_id'] !== $request->user()?->id
            || $flow['expires_at'] < now()->timestamp || ! is_string($state) || ! hash_equals($flow['state'], $state)) {
            return redirect()->route($destination)->withErrors(['oauth' => 'La solicitud de acceso expiró o es inválida. Inicia de nuevo.']);
        }
        $request->session()->forget('oauth_flow');
        if (! $request->filled('code') || ! is_string($request->input('code')) || $request->filled('error')) {
            return redirect()->route($destination)->withErrors(['oauth' => 'Se canceló el acceso con el proveedor.']);
        }

        try {
            $identity = Socialite::driver($provider)->setHttpClient(new Client(['connect_timeout' => 4, 'timeout' => 12]))->user();
            // Verifica el nonce completo que emitió este navegador antes de aceptar la identidad.
            if ($provider === 'apple' && (! is_string($flow['nonce']) || ! is_string($identity->user['nonce'] ?? null)
                || ! hash_equals($flow['nonce'], $identity->user['nonce']))) {
                throw ValidationException::withMessages(['oauth' => 'La respuesta de Apple no corresponde al acceso iniciado.']);
            }
            $user = $this->resolve($provider, $identity, $flow['user_id']);
        } catch (ValidationException $error) {
            return redirect()->route($destination)->withErrors($error->errors());
        } catch (Throwable $error) {
            Log::warning('Error de autenticación externa.', ['provider' => $provider, 'type' => $error::class]);

            return redirect()->route($destination)->withErrors(['oauth' => 'No fue posible completar el acceso. Intenta de nuevo.']);
        }

        if ($flow['user_id'] !== null) {
            return redirect()->route('cuenta.conexiones')->with('success', 'Proveedor vinculado correctamente.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function resolve(string $provider, SocialUser $identity, ?int $userId): User
    {
        $subject = (string) $identity->getId();
        if ($subject === '' || strlen($subject) > 255) {
            throw ValidationException::withMessages(['oauth' => 'El proveedor no devolvió una identidad válida.']);
        }

        return PosTransaction::run(function () use ($provider, $identity, $subject, $userId): User {
            $linked = SocialAccount::where('provider', $provider)->where('provider_user_id', $subject)->first();

            if ($userId !== null) {
                $user = User::findOrFail($userId);
                if (! $user->activo || ($linked && $linked->user_id !== $userId)
                    || (! $linked && $user->socialAccounts()->where('provider', $provider)->exists())) {
                    throw ValidationException::withMessages(['oauth' => 'Esta identidad ya está vinculada o la cuenta está desactivada.']);
                }
            } elseif ($linked) {
                $user = $linked->user;
                if (! $user->activo) {
                    throw ValidationException::withMessages(['oauth' => 'Tu cuenta está desactivada.']);
                }
            } else {
                $email = Str::lower(trim((string) $identity->getEmail()));
                $verified = $identity->user[$provider === 'google' ? 'verified_email' : 'email_verified'] ?? false;
                if (! filter_var($verified, FILTER_VALIDATE_BOOLEAN) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
                    throw ValidationException::withMessages(['oauth' => 'El proveedor debe entregar un correo verificado para crear la cuenta.']);
                }
                if (User::where('email', $email)->exists()) {
                    throw ValidationException::withMessages(['oauth' => 'Ya existe una cuenta con ese correo. Ingresa con tu método habitual y vincula el proveedor desde Conexiones.']);
                }
                $name = Str::limit(trim((string) $identity->getName()) ?: Str::before($email, '@'), 100, '');
                $user = $this->accounts->createVerifiedCustomer($name, $email);
            }

            if (! $linked) {
                $user->socialAccounts()->create(['provider' => $provider, 'provider_user_id' => $subject]);
            }

            return $user;
        });
    }
}
