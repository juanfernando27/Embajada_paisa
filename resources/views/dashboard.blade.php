@extends('layouts.app')
@section('title', 'Inicio')
@section('content')
<div class="page-intro"><div><div class="eyebrow">VISTA GENERAL</div><h1>Tu restaurante, al día.</h1><p>Bienvenido, {{ explode(' ', auth()->user()->name)[0] }}. Cada detalle cuenta.</p></div><span class="date-label">{{ now()->locale('es')->translatedFormat('d \d\e F, Y') }}</span></div>
<section class="welcome-banner"><div><span class="eyebrow">LA EMBAJADA PAISA</span><h2>Más tiempo para<br><em>recibir bien.</em></h2><p>Organiza tus mesas y conoce a quienes nos visitan.</p><a class="button light" href="{{ route('menu.index') }}"><x-icon name="plus" size="17"/> Crear un pedido</a></div><div class="banner-art" aria-hidden="true"><div class="round-table"><span>ep</span></div><i class="chair one"></i><i class="chair two"></i><i class="chair three"></i><i class="chair four"></i><b>✦</b></div></section>
<div class="operation-strip"><a href="{{ route('pedidos.index') }}"><x-icon name="receipt"/><span><small>Pedidos en atención</small><strong>{{ $operacion['pedidos'] }}</strong></span></a><a href="{{ route('reservas.index') }}"><x-icon name="calendar"/><span><small>Reservas de hoy</small><strong>{{ $operacion['reservas'] }}</strong></span></a><a href="{{ route('caja.index') }}"><x-icon name="cash"/><span><small>Cobrado hoy COP</small><strong>{{ \App\Support\Money::format($operacion['cobros']) }}</strong></span></a></div><div class="stats-grid">
 @foreach([['Clientes registrados', $totalClientes, 'users', 'Personas en nuestra base'], ['Mesas registradas', $totalMesas, 'table', 'Distribución del restaurante'], ['Puestos habilitados', $capacidad, 'seats', 'Capacidad física habilitada']] as [$label, $value, $icon, $desc])
 <div class="stat"><div class="stat-head">{{ $label }}<span><x-icon :name="$icon"/></span></div><strong>{{ $value }}</strong><small>{{ $desc }}</small></div>
 @endforeach
 @if($totalUsuarios !== null)<div class="stat"><div class="stat-head">Usuarios activos<span><x-icon name="shield"/></span></div><strong>{{ $totalUsuarios }}</strong><small>Cuentas con acceso habilitado</small></div>@endif
</div>
<div class="dashboard-grid">
 <section class="panel"><div class="panel-heading"><div><h2>Últimos clientes</h2><p>Una bienvenida que comienza por su nombre.</p></div><a class="text-link" href="{{ route('clientes.index') }}">Ver todos <x-icon name="arrow" size="16"/></a></div>
  @forelse($clientes as $cliente)<a class="recent-row" href="{{ route('clientes.show', $cliente) }}"><span class="avatar pale">{{ mb_substr($cliente->nombre, 0, 1) }}{{ mb_substr($cliente->apellido, 0, 1) }}</span><span><strong>{{ $cliente->nombre_completo }}</strong><small>{{ $cliente->tipo_documento }} {{ $cliente->numero_documento }}</small></span><x-icon name="arrow" size="16"/></a>@empty<div class="empty"><x-icon name="users" size="32"/><h3>Aún no hay clientes</h3><p>Registra el primero para empezar.</p></div>@endforelse
 </section>
 <section class="panel"><div class="panel-heading"><div><h2>Un vistazo a las mesas</h2><p>Configuración del espacio.</p></div><a class="text-link" href="{{ route('mesas.index') }}">Ver mesas <x-icon name="arrow" size="16"/></a></div>
 <div class="mini-mesas">@forelse($mesas as $mesa)<a href="{{ route('mesas.show', $mesa) }}" class="mini-mesa {{ $mesa->habilitada ? '' : 'disabled' }}"><x-icon name="table" size="27"/><strong>Mesa {{ $mesa->numero }}</strong><small>{{ $mesa->capacidad }} puestos</small></a>@empty<div class="empty"><p>Aún no hay mesas registradas.</p></div>@endforelse</div>
 <p class="panel-footnote"><span class="status-dot"></span> Capacidad física de las mesas habilitadas.</p>
 </section>
</div>
@endsection
