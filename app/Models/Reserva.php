<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';

    public const ESTADOS = ['confirmada' => 'Confirmada', 'en_curso' => 'En curso', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['inicio' => 'immutable_datetime', 'fin' => 'immutable_datetime', 'comensales' => 'integer'];
    }

    public function mesa()
    {
        return $this->belongsTo(Mesa::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function scopeActivas($q)
    {
        return $q->whereIn('estado', ['confirmada', 'en_curso']);
    }
}
