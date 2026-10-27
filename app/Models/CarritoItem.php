<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarritoItem extends Model
{
    protected $table = 'carrito_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['cantidad' => 'integer'];
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
