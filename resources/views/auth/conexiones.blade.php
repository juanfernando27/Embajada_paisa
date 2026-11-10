@extends('layouts.app')
@section('title', 'Conexiones')
@section('content')
<section class="panel form-panel">
    <div class="eyebrow">SEGURIDAD DE LA CUENTA</div>
    <h1>Conexiones</h1>
    <p>Vincula un proveedor para volver a ingresar a esta cuenta con él.</p>
    <div class="stack spaced">
        @foreach (['google' => 'Google', 'apple' => 'Apple'] as $provider => $label)
            @if (config("services.$provider.enabled"))
                @if (auth()->user()->socialAccounts->contains('provider', $provider))
                    <p>{{ $label }} está vinculado.</p>
                @else
                    <form method="post" action="{{ route('oauth.link', $provider) }}">
                        @csrf
                        <button class="button secondary">Vincular {{ $label }}</button>
                    </form>
                @endif
            @endif
        @endforeach
        @if (!config('services.google.enabled') && !config('services.apple.enabled'))
            <p>El acceso con proveedores externos aún no está configurado.</p>
        @endif
    </div>
</section>
@endsection
