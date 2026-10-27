<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    public function rules(): array
    {
        return [
            'numero' => ['required', 'integer', 'min:1', 'max:999', Rule::unique('mesas', 'numero')->ignore($this->route('mesa'))],
            'capacidad' => ['required', 'integer', 'min:1', 'max:30'],
            'ubicacion' => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'habilitada' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['numero' => 'número de mesa', 'ubicacion' => 'ubicación', 'descripcion' => 'descripción'];
    }
}
