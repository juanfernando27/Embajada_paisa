<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $r)
    {
        $filters = $r->validate(['categoria' => 'nullable|integer|exists:categorias,id', 'buscar' => 'nullable|string|max:100']);
        $categorias = Categoria::where('activa', true)->orderBy('nombre')->get();
        $productos = Producto::with('categoria')->ofertados()->when($filters['categoria'] ?? null, fn ($q, $v) => $q->where('categoria_id', $v))->when($filters['buscar'] ?? null, fn ($q, $v) => $q->where('nombre', 'like', '%'.$v.'%'))->orderBy('nombre')->paginate(12)->withQueryString();

        return $r->expectsJson() ? response()->json(compact('productos', 'categorias')) : view('menu.index', compact('productos', 'categorias', 'filters'));
    }

    public function show(Request $r, Producto $producto)
    {
        abort_unless($producto->activo && $producto->categoria->activa, 404);
        $producto->load('categoria');

        return $r->expectsJson() ? response()->json($producto) : view('menu.show', compact('producto'));
    }
}
