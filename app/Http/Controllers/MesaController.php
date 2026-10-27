<?php

namespace App\Http\Controllers;

use App\Http\Requests\MesaRequest;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Reserva;
use App\Services\PosTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MesaController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'buscar' => ['nullable', 'string', 'max:80'], 'estado' => ['nullable', 'in:habilitada,fuera_servicio'],
        ]);
        $mesas = Mesa::query()->when($filters['buscar'] ?? null, function ($q, $value): void {
            $q->where(fn ($q) => $q->where('numero', 'like', '%'.$value.'%')->orWhere('ubicacion', 'like', '%'.$value.'%'));
        })->when($filters['estado'] ?? null, fn ($q, $value) => $q->where('habilitada', $value === 'habilitada'))
            ->orderBy('numero')->paginate(12)->withQueryString();

        return view('mesas.index', compact('mesas', 'filters'));
    }

    public function create(): View
    {
        return view('mesas.form', ['mesa' => new Mesa(['habilitada' => true])]);
    }

    public function store(MesaRequest $request): RedirectResponse
    {
        $mesa = Mesa::create($request->validated());

        return redirect()->route('mesas.show', $mesa)->with('success', 'Mesa creada correctamente.');
    }

    public function show(Mesa $mesa): View
    {
        return view('mesas.show', compact('mesa'));
    }

    public function edit(Mesa $mesa): View
    {
        return view('mesas.form', compact('mesa'));
    }

    public function update(MesaRequest $request, Mesa $mesa): RedirectResponse
    {
        PosTransaction::run(function () use ($mesa, $request) {
            $mesa->refresh();
            if (! $request->boolean('habilitada') && (Reserva::activas()->where('mesa_id', $mesa->id)->where('fin', '>', now())->exists() || Pedido::where('mesa_id', $mesa->id)->whereIn('estado', ['pendiente', 'preparando', 'listo'])->exists())) {
                throw ValidationException::withMessages(['mesa' => 'Primero atiende los pedidos o reprograma las reservas de esta mesa.']);
            }
            if (Reserva::activas()->where('mesa_id', $mesa->id)->where('fin', '>', now())->where('comensales', '>', $request->integer('capacidad'))->exists()) {
                throw ValidationException::withMessages(['capacidad' => 'La capacidad no puede quedar por debajo de una reserva vigente. Reprograma esa reserva primero.']);
            }
            $mesa->update($request->validated());
        });

        return redirect()->route('mesas.show', $mesa)->with('success', 'Mesa actualizada correctamente.');
    }

    public function destroy(Mesa $mesa): RedirectResponse
    {
        PosTransaction::run(function () use ($mesa) {
            if (Pedido::where('mesa_id', $mesa->id)->exists() || Reserva::where('mesa_id', $mesa->id)->exists()) {
                throw ValidationException::withMessages(['mesa' => 'La mesa tiene historial. Ponla fuera de servicio para conservarlo.']);
            }
            $mesa->delete();
        });

        return redirect()->route('mesas.index')->with('success', 'Mesa eliminada correctamente.');
    }
}
