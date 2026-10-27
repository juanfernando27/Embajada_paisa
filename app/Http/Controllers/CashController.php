<?php

namespace App\Http\Controllers;

use App\Models\CajaTurno;
use App\Models\Pedido;
use App\Rules\MoneyValue;
use App\Services\CashService;
use App\Support\Money;
use Illuminate\Http\Request;

class CashController extends OperationController
{
    public function __construct(private CashService $cash) {}

    public function index(Request $r)
    {
        $turno = CajaTurno::whereNull('cerrado_at')->first();
        $historial = CajaTurno::with('usuario')->whereNotNull('cerrado_at')->latest('id')->paginate(10);
        $pendientes = Pedido::with('mesa')->where('estado_pago', 'pendiente')->where('estado', '!=', 'anulado')->latest('id')->paginate(10, ['*'], 'pendientes_page');
        $totales = $turno ? $turno->pagos()->join('metodos_pago', 'metodos_pago.id', '=', 'pagos.metodo_pago_id')->selectRaw('metodos_pago.nombre, SUM(monto_centavos) as total')->groupBy('metodos_pago.nombre')->get() : collect();

        return $r->expectsJson() ? response()->json(compact('turno', 'totales')) : view('caja.index', compact('turno', 'historial', 'pendientes', 'totales'));
    }

    public function open(Request $r)
    {
        $d = $r->validate(['inicial' => ['required', new MoneyValue]]);
        $t = $this->cash->open($r->user(), Money::parse($d['inicial']));

        return $this->done($r, 'Caja abierta.', 'caja.index', [], $t, 201);
    }

    public function close(Request $r, CajaTurno $turno)
    {
        $d = $r->validate(['contado' => ['required', new MoneyValue], 'notas' => 'nullable|string|max:500']);
        $t = $this->cash->close($r->user(), $turno, Money::parse($d['contado']), $d['notas'] ?? null);

        return $this->done($r, 'Cierre registrado.', 'caja.index', [], $t);
    }

    public function pay(Request $r, Pedido $pedido)
    {
        $d = $r->validate(['metodo_pago_id' => 'required|integer|exists:metodos_pago,id', 'total_esperado' => 'required|integer|min:0', 'recibido' => ['required', new MoneyValue], 'referencia' => 'nullable|string|max:100']);
        $p = $this->cash->pay($r->user(), $pedido, $d);

        return $this->done($r, 'Cobro registrado.', 'pedidos.show', [$pedido], $p, 201);
    }
}
