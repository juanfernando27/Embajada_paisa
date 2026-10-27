<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Reserva;
use App\Services\ReservationService;
use Illuminate\Http\Request;

class ReservationController extends OperationController
{
    public function __construct(private ReservationService $service) {}

    private function data(Request $r, bool $full = true): array
    {
        $rules = ['fecha' => 'required|date_format:Y-m-d', 'hora' => 'required|date_format:H:i', 'duracion' => 'required|integer|min:30|max:240', 'comensales' => 'required|integer|min:1|max:30'];
        if ($full) {
            $rules += ['mesa_id' => 'required|integer|exists:mesas,id', 'cliente_id' => ($r->user()->rol === 'cliente' ? 'nullable' : 'required').'|integer|exists:clientes,id', 'notas' => 'nullable|string|max:500'];
        }

        return $r->validate($rules);
    }

    public function index(Request $r)
    {
        $filters = $r->validate(['fecha' => 'nullable|date_format:Y-m-d', 'estado' => 'nullable|in:confirmada,en_curso,finalizada,cancelada']);
        $reservas = Reserva::with('mesa', 'cliente')->when($r->user()->rol === 'cliente', fn ($q) => $q->whereHas('cliente', fn ($c) => $c->where('user_id', $r->user()->id)))->when($filters['fecha'] ?? null, fn ($q, $v) => $q->whereDate('inicio', $v))->when($filters['estado'] ?? null, fn ($q, $v) => $q->where('estado', $v))->orderByDesc('inicio')->paginate(12)->withQueryString();

        return $r->expectsJson() ? response()->json($reservas) : view('reservas.index', compact('reservas', 'filters'));
    }

    public function create(Request $r)
    {
        return $this->form($r, new Reserva);
    }

    public function edit(Request $r, Reserva $reserva)
    {
        $this->own($r, $reserva);

        return $this->form($r, $reserva);
    }

    private function form(Request $r, Reserva $reserva)
    {
        $r->validate(['fecha' => 'nullable|date_format:Y-m-d', 'hora' => 'nullable|date_format:H:i', 'comensales' => 'nullable|integer|min:1|max:30', 'duracion' => 'nullable|integer|min:30|max:240', 'mesa_id' => 'nullable|integer']);
        $mesas = Mesa::where('habilitada', true)->orderBy('numero')->get();
        $clientes = $r->user()->rol !== 'cliente' ? Cliente::orderBy('nombre')->get() : collect();

        return view('reservas.form', compact('reserva', 'mesas', 'clientes'));
    }

    public function available(Request $r)
    {
        $d = $this->data($r, false);
        $except = null;
        if ($r->filled('reserva_id')) {
            $r->validate(['reserva_id' => 'integer|exists:reservas,id']);
            $existing = Reserva::findOrFail($r->input('reserva_id'));
            $this->own($r, $existing);
            $except = $existing->id;
        }
        $mesas = $this->service->available($d, $except);

        return $r->expectsJson() ? response()->json($mesas) : view('reservas.availability', compact('mesas', 'd'));
    }

    public function store(Request $r)
    {
        $v = $this->service->save($r->user(), $this->data($r));

        return $this->done($r, 'Reserva confirmada.', 'reservas.index', [], $v, 201);
    }

    public function update(Request $r, Reserva $reserva)
    {
        $this->own($r, $reserva);
        $v = $this->service->save($r->user(), $this->data($r), $reserva);

        return $this->done($r, 'Reserva actualizada.', 'reservas.index', [], $v);
    }

    public function state(Request $r, Reserva $reserva)
    {
        $this->own($r, $reserva);
        $d = $r->validate(['estado' => 'required|in:en_curso,finalizada,cancelada', 'motivo' => 'required|string|min:5|max:500']);
        $v = $this->service->state($r->user(), $reserva, $d['estado'], $d['motivo']);

        return $this->done($r, 'Reserva actualizada.', 'reservas.index', [], $v);
    }
}
