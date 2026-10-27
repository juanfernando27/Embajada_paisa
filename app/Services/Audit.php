<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;

final class Audit
{
    public static function record(string $accion, Model $m, ?array $antes = null, ?array $despues = null, ?string $motivo = null): void
    {
        Auditoria::create(['user_id' => auth()->id(), 'accion' => $accion, 'entidad' => class_basename($m), 'entidad_id' => $m->id, 'antes' => $antes, 'despues' => $despues, 'motivo' => $motivo, 'created_at' => now()]);
    }
}
