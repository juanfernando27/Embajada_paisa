<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoEstado extends Model
{
    protected $table = 'pedido_estados';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
