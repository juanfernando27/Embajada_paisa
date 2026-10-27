<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->rol, ['administrador', 'trabajador'], true);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['numero_documento' => Str::upper(trim((string) $this->input('numero_documento'))), 'email' => $this->filled('email') ? Str::lower(trim((string) $this->input('email'))) : null]);
    }

    public function rules(): array
    {
        return [
            'tipo_documento' => ['required', Rule::in(['CC', 'CE', 'TI', 'PAS'])],
            'numero_documento' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9-]{4,30}$/', Rule::unique('clientes', 'numero_documento')->ignore($this->route('cliente'))],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('clientes', 'email')->ignore($this->route('cliente'))],
            'telefono' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
        ];
    }

    public function attributes(): array
    {
        return ['numero_documento' => 'número de documento', 'tipo_documento' => 'tipo de documento', 'email' => 'correo electrónico', 'telefono' => 'teléfono'];
    }
}
