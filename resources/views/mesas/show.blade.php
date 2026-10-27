@extends('layouts.app')
@section('title', 'Detalle de mesa')
@section('content')
<a class="back-link" href="{{ route('mesas.index') }}"><x-icon name="back" size="17"/> Volver a mesas</a>
<div class="page-intro"><div><div class="eyebrow">ESPACIO DEL RESTAURANTE</div><h1>Mesa {{ $mesa->numero }}</h1><p>{{ $mesa->ubicacion }}</p></div>@if(auth()->user()->esAdministrador())<a class="button" href="{{ route('mesas.edit', $mesa) }}"><x-icon name="edit" size="18"/> Editar mesa</a>@endif</div>
<section class="panel detail-panel"><div class="profile-banner"><span class="table-badge"><x-icon name="table" size="45"/></span><div><h2>{{ $mesa->capacidad }} puestos</h2><span class="badge {{ $mesa->habilitada ? 'positive' : 'gray' }}">{{ $mesa->habilitada ? 'Habilitada' : 'Fuera de servicio' }}</span></div></div><dl class="detail-grid"><div><dt>Número</dt><dd>{{ $mesa->numero }}</dd></div><div><dt>Ubicación</dt><dd>{{ $mesa->ubicacion }}</dd></div><div class="full-span"><dt>Descripción</dt><dd>{{ $mesa->descripcion ?? 'Sin descripción adicional.' }}</dd></div></dl></section>
@if(auth()->user()->esAdministrador())<section class="danger-zone"><div><h3>Eliminar mesa</h3><p>La mesa se retirará del inventario de este adelanto.</p></div><form method="post" action="{{ route('mesas.destroy', $mesa) }}" data-confirm="¿Eliminar definitivamente la mesa {{ $mesa->numero }}? Puedes ponerla fuera de servicio para conservarla.">@csrf @method('DELETE')<button class="button danger-outline"><x-icon name="trash" size="17"/> Eliminar mesa</button></form></section>@endif
@endsection
