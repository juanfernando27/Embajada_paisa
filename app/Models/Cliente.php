<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    protected $fillable = ['tipo_documento', 'numero_documento', 'nombre', 'apellido', 'email', 'telefono', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCorreoContactoAttribute(): ?string
    {
        return $this->email ?? $this->user?->email;
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->nombre.' '.$this->apellido;
    }
}
