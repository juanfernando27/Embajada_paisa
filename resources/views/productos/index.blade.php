@extends('layouts.app')
@section('title', 'Productos')
@section('content')

<div class="page-intro"><div><div class="eyebrow">ADMINISTRACIÓN DE LA CARTA</div><h1>Productos</h1><p>Precios, categorías y disponibilidad.</p></div><a class="button" href="{{ route('productos.create') }}"><x-icon name="plus"/> Registrar producto</a></div><section class="panel"><div class="table-scroll" tabindex="0"><table><thead><tr><th>Producto</th><th>Categoría</th><th>Precio COP</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>@forelse($productos as $p)<tr><td><div class="product-admin-name"><div class="product-admin-thumb"><x-product-art :seed="$p->id" :imagen="$p->imagen"/></div><strong>{{ $p->nombre }}</strong></div></td><td>{{ $p->categoria->nombre }}</td><td>{{ \App\Support\Money::format($p->precio_centavos) }}</td><td>{{ $p->stock??'Sin límite' }}</td><td><span class="badge {{ $p->activo?'positive':'gray' }}">{{ $p->activo?'Activo':'Desactivado' }}</span></td><td><a class="text-link" href="{{ route('productos.edit',$p) }}">Editar <x-icon name="edit" size="16"/></a></td></tr>@empty<tr><td colspan="6" class="empty">Registra el primer producto.</td></tr>@endforelse</tbody></table></div>{{ $productos->links() }}</section>

@endsection
