@props(['seed'=>1, 'imagen'=>null, 'nombre'=>null, 'categoria'=>null])
@if($imagen)
<div {{ $attributes->merge(['class'=>'product-art']) }} aria-hidden="true"><img src="{{ route('productos.imagen', ['archivo'=>basename($imagen)]) }}" alt="" loading="lazy"></div>
@elseif(in_array($nombre, ['Bandeja paisa', 'Empanadas de la casa'], true))
<div {{ $attributes->merge(['class'=>'product-art product-art-photo']) }} aria-hidden="true"><img src="{{ asset($nombre==='Bandeja paisa'?'images/bandeja-paisa.png':'images/empanadas.png') }}" alt="" loading="lazy"><span class="photo-caption">Imagen ilustrativa</span></div>
@elseif(in_array($categoria, ['Bebidas', 'Postres', 'Entradas'], true))
<div {{ $attributes->merge(['class'=>'product-art category-art-panel variant-'.($seed%3)]) }} aria-hidden="true"><div class="category-orbit"><x-icon :name="match($categoria){'Bebidas'=>'coffee','Postres'=>'dessert',default=>'bag'}" size="75"/></div><span class="category-flourish">✦</span></div>
@else
<div {{ $attributes->merge(['class'=>'product-art variant-'.($seed%3)]) }} aria-hidden="true"><svg viewBox="0 0 240 160"><ellipse cx="120" cy="116" rx="72" ry="8" fill="currentColor" opacity=".08"/><ellipse cx="120" cy="83" rx="74" ry="51" fill="var(--plate)" stroke="var(--color-accent)"/><ellipse cx="120" cy="83" rx="59" ry="38" fill="none" stroke="var(--color-accent)" opacity=".4"/><path d="M83 85c-6-19 6-31 20-28 18-9 34 0 36 13 20 3 25 17 8 28-23 12-52 9-64-13Z" fill="var(--color-secondary)"/><path d="M96 71c14-4 23-1 29 5M92 84c15-2 26 1 33 6" stroke="var(--color-accent)" stroke-width="3" fill="none" stroke-linecap="round"/><circle cx="146" cy="78" r="12" fill="var(--color-accent)"/><circle cx="146" cy="78" r="6" fill="none" stroke="var(--plate)"/><path d="M25 45v65m-5-65v15c0 7 10 7 10 0V45m183 0v65m0-65c-7 5-7 24 0 26" stroke="currentColor" fill="none" opacity=".4" stroke-width="2"/><path d="m194 23 2 5 5 2-5 2-2 5-2-5-5-2 5-2Z" fill="var(--color-accent)"/></svg></div>

@endif
