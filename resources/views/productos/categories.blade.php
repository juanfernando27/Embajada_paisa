@extends('layouts.app')
@section('title', 'Categorías')
@section('content')

<div class="page-intro"><div><div class="eyebrow">ORDENA TU CARTA</div><h1>Categorías</h1><p>Una selección fácil de explorar.</p></div></div><div class="management-grid"><section class="panel form-panel">@forelse($categorias as $c)<form class="category-edit" method="post" action="{{ route('categorias.update',$c) }}">@csrf @method('PUT')<div class="form-grid"><x-field name="nombre" :id="'nombre-'.$c->id" :label="'Categoría · '.$c->productos_count.' productos'" :value="$c->nombre" :required="true" maxlength="80"/><div class="field"><label for="activa-{{ $c->id }}">Visibilidad</label><select name="activa" id="activa-{{ $c->id }}"><option value="1" @selected($c->activa)>Activa</option><option value="0" @selected(!$c->activa)>Oculta</option></select></div></div><button class="button secondary spaced">Guardar categoría</button></form>@empty<p class="muted">Aún no hay categorías.</p>@endforelse</section><form class="panel form-panel" method="post" action="{{ route('categorias.store') }}">@csrf<h2>Nueva categoría</h2><x-field name="nombre" label="Nombre" :required="true" maxlength="80"/><button class="button spaced">Crear categoría</button></form></div>

@endsection
