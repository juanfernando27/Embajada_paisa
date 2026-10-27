@extends('layouts.app')
@section('title', 'Mi perfil')
@section('content')
<div class="page-intro"><div><div class="eyebrow">TU ESPACIO EN LA EMBAJADA</div><h1>Hola, {{ explode(' ', auth()->user()->name)[0] }}.</h1><p>Estos son tus datos registrados en el restaurante.</p></div></div>
@if($cliente)@include('clientes.details')@else<section class="panel empty"><x-icon name="users" size="40"/><h2>Tu perfil está por asociar</h2><p>Tu cuenta está activa. Pide al administrador que la vincule a tu registro de cliente.</p></section>@endif
<div class="soft-note"><x-icon name="lock" size="20"/><p>Tu cuenta permite consultar únicamente tu información. Para corregir tus datos, comunícate con el restaurante.</p></div>
<div class="hero-actions spaced"><a class="button" href="{{ route('menu.index') }}">Explorar la carta</a><a class="button secondary" href="{{ route('reservas.create') }}">Reservar una mesa</a></div>@endsection
