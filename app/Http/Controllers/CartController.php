<?php

namespace App\Http\Controllers;

use App\Models\CarritoItem;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\MetodoPago;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends OperationController
{
    public function __construct(private CartService $cart) {}

    public function index(Request $r)
    {
        $summary = $this->cart->summary($r->user());
        $token = (string) Str::uuid();
        $metodos = MetodoPago::where('activo', true)->get();
        $mesas = Mesa::where('habilitada', true)->orderBy('numero')->get();
        $clientes = $r->user()->rol !== 'cliente' ? Cliente::orderBy('nombre')->get() : collect();

        return $r->expectsJson() ? response()->json([...$summary, 'token' => $token, 'metodos' => $metodos]) : view('carrito.index', compact('summary', 'token', 'metodos', 'mesas', 'clientes'));
    }

    public function store(Request $r)
    {
        $d = $r->validate(['producto_id' => 'required|integer|exists:productos,id', 'cantidad' => 'required|integer|min:1|max:99']);
        $i = $this->cart->add($r->user(), $d['producto_id'], $d['cantidad']);

        return $this->done($r, 'Producto agregado.', 'carrito.index', [], $i, 201);
    }

    public function update(Request $r, CarritoItem $item)
    {
        $d = $r->validate(['cantidad' => 'required|integer|min:1|max:99']);
        $i = $this->cart->update($r->user(), $item, $d['cantidad']);

        return $this->done($r, 'Cantidad actualizada.', 'carrito.index', [], $i);
    }

    public function destroy(Request $r, CarritoItem $item)
    {
        abort_unless($item->user_id === $r->user()->id, 403);
        $item->delete();

        return $this->done($r, 'Producto retirado.', 'carrito.index');
    }
}
