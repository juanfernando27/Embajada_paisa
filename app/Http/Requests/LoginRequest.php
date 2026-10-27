<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email', 'max:150'], 'password' => ['required', 'string', 'max:255']];
    }

    public function authenticate(): void
    {
        $key = Str::transliterate($this->string('email')->toString().'|'.$this->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Demasiados intentos. Intenta de nuevo en '.RateLimiter::availableIn($key).' segundos.']);
        }
        if (! Auth::attempt([...$this->only('email', 'password'), 'activo' => true])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'No fue posible ingresar. Revisa tus credenciales o consulta al administrador.']);
        }
        RateLimiter::clear($key);
    }
}
