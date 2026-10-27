<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    protected $fillable = ['numero', 'capacidad', 'ubicacion', 'descripcion', 'habilitada'];

    protected function casts(): array
    {
        return ['numero' => 'integer', 'capacidad' => 'integer', 'habilitada' => 'boolean'];
    }

    public function getEstadoOperacionAttribute(): string
    {
        if (! $this->habilitada) {
            return 'fuera_servicio';
        }
        if (Pedido::where('mesa_id', $this->id)->whereIn('estado', ['pendiente', 'preparando', 'listo'])->exists() || Reserva::where('mesa_id', $this->id)->where('estado', 'en_curso')->exists()) {
            return 'ocupada';
        }
        if (Reserva::activas()->where('mesa_id', $this->id)->where('inicio', '<=', now())->where('fin', '>', now())->exists()) {
            return 'reservada';
        }

        return 'disponible';
    }
}
