@extends('layouts.app')
@section('title', $mesa->exists ? 'Editar mesa' : 'Crear mesa')
@section('content')
<a class="back-link" href="{{ route('mesas.index') }}"><x-icon name="back" size="17"/> Volver a mesas</a>
<div class="page-intro"><div><div class="eyebrow">CONFIGURACIÓN DEL ESPACIO</div><h1>{{ $mesa->exists ? 'Editar mesa '.$mesa->numero : 'Crear mesa' }}</h1><p>Define sus características y habilitación.</p></div></div>
<div class="form-layout"><form class="panel form-panel" method="post" action="{{ $mesa->exists ? route('mesas.update', $mesa) : route('mesas.store') }}">@csrf @if($mesa->exists)@method('PUT')@endif
<div class="form-section-title"><span>01</span><div><h2>Datos de la mesa</h2><p>Los campos con * son obligatorios.</p></div></div><div class="form-grid">
<x-field name="numero" label="Número de mesa" type="number" :required="true" :value="$mesa->numero" min="1" max="999" step="1"/>
<x-field name="capacidad" label="Capacidad (personas)" type="number" :required="true" :value="$mesa->capacidad" min="1" max="30" step="1"/>
<x-field name="ubicacion" label="Ubicación" :required="true" :value="$mesa->ubicacion" maxlength="80" placeholder="Ej. Salón principal"/>
<div class="field"><label for="habilitada">Habilitación <span class="required">*</span></label><select name="habilitada" id="habilitada" required><option value="1" @selected((string) old('habilitada', (int) $mesa->habilitada) === '1')>Habilitada</option><option value="0" @selected((string) old('habilitada', (int) $mesa->habilitada) === '0')>Fuera de servicio</option></select></div>
<div class="field full-span"><label for="descripcion">Descripción</label><textarea name="descripcion" id="descripcion" maxlength="500" rows="4" placeholder="Detalles que ayuden al equipo a identificar la mesa.">{{ old('descripcion', $mesa->descripcion) }}</textarea><small>Opcional. Máximo 500 caracteres.</small></div></div>
<div class="form-actions"><a class="button secondary" href="{{ route('mesas.index') }}">Cancelar</a><button class="button"><x-icon name="check" size="18"/> Guardar mesa</button></div></form>
<aside class="form-note"><x-icon name="table" size="30"/><h3>Un espacio bien organizado.</h3><p>Cada mesa tiene un número único y una capacidad entre 1 y 30 personas.</p><hr><p>Una mesa fuera de servicio se conserva en el inventario, pero no suma puestos habilitados.</p></aside></div>
@endsection
