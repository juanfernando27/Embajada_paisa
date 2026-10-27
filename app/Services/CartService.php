<?php

namespace App\Services;

use App\Models\CarritoItem;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function summary(User $u): array
    {
        $items = CarritoItem::with('producto.categoria')->where('user_id', $u->id)->orderBy('id')->get();
        $total = 0;
        $sig = [];
        foreach ($items as $i) {
            $p = $i->producto;
            $total += $i->cantidad * $p->precio_centavos;
            $sig[] = [$i->id, $i->cantidad, $p->id, $p->precio_centavos, $p->stock, $p->activo, $p->categoria->activa];
        }

        return ['items' => $items, 'total' => $total, 'huella' => hash('sha256', json_encode($sig))];
    }

    public function quantity(Producto $p, int $n): void
    {
        if ($n < 1 || $n > 99 || ! $p->activo || ! $p->categoria->activa || ($p->stock !== null && $n > $p->stock)) {
            throw ValidationException::withMessages(['cantidad' => 'Cantidad no disponible. Usa entre 1 y 99 unidades y respeta las existencias.']);
        }
    }

    public function add(User $u, int $id, int $n): CarritoItem
    {
        return PosTransaction::run(function () use ($u, $id, $n) {
            $p = Producto::with('categoria')->findOrFail($id);
            $i = CarritoItem::firstOrNew(['user_id' => $u->id, 'producto_id' => $id]);
            $this->quantity($p, ($i->cantidad ?? 0) + $n);
            $i->cantidad = ($i->cantidad ?? 0) + $n;
            $i->save();

            return $i;
        });
    }

    public function update(User $u, CarritoItem $i, int $n): CarritoItem
    {
        abort_unless($i->user_id === $u->id, 403);

        return PosTransaction::run(function () use ($i, $n) {
            $i->refresh();
            $this->quantity($i->producto()->firstOrFail(), $n);
            $i->update(['cantidad' => $n]);

            return $i;
        });
    }
}
