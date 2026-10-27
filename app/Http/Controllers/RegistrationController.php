<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use App\Rules\BcryptPasswordLength;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegistrationController extends OperationController
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $r)
    {
        $key = 'registro:'.$r->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages(['email' => 'Demasiados intentos. Espera unos minutos.']);
        }
        RateLimiter::hit($key, 300);
        $r->merge(['email' => mb_strtolower(trim((string) $r->input('email'))), 'numero_documento' => mb_strtoupper(trim((string) $r->input('numero_documento')))]);
        $d = $r->validate(['nombre' => 'required|string|max:50', 'apellido' => 'required|string|max:50', 'email' => 'required|email|max:150|unique:users,email|unique:clientes,email', 'tipo_documento' => 'required|in:CC,CE,TI,PAS', 'numero_documento' => ['required', 'string', 'regex:/^[A-Z0-9-]{4,30}$/', 'unique:clientes,numero_documento'], 'telefono' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{7,20}$/'], 'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(10)->mixedCase()->numbers(), new BcryptPasswordLength]]);
        $u = DB::transaction(function () use ($d) {
            $u = User::create(['name' => $d['nombre'].' '.$d['apellido'], 'email' => $d['email'], 'password' => $d['password'], 'rol' => 'cliente', 'activo' => true]);
            Cliente::create([...collect($d)->only(['nombre', 'apellido', 'email', 'tipo_documento', 'numero_documento', 'telefono'])->all(), 'user_id' => $u->id]);

            return $u;
        });
        Auth::login($u);
        $r->session()->regenerate();
        RateLimiter::clear($key);

        return $this->done($r, 'Tu cuenta está lista.', 'menu.index', [], $u, 201);
    }
}
