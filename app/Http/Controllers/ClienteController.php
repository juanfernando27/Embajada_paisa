<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['buscar' => ['nullable', 'string', 'max:100']]);
        $buscar = $filters['buscar'] ?? '';
        $clientes = Cliente::query()->when($buscar !== '', function ($query) use ($buscar): void {
            $query->where(function ($q) use ($buscar): void {
                foreach (['nombre', 'apellido', 'numero_documento', 'email', 'telefono'] as $field) {
                    $q->orWhere($field, 'like', '%'.$buscar.'%');
                }
            });
        })->orderBy('apellido')->orderBy('nombre')->orderBy('id')->paginate(10)->withQueryString();

        return view('clientes.index', compact('clientes', 'buscar'));
    }

    public function create(): View
    {
        return view('clientes.create');
    }

    public function store(ClienteRequest $request): RedirectResponse
    {
        $cliente = Cliente::create($request->validated());

        return redirect()->route('clientes.show', $cliente)->with('success', 'Cliente registrado correctamente.');
    }

    public function show(Cliente $cliente): View
    {
        return view('clientes.show', compact('cliente'));
    }

    public function profile(Request $request): View
    {
        return view('clientes.profile', ['cliente' => $request->user()->cliente]);
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(ClienteRequest $request, Cliente $cliente)
    {
        $cliente->update($request->validated());

        return $request->expectsJson() ? response()->json($cliente) : redirect()->route('clientes.show', $cliente)->with('success', 'Ficha actualizada.');
    }

    public function destroy(Request $request, Cliente $cliente)
    {
        if ($cliente->user_id || Pedido::where('cliente_id', $cliente->id)->exists() || Reserva::where('cliente_id', $cliente->id)->exists()) {
            throw ValidationException::withMessages(['cliente' => 'Conserva esta ficha: tiene cuenta o historial asociado.']);
        }
        $cliente->delete();

        return $request->expectsJson() ? response()->json(['message' => 'Ficha eliminada.']) : redirect()->route('clientes.index')->with('success', 'Ficha eliminada.');
    }
}
