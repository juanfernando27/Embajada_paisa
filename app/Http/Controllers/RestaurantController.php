<?php

namespace App\Http\Controllers;

use App\Models\RestauranteConfig;
use App\Services\Audit;
use Illuminate\Http\Request;

class RestaurantController extends OperationController
{
    public function show(Request $r)
    {
        $info = RestauranteConfig::firstOrFail();

        return $r->expectsJson() ? response()->json($info) : view('restaurante.show', compact('info'));
    }

    public function edit()
    {
        return view('restaurante.form', ['info' => RestauranteConfig::firstOrFail()]);
    }

    public function update(Request $r)
    {
        $d = $r->validate(['nombre' => 'required|string|max:120', 'ciudad' => 'required|string|max:100', 'direccion' => 'nullable|string|max:200', 'telefono' => 'nullable|string|max:30', 'horario' => 'nullable|string|max:300', 'descripcion' => 'nullable|string|max:3000', 'latitud' => 'nullable|numeric|between:-90,90|required_with:longitud', 'longitud' => 'nullable|numeric|between:-180,180|required_with:latitud']);
        $c = RestauranteConfig::firstOrFail();
        $old = $c->toArray();
        $c->update($d);
        Audit::record('restaurante_actualizado', $c, $old, $c->toArray());

        return $this->done($r, 'Información actualizada.', 'restaurante.show', [], $c);
    }
}
