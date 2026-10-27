@props(['name', 'size' => 20])
@php
$paths = [
 'camera'=>'<rect x="3" y="6" width="18" height="15" rx="3"/><path d="m8 6 2-3h4l2 3"/><circle cx="12" cy="13" r="4"/>',
 'upload'=>'<path d="M12 16V3m-5 5 5-5 5 5M4 16v5h16v-5"/>',
 'coffee'=>'<path d="M4 8h13v7a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5ZM17 9h2a3 3 0 0 1 0 6h-2M6 3v2m5-2v2m5-2v2M2 23h18"/>',
 'dessert'=>'<path d="M3 15h18v6H3ZM3 15l9-10 9 10M7 11h10M12 5V2"/><circle cx="12" cy="2" r="1"/>',
 'sparkles'=>'<path d="m12 3 2 6 6 3-6 2-2 7-2-7-7-2 7-3ZM20 2v4m-2-2h4"/>',

 'menu'=>'<path d="M4 4v7m4-7v7M2 4v5a4 4 0 0 0 8 0V4M6 13v8M19 3v18M19 3c-6 6-6 13 0 12"/>',
 'bag'=>'<path d="M4 7h16l1 14H3Z"/><path d="M8 7V5a4 4 0 0 1 8 0v2"/>',
 'receipt'=>'<path d="M5 3h14v18l-3-2-4 2-4-2-3 2Z"/><path d="M9 7h6M9 11h6m-6 4h3"/>',
 'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18m-14 4h3m4 0h3"/>',
 'cook'=>'<path d="M6 13a5 5 0 0 1 0-10c2-3 8-3 10 0a5 5 0 0 1 0 10v8H6Z"/><path d="M6 17h10m-6-8v4m3-4v4"/>',
 'cash'=>'<rect x="3" y="8" width="18" height="13" rx="2"/><path d="M6 8V3h12v5M3 16h18m-13-5h8"/>',
 'chart'=>'<path d="M3 3v18h18M7 16v-5m5 5V7m5 9V4"/>',
 'grid'=>'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
 'settings'=>'<path d="M3 6h18M3 12h18M3 18h18"/><circle cx="8" cy="6" r="2"/><circle cx="16" cy="12" r="2"/><circle cx="9" cy="18" r="2"/>',

 'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Z"/><path d="M9 21v-8h6v8"/>',
 'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6M21 21v-3a6 6 0 0 0-3-5"/>',
 'table' => '<rect x="4" y="6" width="16" height="11" rx="3"/><path d="M8 3v3m8-3v3M8 17v4m8-4v4M1 10h3m16 0h3"/>',
 'shield' => '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6Z"/><path d="m8 12 3 3 5-6"/>',
 'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
 'plus' => '<path d="M12 5v14M5 12h14"/>',
 'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
 'back' => '<path d="M20 12H4m6-6-6 6 6 6"/>',
 'logout' => '<path d="M9 4H4v16h5m6-13 5 5-5 5M8 12h12"/>',
 'edit' => '<path d="m15 4 5 5M4 20l5-1L21 7a2 2 0 0 0-4-4L5 15Z"/>',
 'check' => '<path d="m5 12 4 4L19 6"/>',
 'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>',
 'phone' => '<path d="m5 3 4 5-2 3a16 16 0 0 0 6 6l3-2 5 4-2 3C9 22 2 15 2 5Z"/>',
 'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
 'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
 'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2"/>',
 'trash' => '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/>',
 'seats' => '<circle cx="12" cy="6" r="3"/><path d="M7 21v-4a5 5 0 0 1 10 0v4M4 21h16"/>',
];
@endphp
<svg {{ $attributes->merge(['class' => 'icon']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['check'] !!}</svg>
