@extends('layouts.app')
@section('title', 'Ficha del cliente')
@section('content')
<a class="back-link" href="{{ route('clientes.index') }}"><x-icon name="back" size="17"/> Volver a clientes</a>
<div class="page-intro"><div><div class="eyebrow">FICHA DEL CLIENTE</div><h1>{{ $cliente->nombre_completo }}</h1><p>Registrado el {{ $cliente->created_at->format('d/m/Y') }}.</p></div><span class="label-pill"><x-icon name="check" size="16"/> Cliente registrado</span></div>
@include('clientes.details')<div class="form-actions"><a class="button" href="{{ route('clientes.edit',$cliente) }}">Editar ficha</a>@if(auth()->user()->esAdministrador())<form method="post" action="{{ route('clientes.destroy',$cliente) }}" data-confirm="¿Eliminar esta ficha? Solo se permite si no tiene cuenta ni historial.">@csrf @method('DELETE')<button class="button danger-outline">Eliminar ficha</button></form>@endif</div>
@endsection
