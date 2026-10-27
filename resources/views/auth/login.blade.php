<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Ingresar · La Embajada Paisa</title><link rel="icon" href="{{ asset('marca.svg') }}"><link rel="stylesheet" href="{{ asset('css/app.css') }}"><script src="{{ asset('js/app.js') }}" defer></script></head>
<body class="login-body"><a class="skip" href="#ingreso">Saltar al formulario</a>
 <main class="login-shell">
  <section class="login-brand"><div class="brand"><span class="brand-symbol">ep<span>✦</span></span><span>LA EMBAJADA<small>PAISA · RESTAURANTE</small></span></div>
   <div class="login-story"><div class="eyebrow">BIENVENIDO A NUESTRA MESA</div><h1>El buen servicio<br>empieza con<br><em>todo en orden.</em></h1><p>Un solo lugar para cuidar de tus clientes,<br>tu equipo y cada mesa del restaurante.</p>
    <figure class="login-food"><img src="{{ asset('images/empanadas.png') }}" alt="Presentación ilustrativa de empanadas colombianas"><figcaption><x-icon name="sparkles" size="16"/> El gusto de compartir.</figcaption></figure>
   </div><small class="login-location">PASTO, NARIÑO <span>✦</span> EL SABOR DE CASA</small>
  </section>
  <section class="login-form-side" id="ingreso"><div class="login-form-wrap"><span class="label-pill"><x-icon name="lock" size="15"/> Acceso al sistema</span><h2>Qué gusto verte.</h2><p class="muted">Ingresa con tu cuenta para continuar.</p><x-alerts/>
   <form method="post" action="{{ route('login.store') }}" class="stack">@csrf
    <x-field name="email" label="Correo electrónico" type="email" :required="true" maxlength="150" autocomplete="username" placeholder="tu@correo.com" autofocus/>
    <div class="password-wrap"><x-field name="password" label="Contraseña" type="password" :required="true" maxlength="255" autocomplete="current-password"/><button type="button" class="password-toggle" data-password="password" aria-label="Mostrar contraseña" aria-pressed="false"><x-icon name="eye"/></button></div>
    <button class="button full">Ingresar <x-icon name="arrow" size="18"/></button>
   </form><p class="login-help"><a class="text-link" href="{{ route('registro') }}">Crear una cuenta de cliente</a><br><a class="text-link" href="{{ route('menu.index') }}">Ver nuestra carta</a><br><br>¿Necesitas una cuenta o recuperar tu acceso?<br>Comunícate con el administrador del restaurante.</p>
   <div class="login-fine"><x-icon name="shield" size="16"/> Tu acceso es personal. Cuida tus credenciales.</div>
  </div></section>
 </main>
</body></html>
