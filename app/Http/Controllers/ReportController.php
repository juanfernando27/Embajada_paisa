<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Pago;
use App\Models\Pedido;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    private function range(Request $r): array
    {
        $d = $r->validate(['desde' => 'nullable|date_format:Y-m-d', 'hasta' => 'nullable|date_format:Y-m-d']);
        $a = CarbonImmutable::parse($d['desde'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $b = CarbonImmutable::parse($d['hasta'] ?? now()->toDateString())->endOfDay();
        if ($b->lt($a) || $a->diffInDays($b) > 366) {
            throw ValidationException::withMessages(['hasta' => 'El rango debe ser ordenado y no superar 366 días.']);
        }

        return [$a, $b];
    }

    public function index(Request $r)
    {
        [$desde,$hasta] = $this->range($r);
        $base = Pago::whereBetween('created_at', [$desde, $hasta]);
        $total = (int) (clone $base)->sum('monto_centavos');
        $cobros = (clone $base)->count();
        $metodos = (clone $base)->join('metodos_pago', 'metodos_pago.id', '=', 'pagos.metodo_pago_id')->selectRaw('metodos_pago.nombre, SUM(monto_centavos) as total')->groupBy('metodos_pago.nombre')->get();
        $estados = Pedido::whereBetween('created_at', [$desde, $hasta])->selectRaw('estado, COUNT(*) as cantidad')->groupBy('estado')->get();
        $productos = DB::table('pedido_items')->join('pagos', 'pagos.pedido_id', '=', 'pedido_items.pedido_id')->whereBetween('pagos.created_at', [$desde, $hasta])->selectRaw('pedido_items.nombre, SUM(cantidad) as unidades, SUM(total_centavos) as bruto')->groupBy('pedido_items.nombre')->orderByDesc('unidades')->limit(10)->get();
        $pagos = (clone $base)->with('pedido', 'metodoPago')->latest('id')->paginate(20)->withQueryString();

        return $r->expectsJson() ? response()->json(compact('total', 'cobros', 'metodos', 'estados', 'productos')) : view('reportes.index', compact('desde', 'hasta', 'total', 'cobros', 'metodos', 'estados', 'productos', 'pagos'));
    }

    public function csv(Request $r)
    {
        [$a,$b] = $this->range($r);

        return response()->streamDownload(function () use ($a, $b) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Pedido', 'Fecha', 'Método', 'Cobrado COP', 'Descuento COP'], ';', '"', '');
            Pago::with('pedido', 'metodoPago')->whereBetween('created_at', [$a, $b])->orderBy('id')->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $p) {
                    fputcsv($out, [$p->pedido->codigo, $p->created_at->format('Y-m-d H:i'), $p->metodoPago->nombre, Money::input($p->monto_centavos), Money::input($p->pedido->descuento_centavos)], ';', '"', '');
                }
            });
            fclose($out);
        }, 'ventas_embajada.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function audit(Request $r)
    {
        $filters = $r->validate(['accion' => 'nullable|string|max:80']);
        $auditorias = Auditoria::with('usuario')->when($filters['accion'] ?? null, fn ($q, $v) => $q->where('accion', $v))->latest('id')->paginate(30)->withQueryString();

        return $r->expectsJson() ? response()->json($auditorias) : view('reportes.audit', compact('auditorias', 'filters'));
    }
}
