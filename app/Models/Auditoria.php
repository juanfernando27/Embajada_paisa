<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected $table = 'auditorias';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['antes' => 'array', 'despues' => 'array', 'created_at' => 'datetime'];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
