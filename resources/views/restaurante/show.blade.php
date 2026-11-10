@extends('layouts.app')
@section('title', 'El restaurante')
@section('content')
    @php
        $coordinates = $info->latitud !== null && $info->longitud !== null
            ? $info->latitud.','.$info->longitud
            : null;
        $destination = $coordinates ?? ($info->direccion
            ? $info->direccion.', '.$info->ciudad.', Colombia'
            : null);
        $phone = preg_replace('/[^\d+]/', '', $info->telefono ?? '');
    @endphp

    <section class="restaurant-hero">
        <div class="eyebrow">PASTO · NARIÑO · COLOMBIA</div>
        <h1>{{ $info->nombre }}</h1>
        <p>{{ $info->descripcion ?? 'Un lugar para compartir los sabores de nuestra tradición.' }}</p>
        <figure class="restaurant-food">
            <img src="{{ asset('images/empanadas.png') }}" alt="Presentación ilustrativa de empanadas colombianas">
            <figcaption>Inspiración gastronómica</figcaption>
        </figure>
    </section>

    <div class="management-grid">
        <section class="panel form-panel">
            <div class="eyebrow">VEN A NUESTRA MESA</div>
            <h2>Encuéntranos.</h2>
            <dl class="contact-list">
                <div>
                    <dt><x-icon name="pin"/> Ubicación</dt>
                    <dd>{{ $info->direccion ?? 'Dirección por confirmar con el restaurante.' }}<br>{{ $info->ciudad }}</dd>
                </div>
                <div>
                    <dt><x-icon name="phone"/> Contacto</dt>
                    <dd>
                        @if($phone)
                            <a class="restaurant-phone" href="tel:{{ $phone }}">{{ $info->telefono }}</a>
                        @else
                            {{ $info->telefono ?? 'Teléfono por confirmar.' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt><x-icon name="calendar"/> Horario</dt>
                    <dd class="restaurant-hours">{{ $info->horario ?? 'Consulta el horario con nuestro equipo.' }}</dd>
                </div>
            </dl>

            @if($destination)
                <iframe
                    class="restaurant-map"
                    title="Mapa de ubicación de {{ $info->nombre }}"
                    src="https://www.google.com/maps?q={{ rawurlencode($destination) }}&amp;z=17&amp;output=embed"
                    loading="lazy"
                    allowfullscreen
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
                <a class="button secondary spaced" target="_blank" rel="noopener noreferrer"
                    href="https://www.google.com/maps/dir/?api=1&amp;destination={{ rawurlencode($destination) }}">
                    Cómo llegar
                </a>
            @endif
        </section>

        <section class="panel form-panel invitation-panel">
            <span class="small-star">✦</span>
            <h2>Una mesa.<br>Muchas historias.</h2>
            <p>Elige tus favoritos y comparte un buen momento.</p>
            <div class="stack spaced">
                <a class="button" href="{{ route('menu.index') }}">Explorar la carta</a>
                <a class="button secondary" href="{{ auth()->check() ? route('reservas.create') : route('login') }}">Reservar una mesa</a>
            </div>
        </section>
    </div>
@endsection
