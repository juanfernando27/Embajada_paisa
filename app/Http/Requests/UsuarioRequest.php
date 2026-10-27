<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\BcryptPasswordLength;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($this->route('usuario'))],
            'rol' => ['required', Rule::in(array_keys(User::ROLES))],
            'activo' => ['required', 'boolean'],
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'max:72', 'confirmed', Password::min(10)->mixedCase()->numbers(), new BcryptPasswordLength],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id', Rule::prohibitedIf(fn () => $this->input('rol') !== 'cliente')],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'email' => 'correo electrónico', 'password' => 'contraseña', 'cliente_id' => 'cliente asociado'];
    }
}
