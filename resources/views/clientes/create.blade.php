@extends('layouts.app')
@section('title', 'Registrar cliente')
@section('content')
<a class="back-link" href="{{ route('clientes.index') }}"><x-icon name="back" size="17"/> Volver a clientes</a>
<div class="page-intro"><div><div class="eyebrow">UNA NUEVA BIENVENIDA</div><h1>Registrar cliente</h1><p>Guarda sus datos de identificación y contacto.</p></div></div>
<div class="form-layout"><form class="panel form-panel" method="post" action="{{ route('clientes.store') }}">@csrf
<div class="form-section-title"><span>01</span><div><h2>Identificación</h2><p>Los campos con * son obligatorios.</p></div></div>
<div class="form-grid"><div class="field"><label for="tipo_documento">Tipo de documento <span class="required">*</span></label><select id="tipo_documento" name="tipo_documento" required>@foreach(['CC' => 'Cédula de ciudadanía', 'CE' => 'Cédula de extranjería', 'TI' => 'Tarjeta de identidad', 'PAS' => 'Pasaporte'] as $key => $label)<option value="{{ $key }}" @selected(old('tipo_documento', 'CC') === $key)>{{ $label }}</option>@endforeach</select></div>
<x-field name="numero_documento" label="Número de documento" :required="true" maxlength="30" hint="Entre 4 y 30 letras, números o guiones."/>
<x-field name="nombre" label="Nombres" :required="true" maxlength="100" autocomplete="given-name"/>
<x-field name="apellido" label="Apellidos" :required="true" maxlength="100" autocomplete="family-name"/></div>
<div class="form-section-title divided"><span>02</span><div><h2>Contacto</h2><p>Información para comunicarse con el cliente.</p></div></div>
<div class="form-grid"><x-field name="telefono" label="Teléfono" type="tel" :required="true" maxlength="20" autocomplete="tel" placeholder="300 000 0000"/><x-field name="email" label="Correo electrónico" type="email" maxlength="150" autocomplete="email" hint="Opcional. Facilita el contacto con el cliente."/></div>
<div class="form-actions"><a class="button secondary" href="{{ route('clientes.index') }}">Cancelar</a><button class="button"><x-icon name="check" size="18"/> Guardar cliente</button></div></form>
<aside class="form-note"><x-icon name="users" size="28"/><h3>Un registro, una persona.</h3><p>El documento y el correo, cuando se registra, deben ser únicos.</p><hr><p>Este registro no crea una cuenta de acceso. El administrador puede asociar una desde Usuarios y roles.</p></aside></div>
@endsection
