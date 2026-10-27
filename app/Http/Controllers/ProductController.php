<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Rules\MoneyValue;
use App\Services\Audit;
use App\Services\PosTransaction;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends OperationController
{
    public function index(Request $r)
    {
        $productos = Producto::with('categoria')->orderBy('nombre')->paginate(15);

        return $r->expectsJson() ? response()->json($productos) : view('productos.index', compact('productos'));
    }

    public function create()
    {
        return $this->form(new Producto(['activo' => true]));
    }

    public function edit(Producto $producto)
    {
        return $this->form($producto);
    }

    private function form(Producto $producto)
    {
        $categorias = Categoria::where('activa', true)->orderBy('nombre')->get();

        return view('productos.form', compact('producto', 'categorias'));
    }

    private function data(Request $r): array
    {
        $d = $r->validate(['imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', 'quitar_imagen' => 'nullable|boolean', 'nombre' => 'required|string|max:120', 'descripcion' => 'required|string|max:3000', 'alergenos' => 'nullable|string|max:500', 'categoria_id' => ['required', 'integer', Rule::exists('categorias', 'id')->where('activa', true)], 'precio' => ['required', new MoneyValue], 'stock' => 'nullable|integer|min:0|max:100000', 'activo' => 'required|boolean']);
        $d['precio_centavos'] = Money::parse($d['precio']);
        unset($d['precio'], $d['imagen'], $d['quitar_imagen']);
        if ($d['precio_centavos'] < 1) {
            throw ValidationException::withMessages(['precio' => 'El precio debe ser mayor que cero.']);
        }

        return $d;
    }

    private function saveProduct(Request $r, ?Producto $producto = null): Producto
    {
        $d = $this->data($r);
        $newPath = null;
        $oldPath = null;
        if ($r->boolean('quitar_imagen')) {
            $d['imagen'] = null;
        }
        if ($r->hasFile('imagen')) {
            $newPath = $r->file('imagen')->store('productos', 'public');
            if ($newPath === false) {
                throw ValidationException::withMessages(['imagen' => 'No se pudo guardar la imagen. Revisa los permisos de storage.']);
            }
            $d['imagen'] = $newPath;
        }
        try {
            $saved = PosTransaction::run(function () use ($producto, $d, &$oldPath) {
                $before = null;
                if ($producto) {
                    $producto->refresh();
                    $before = $producto->toArray();
                    $oldPath = $producto->imagen;
                    $producto->update($d);
                } else {
                    $producto = Producto::create($d);
                }
                Audit::record($before === null ? 'producto_creado' : 'producto_modificado', $producto, $before, $producto->toArray());

                return $producto;
            });
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $e;
        }
        if (($newPath || $r->boolean('quitar_imagen')) && $oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $saved;
    }

    public function store(Request $r)
    {
        $p = $this->saveProduct($r);

        return $this->done($r, 'Producto registrado.', 'productos.index', [], $p, 201);
    }

    public function update(Request $r, Producto $producto)
    {
        $p = $this->saveProduct($r, $producto);

        return $this->done($r, 'Producto actualizado.', 'productos.index', [], $p);
    }

    public function toggle(Request $r, Producto $producto)
    {
        $d = $r->validate(['activo' => 'required|boolean', 'motivo' => 'required|string|min:5|max:500']);
        PosTransaction::run(function () use ($producto, $d) {
            $producto->refresh();
            $old = ['activo' => $producto->activo];
            $producto->update(['activo' => $d['activo']]);
            Audit::record('producto_activacion', $producto, $old, ['activo' => $producto->activo], $d['motivo']);
        });

        return $this->done($r, 'Disponibilidad actualizada.', 'productos.index', [], $producto);
    }

    public function categories(Request $r)
    {
        $categorias = Categoria::withCount('productos')->orderBy('nombre')->get();

        return $r->expectsJson() ? response()->json($categorias) : view('productos.categories', compact('categorias'));
    }

    public function categoryStore(Request $r)
    {
        $d = $r->validate(['nombre' => 'required|string|max:80|unique:categorias,nombre']);
        $c = Categoria::create($d);
        Audit::record('categoria_creada', $c, null, $c->toArray());

        return $this->done($r, 'Categoría creada.', 'categorias.index', [], $c, 201);
    }

    public function categoryUpdate(Request $r, Categoria $categoria)
    {
        $d = $r->validate(['nombre' => ['required', 'string', 'max:80', Rule::unique('categorias', 'nombre')->ignore($categoria)], 'activa' => 'required|boolean']);
        PosTransaction::run(function () use ($categoria, $d) {
            $old = $categoria->toArray();
            $categoria->update($d);
            Audit::record('categoria_modificada', $categoria, $old, $categoria->toArray());
        });

        return $this->done($r, 'Categoría actualizada.', 'categorias.index', [], $categoria);
    }
}
