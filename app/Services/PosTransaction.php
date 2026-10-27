<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;

final class PosTransaction
{
    // Tomar el bloqueo de escritura antes de leer evita carreras de stock,
    // reservas y cobros en SQLite. Todas esas operaciones usan esta transacción.
    public static function run(Closure $f): mixed
    {
        return DB::transaction(function () use ($f) {
            DB::table('pos_locks')->where('id', 1)->increment('version');

            return $f();
        }, 5);
    }
}
