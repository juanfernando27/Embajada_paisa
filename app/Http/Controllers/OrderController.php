<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Models\Pedido;
use App\Rules\MoneyValue;
use App\Services\OrderService;
use App\Support\Money;
use Illuminate\Http\Request;

class OrderController extends OperationController
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $r)
    {
        $filters = $r->validate(['estado' => 'nullable|in:pendiente,preparando,listo,entregado,anulado', 'pago' => 'nullable|in:pendiente,pagado']);
        $pedidos = Pedido::with('mesa', 'cliente')->when($r->user()->rol === 'cliente', fn ($q) => $q->where('user_id', $r->user()->id))->when($filters['estado'] ?? null, fn ($q, $v) => $q->where('estado', $v))->when($filters['pago'] ?? null, fn ($q, $v) => $q->where('estado_pago', $v))->latest('id')->paginate(15)->withQueryString();

        return $r->expectsJson() ? response()->json($pedidos) : view('pedidos.index', compact('pedidos', 'filters'));
    }

    public function store(Request $r)
    {
        $d = $r->validate(['token' => 'required|uuid', 'huella' => 'required|string|size:64', 'metodo_pago_id' => 'required|integer|exists:metodos_pago,id', 'mesa_id' => 'nullable|integer|exists:mesas,id', 'cliente_id' => 'nullable|integer|exists:clientes,id', 'notas' => 'nullable|string|max:500']);
        $p = $this->orders->checkout($r->user(), $d);

        return $this->done($r, 'Pedido recibido.', 'pedidos.show', [$p], $p->load('items'), 201);
    }

    public function show(Request $r, Pedido $pedido)
    {
        $this->own($r, $pedido);
        $pedido->load('items', 'historial.usuario', 'mesa', 'cliente', 'metodoPago', 'pago');
        $metodos = MetodoPago::where('activo', true)->get();

        return $r->expectsJson() ? response()->json($pedido) : view('pedidos.show', compact('pedido', 'metodos'));
    }

    public function state(Request $r, Pedido $pedido)
    {
        $this->own($r, $pedido);
        $d = $r->validate(['estado' => 'required|in:preparando,listo,entregado,anulado', 'estado_esperado' => 'required|in:pendiente,preparando,listo,entregado,anulado', 'motivo' => 'nullable|string|max:500']);
        $p = $this->orders->transition($r->user(), $pedido, $d['estado'], $d['estado_esperado'], $d['motivo'] ?? null);

        return $this->done($r, 'Estado actualizado.', 'pedidos.show', [$p], $p);
    }

    public function discount(Request $r, Pedido $pedido)
    {
        $d = $r->validate(['monto' => ['required', new MoneyValue], 'motivo' => 'required|string|min:5|max:500']);
        $p = $this->orders->discount($pedido, Money::parse($d['monto']), $d['motivo']);

        return $this->done($r, 'Descuento registrado.', 'pedidos.show', [$p], $p);
    }

    public function kitchen(Request $r)
    {
        $pedidos = Pedido::with('items', 'mesa')->whereIn('estado', ['pendiente', 'preparando', 'listo'])->orderBy('id')->get();

        return $r->expectsJson() ? response()->json($pedidos) : view('pedidos.kitchen', compact('pedidos'));
    }

    public function receipt(Request $r, Pedido $pedido)
    {
        $this->own($r, $pedido);
        abort_unless($pedido->estado_pago === 'pagado', 404);
        $pedido->load('items', 'pago.metodoPago');

        return view('pedidos.receipt', compact('pedido'));
    }
}
