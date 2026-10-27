<?php

namespace App\Services;

use App\Models\Mesa;
use App\Models\Reserva;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException as V;

class ReservationService
{
    public function interval(array $d): array
    {
        $s = CarbonImmutable::createFromFormat('Y-m-d H:i', $d['fecha'].' '.$d['hora'])->startOfMinute();
        if ($s->lte(now()) || $s->gt(now()->addYear())) {
            throw V::withMessages(['fecha' => 'Selecciona un horario futuro dentro de los próximos 12 meses.']);
        }

        return [$s, $s->addMinutes((int) $d['duracion'])];
    }

    public function available(array $d, ?int $except = null)
    {
        [$s,$e] = $this->interval($d);

        return Mesa::where('habilitada', true)->where('capacidad', '>=', $d['comensales'])->whereNotIn('id', Reserva::activas()->when($except, fn ($q) => $q->where('id', '!=', $except))->where('inicio', '<', $e)->where('fin', '>', $s)->select('mesa_id'))->orderBy('numero')->get();
    }

    public function save(User $u, array $d, ?Reserva $r = null): Reserva
    {
        // Intervalos [inicio, fin): dos reservas contiguas no se solapan.
        return PosTransaction::run(function () use ($u, $d, $r) {
            if ($r) {
                $r->refresh();
                if ($r->estado !== 'confirmada' || $r->inicio->lte(now())) {
                    throw V::withMessages(['reserva' => 'Solo puedes modificar reservas futuras confirmadas.']);
                }
            }[$s,$e] = $this->interval($d);
            $m = Mesa::findOrFail($d['mesa_id']);
            if (! $m->habilitada || $m->capacidad < $d['comensales']) {
                throw V::withMessages(['mesa_id' => 'Mesa fuera de servicio o sin capacidad suficiente.']);
            }
            if (Reserva::activas()->where('mesa_id', $m->id)->when($r, fn ($q) => $q->where('id', '!=', $r->id))->where('inicio', '<', $e)->where('fin', '>', $s)->exists()) {
                throw V::withMessages(['mesa_id' => 'Ese horario acaba de ocuparse. Selecciona otra mesa u hora.']);
            }
            $cid = $u->rol === 'cliente' ? $u->cliente()->value('id') : ($d['cliente_id'] ?? null);
            if (! $cid) {
                throw V::withMessages(['cliente_id' => 'Se necesita una ficha de cliente. Contacta al administrador.']);
            }
            $data = ['cliente_id' => $cid, 'mesa_id' => $m->id, 'inicio' => $s, 'fin' => $e, 'comensales' => $d['comensales'], 'notas' => $d['notas'] ?? null];
            $before = $r?->toArray();
            if ($r) {
                $r->update($data);
            } else {
                $r = Reserva::create([...$data, 'user_id' => $u->id, 'estado' => 'confirmada']);
            }
            Audit::record($before ? 'reserva_modificada' : 'reserva_creada', $r, $before, $r->toArray());

            return $r;
        });
    }

    public function state(User $u, Reserva $r, string $next, string $reason): Reserva
    {
        return PosTransaction::run(function () use ($u, $r, $next, $reason) {
            $r->refresh();
            $old = $r->estado;
            if ($u->rol === 'cliente') {
                abort_unless($r->cliente()->where('user_id', $u->id)->exists(), 403);
                if ($next !== 'cancelada' || $old !== 'confirmada' || $r->inicio->lte(now())) {
                    throw V::withMessages(['estado' => 'Solo puedes cancelar reservas futuras confirmadas.']);
                }
            } else {
                if (! in_array($next, ['confirmada' => ['en_curso', 'cancelada'], 'en_curso' => ['finalizada', 'cancelada']][$old] ?? [], true)) {
                    throw V::withMessages(['estado' => 'Cambio de estado no permitido.']);
                }
                if ($next === 'en_curso' && ($r->inicio->gt(now()) || $r->fin->lte(now()))) {
                    throw V::withMessages(['estado' => 'Registra el ingreso dentro del horario reservado.']);
                }
            }
            $r->update(['estado' => $next]);
            Audit::record('reserva_estado', $r, ['estado' => $old], ['estado' => $next], $reason);

            return $r;
        });
    }
}
