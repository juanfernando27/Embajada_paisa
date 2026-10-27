<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';

    public const ESTADOS = ['pendiente' => 'Pendiente', 'preparando' => 'En preparación', 'listo' => 'Listo', 'entregado' => 'Entregado', 'anulado' => 'Anulado'];

    protected $guarded = ['id'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return ['subtotal_centavos' => 'integer', 'descuento_centavos' => 'integer', 'total_centavos' => 'integer'];
    }

    public function items()
    {
        return $this->hasMany(PedidoItem::class);
    }

    public function historial()
    {
        return $this->hasMany(PedidoEstado::class)->orderBy('id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function mesa()
    {
        return $this->belongsTo(Mesa::class);
    }

    public function pago()
    {
        return $this->hasOne(Pago::class);
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class);
    }

    public function getCodigoAttribute(): string
    {
        return 'EP-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
