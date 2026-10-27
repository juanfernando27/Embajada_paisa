<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class OperationController extends Controller
{
    protected function done(Request $r, string $message, string $route, array $params = [], mixed $data = null, int $status = 200): mixed
    {
        return $r->expectsJson() ? response()->json(['message' => $message, 'data' => $data], $status) : redirect()->route($route, $params)->with('success', $message);
    }

    protected function own(Request $r, Model $m): void
    {
        if ($r->user()->rol === 'cliente') {
            $own = $m instanceof Reserva ? $m->cliente()->where('user_id', $r->user()->id)->exists() : $m->user_id === $r->user()->id;
            abort_unless($own, 403);
        }
    }
}
