<?php

namespace App\Services;

use App\Models\CajaTurno;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\User;
use App\Support\Money;
use Illuminate\Validation\ValidationException as V;

class CashService
{
    public function open(User $u, int $initial): CajaTurno
    {
        return PosTransaction::run(function () use ($u, $initial) {
            if (CajaTurno::whereNull('cerrado_at')->exists()) {
                throw V::withMessages(['caja' => 'Ya hay una caja abierta.']);
            }
            $t = CajaTurno::create(['user_id' => $u->id, 'inicial_centavos' => $initial, 'abierto_at' => now()]);
            Audit::record('caja_abierta', $t, null, ['inicial_centavos' => $initial]);

            return $t;
        });
    }

    public function pay(User $u, Pedido $p, array $d): Pago
    {
        return PosTransaction::run(function () use ($u, $p, $d) {
            $p->refresh();
            if ($p->estado === 'anulado' || $p->estado_pago === 'pagado') {
                throw V::withMessages(['pago' => 'El pedido ya fue cobrado o está anulado.']);
            }
            $t = CajaTurno::whereNull('cerrado_at')->first();
            if (! $t) {
                throw V::withMessages(['caja' => 'El administrador debe abrir caja antes de cobrar.']);
            }
            if ($p->total_centavos !== (int) $d['total_esperado']) {
                throw V::withMessages(['pago' => 'El total cambió. Actualiza la pantalla.']);
            }
            $m = MetodoPago::where('activo', true)->findOrFail($d['metodo_pago_id']);
            $received = Money::parse($d['recibido']);
            if ($m->codigo !== 'efectivo') {
                if (empty($d['referencia']) || mb_strlen(trim($d['referencia'])) < 4) {
                    throw V::withMessages(['referencia' => 'Referencia obligatoria (mínimo 4 caracteres).']);
                }
                if ($received !== $p->total_centavos) {
                    throw V::withMessages(['recibido' => 'El comprobante debe coincidir con el total.']);
                }
            }
            if ($received < $p->total_centavos) {
                throw V::withMessages(['recibido' => 'El monto recibido es insuficiente.']);
            }
            $pay = Pago::create(['pedido_id' => $p->id, 'caja_turno_id' => $t->id, 'user_id' => $u->id, 'metodo_pago_id' => $m->id, 'monto_centavos' => $p->total_centavos, 'recibido_centavos' => $received, 'cambio_centavos' => $received - $p->total_centavos, 'referencia' => $d['referencia'] ?? null]);
            $p->update(['estado_pago' => 'pagado', 'metodo_pago_id' => $m->id]);
            Audit::record('pago_registrado', $p, ['estado_pago' => 'pendiente'], ['estado_pago' => 'pagado', 'monto_centavos' => $pay->monto_centavos, 'metodo' => $m->codigo]);

            return $pay;
        });
    }

    public function close(User $u, CajaTurno $t, int $counted, ?string $reason): CajaTurno
    {
        return PosTransaction::run(function () use ($u, $t, $counted, $reason) {
            $t->refresh();
            if ($t->cerrado_at) {
                throw V::withMessages(['caja' => 'La caja ya está cerrada.']);
            }
            $expected = $t->esperado();
            if ($expected !== $counted && (! $reason || mb_strlen(trim($reason)) < 5)) {
                throw V::withMessages(['notas' => 'Explica la diferencia de efectivo.']);
            }
            $t->update(['cerrado_por' => $u->id, 'esperado_centavos' => $expected, 'contado_centavos' => $counted, 'diferencia_centavos' => $counted - $expected, 'cerrado_at' => now(), 'notas' => $reason]);
            Audit::record('caja_cerrada', $t, null, ['esperado_centavos' => $expected, 'contado_centavos' => $counted, 'diferencia_centavos' => $counted - $expected], $reason);

            return $t;
        });
    }
}
