<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CajaTurno extends Model
{
    protected $table = 'caja_turnos';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['abierto_at' => 'datetime', 'cerrado_at' => 'datetime'];
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function esperado(): int
    {
        return $this->inicial_centavos + (int) $this->pagos()->whereHas('metodoPago', fn ($q) => $q->where('codigo', 'efectivo'))->sum('monto_centavos');
    }
}
