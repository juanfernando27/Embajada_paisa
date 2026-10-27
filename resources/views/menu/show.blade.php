@extends('layouts.app')
@section('title', 'Detalle del producto')
@section('content')

<a class="back-link" href="{{ route('menu.index') }}"><x-icon name="back"/> Volver a la carta</a><div class="product-detail"><x-product-art :seed="$producto->id" :imagen="$producto->imagen" :nombre="$producto->nombre" :categoria="$producto->categoria->nombre"/><section class="panel form-panel"><div class="eyebrow">{{ $producto->categoria->nombre }}</div><h1>{{ $producto->nombre }}</h1><p class="description">{{ $producto->descripcion }}</p><h2 class="price">{{ \App\Support\Money::format($producto->precio_centavos) }}</h2>@if($producto->alergenos)<div class="soft-note"><p><strong>Ingredientes y alérgenos:</strong> {{ $producto->alergenos }}</p></div>@endif<p class="fine-print muted">Si tienes una alergia, confirma los ingredientes con el personal antes de pedir.</p>@auth<form class="stack spaced" method="post" action="{{ route('carrito.store') }}">@csrf<input type="hidden" name="producto_id" value="{{ $producto->id }}"><x-field name="cantidad" label="Cantidad" type="number" :required="true" value="1" min="1" :max="min(99,$producto->stock??99)"/><button class="button" @disabled($producto->stock===0)>{{ $producto->stock===0?'Agotado':'Agregar al carrito' }}</button></form>@else<a class="button spaced" href="{{ route('login') }}">Ingresa para pedir</a>@endauth</section></div>

@endsection
