<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = ['categoria_id', 'nombre', 'descripcion', 'alergenos', 'precio_centavos', 'stock', 'activo', 'imagen'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'precio_centavos' => 'integer', 'stock' => 'integer'];
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function scopeOfertados($q)
    {
        return $q->where('activo', true)->whereHas('categoria', fn ($c) => $c->where('activa', true));
    }
}
