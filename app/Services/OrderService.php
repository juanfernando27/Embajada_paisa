<?php

namespace App\Services;

use App\Models\CarritoItem;
use App\Models\Mesa;
use App\Models\MetodoPago;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Validation\ValidationException as V;

class OrderService
{
    public function __construct(private CartService $cart) {}

    public function checkout(User $u, array $d): Pedido
    {
        return PosTransaction::run(function () use ($u, $d) {
            // Reintentar el mismo formulario devuelve el pedido, sin descontar stock otra vez.
            if ($old = Pedido::where('user_id', $u->id)->where('token', $d['token'])->first()) {
                return $old;
            }
            $s = $this->cart->summary($u);
            if ($s['items']->isEmpty()) {
                throw V::withMessages(['carrito' => 'Tu carrito está vacío.']);
            }
            if (! hash_equals($s['huella'], $d['huella'])) {
                throw V::withMessages(['carrito' => 'El carrito o los precios cambiaron. Revisa el resumen y confirma otra vez.']);
            }
            if (! MetodoPago::whereKey($d['metodo_pago_id'])->where('activo', true)->exists()) {
                throw V::withMessages(['metodo_pago_id' => 'Método de pago no disponible.']);
            }
            if (! empty($d['mesa_id'])) {
                $m = Mesa::findOrFail($d['mesa_id']);
                if (! $m->habilitada || Pedido::where('mesa_id', $m->id)->whereIn('estado', ['pendiente', 'preparando', 'listo'])->exists()) {
                    throw V::withMessages(['mesa_id' => 'La mesa no está disponible para un nuevo pedido.']);
                }
                if ($u->rol === 'cliente' && Reserva::activas()->where('mesa_id', $m->id)->where('inicio', '<=', now())->where('fin', '>', now())->whereHas('cliente', fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $u->id))->exists()) {
                    throw V::withMessages(['mesa_id' => 'La mesa tiene una reserva de otra persona.']);
                }
            }
            $p = Pedido::create(['user_id' => $u->id, 'cliente_id' => $u->rol === 'cliente' ? $u->cliente()->value('id') : ($d['cliente_id'] ?? null), 'mesa_id' => $d['mesa_id'] ?? null, 'metodo_pago_id' => $d['metodo_pago_id'], 'token' => $d['token'], 'estado' => 'pendiente', 'estado_pago' => 'pendiente', 'subtotal_centavos' => $s['total'], 'descuento_centavos' => 0, 'total_centavos' => $s['total'], 'notas' => $d['notas'] ?? null]);
            foreach ($s['items'] as $i) {
                $product = Producto::with('categoria')->findOrFail($i->producto_id);
                $this->cart->quantity($product, $i->cantidad);
                // El comprobante conserva nombre y precio aunque cambie el catálogo.
                PedidoItem::create(['pedido_id' => $p->id, 'producto_id' => $product->id, 'nombre' => $product->nombre, 'cantidad' => $i->cantidad, 'precio_centavos' => $product->precio_centavos, 'total_centavos' => $i->cantidad * $product->precio_centavos]);
                if ($product->stock !== null) {
                    $product->decrement('stock', $i->cantidad);
                }
            }
            PedidoEstado::create(['pedido_id' => $p->id, 'user_id' => $u->id, 'nuevo' => 'pendiente', 'created_at' => now()]);
            CarritoItem::where('user_id', $u->id)->delete();
            Audit::record('pedido_creado', $p, null, ['estado' => 'pendiente', 'total_centavos' => $p->total_centavos]);

            return $p;
        });
    }

    public function transition(User $u, Pedido $p, string $next, string $expected, ?string $reason): Pedido
    {
        return PosTransaction::run(function () use ($u, $p, $next, $expected, $reason) {
            $p->refresh();
            if ($p->estado !== $expected) {
                throw V::withMessages(['estado' => 'El pedido cambió en otra sesión. Actualiza la pantalla.']);
            }
            if ($next === 'anulado') {
                abort_unless($u->esAdministrador() || ($u->rol === 'cliente' && $p->user_id === $u->id), 403);
                if (($u->rol === 'cliente' && $p->estado !== 'pendiente') || $p->estado === 'anulado' || $p->estado_pago === 'pagado') {
                    throw V::withMessages(['estado' => 'No se puede anular un pedido cobrado o ya anulado.']);
                }
                if (! $reason || mb_strlen(trim($reason)) < 5) {
                    throw V::withMessages(['motivo' => 'Explica el motivo de la anulación (mínimo 5 caracteres).']);
                }
                foreach ($p->items as $i) {
                    $product = Producto::findOrFail($i->producto_id);
                    if ($product->stock !== null) {
                        $product->increment('stock', $i->cantidad);
                    }
                }
            } else {
                abort_unless($u->rol !== 'cliente', 403);
                if ((['pendiente' => 'preparando', 'preparando' => 'listo', 'listo' => 'entregado'][$p->estado] ?? null) !== $next) {
                    throw V::withMessages(['estado' => 'El cambio no respeta el flujo de preparación.']);
                }
            }
            $old = $p->estado;
            $p->update(['estado' => $next]);
            PedidoEstado::create(['pedido_id' => $p->id, 'user_id' => $u->id, 'anterior' => $old, 'nuevo' => $next, 'motivo' => $reason, 'created_at' => now()]);
            Audit::record($next === 'anulado' ? 'pedido_anulado' : 'pedido_estado', $p, ['estado' => $old], ['estado' => $next], $reason);

            return $p;
        });
    }

    public function discount(Pedido $p, int $amount, string $reason): Pedido
    {
        return PosTransaction::run(function () use ($p, $amount, $reason) {
            $p->refresh();
            if ($p->estado === 'anulado' || $p->estado_pago === 'pagado' || $amount > $p->subtotal_centavos) {
                throw V::withMessages(['monto' => 'El descuento no es válido para este pedido.']);
            }
            $old = ['descuento_centavos' => $p->descuento_centavos, 'total_centavos' => $p->total_centavos];
            $p->update(['descuento_centavos' => $amount, 'total_centavos' => $p->subtotal_centavos - $amount]);
            Audit::record('descuento', $p, $old, ['descuento_centavos' => $amount, 'total_centavos' => $p->total_centavos], $reason);

            return $p;
        });
    }
}
