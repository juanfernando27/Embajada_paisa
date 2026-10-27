<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->validate(['buscar' => ['nullable', 'string', 'max:100'], 'rol' => ['nullable', 'in:administrador,trabajador,cliente']]);
        $usuarios = User::query()->when($filters['buscar'] ?? null, fn ($q, $value) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$value.'%')->orWhere('email', 'like', '%'.$value.'%')))
            ->when($filters['rol'] ?? null, fn ($q, $value) => $q->where('rol', $value))
            ->orderBy('name')->orderBy('id')->paginate(10)->withQueryString();

        return $request->expectsJson() ? response()->json($usuarios) : view('usuarios.index', compact('usuarios', 'filters'));
    }

    public function create(): View
    {
        return $this->form(new User(['activo' => true, 'rol' => 'trabajador']));
    }

    public function edit(User $usuario): View
    {
        return $this->form($usuario);
    }

    private function form(User $usuario): View
    {
        $clientes = Cliente::query()->where(fn ($q) => $q->whereNull('user_id')->when($usuario->exists, fn ($q) => $q->orWhere('user_id', $usuario->id)))
            ->orderBy('apellido')->get();

        return view('usuarios.form', compact('usuario', 'clientes'));
    }

    public function store(UsuarioRequest $request): RedirectResponse|JsonResponse
    {
        $usuario = DB::transaction(function () use ($request) {
            $usuario = User::create(Arr::except($request->validated(), ['cliente_id']));
            $this->associate($usuario, $request->input('cliente_id'));

            return $usuario;
        });

        return $request->expectsJson() ? response()->json($usuario, 201) : redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse|JsonResponse
    {
        DB::transaction(function () use ($request, $usuario): void {
            // Serializa las modificaciones de administradores para conservar al menos uno activo.
            $administradores = User::where('rol', 'administrador')->orderBy('id')->lockForUpdate()->get();
            $usuario->refresh();
            $data = Arr::except($request->validated(), ['cliente_id']);
            if ($usuario->is($request->user()) && ($data['rol'] !== 'administrador' || ! $data['activo'])) {
                throw ValidationException::withMessages(['rol' => 'No puedes quitarte el rol de administrador ni desactivar tu propia cuenta.']);
            }
            if ($usuario->rol === 'administrador' && $usuario->activo && ($data['rol'] !== 'administrador' || ! $data['activo']) && $administradores->where('activo', true)->count() <= 1) {
                throw ValidationException::withMessages(['rol' => 'Debe existir al menos un administrador activo.']);
            }
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['remember_token'] = Str::random(60);
            }
            $usuario->fill(Arr::except($data, ['remember_token']));
            if (isset($data['remember_token'])) {
                $usuario->remember_token = $data['remember_token'];
            }
            $usuario->save();
            $this->associate($usuario, $usuario->rol === 'cliente' ? $request->input('cliente_id') : null);
        });

        return $request->expectsJson() ? response()->json($usuario->fresh()) : redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    private function associate(User $usuario, mixed $clienteId): void
    {
        $cliente = $clienteId ? Cliente::lockForUpdate()->findOrFail($clienteId) : null;
        if ($cliente && $cliente->user_id && $cliente->user_id !== $usuario->id) {
            throw ValidationException::withMessages(['cliente_id' => 'Este cliente ya tiene una cuenta asociada.']);
        }
        Cliente::where('user_id', $usuario->id)->update(['user_id' => null]);
        if ($cliente) {
            $cliente->update(['user_id' => $usuario->id]);
        }
    }
}
