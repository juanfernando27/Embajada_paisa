<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = ['administrador' => 'Administrador', 'trabajador' => 'Trabajador', 'cliente' => 'Cliente'];

    protected $fillable = ['name', 'email', 'password', 'rol', 'activo'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'activo' => 'boolean'];
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class);
    }

    public function esAdministrador(): bool
    {
        return $this->rol === 'administrador';
    }

    public function nombreRol(): string
    {
        return self::ROLES[$this->rol] ?? 'Sin rol';
    }
}
