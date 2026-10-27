@extends('layouts.app')
@section('title', 'Disponibilidad')
@section('content')

<div class="page-intro"><div><h1>Mesas disponibles</h1><p>{{ $d['fecha'] }} · {{ $d['hora'] }} · {{ $d['comensales'] }} personas</p></div></div><div class="mesas-grid">@forelse($mesas as $m)<article class="mesa-card"><x-icon name="table" size="40"/><h2>Mesa {{ $m->numero }}</h2><p>{{ $m->ubicacion }} · {{ $m->capacidad }} puestos</p><a class="button spaced" href="{{ route('reservas.create',[...$d,'mesa_id'=>$m->id]) }}">Elegir mesa</a></article>@empty<section class="panel empty full-span"><h2>No hay mesas para ese horario.</h2><a class="button spaced" href="{{ route('reservas.create') }}">Buscar otro horario</a></section>@endforelse</div>

@endsection
