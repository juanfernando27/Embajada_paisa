<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware(['auth', 'activo', 'auth.session'])->group(function (): void {
    Route::get('/inicio', DashboardController::class)->name('dashboard');
    Route::get('/mi-perfil', [ClienteController::class, 'profile'])->middleware('rol:cliente')->name('perfil');
    Route::middleware('rol:administrador,trabajador')->group(function (): void {
        // Se conserva el CRUD y se añade edición de fichas.
        Route::resource('clientes', ClienteController::class)->except(['destroy']);
        Route::get('/mesas', [MesaController::class, 'index'])->name('mesas.index');
        Route::get('/mesas/{mesa}', [MesaController::class, 'show'])->whereNumber('mesa')->name('mesas.show');
    });
    Route::middleware('rol:administrador')->group(function (): void {
        Route::get('/mesas/crear', [MesaController::class, 'create'])->name('mesas.create');
        Route::post('/mesas', [MesaController::class, 'store'])->name('mesas.store');
        Route::get('/mesas/{mesa}/editar', [MesaController::class, 'edit'])->name('mesas.edit');
        Route::put('/mesas/{mesa}', [MesaController::class, 'update'])->name('mesas.update');
        Route::delete('/mesas/{mesa}', [MesaController::class, 'destroy'])->name('mesas.destroy');
        Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy']);
    });
});

// Ampliación RF-01 a RF-22. HTML y JSON comparten controladores, permisos y CSRF.
Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
Route::get('/menu/{producto}', [MenuController::class, 'show'])->name('menu.show');
Route::get('/restaurante', [RestaurantController::class, 'show'])->name('restaurante.show');
Route::middleware('guest')->group(function () {
    Route::get('/registro', [RegistrationController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistrationController::class, 'store'])->name('registro.store');
});
Route::middleware(['auth', 'activo', 'auth.session'])->group(function () {
    Route::get('/carrito', [CartController::class, 'index'])->name('carrito.index');
    Route::post('/carrito', [CartController::class, 'store'])->name('carrito.store');
    Route::put('/carrito/{item}', [CartController::class, 'update'])->name('carrito.update');
    Route::delete('/carrito/{item}', [CartController::class, 'destroy'])->name('carrito.destroy');
    Route::get('/pedidos', [OrderController::class, 'index'])->name('pedidos.index');
    Route::post('/pedidos', [OrderController::class, 'store'])->name('pedidos.store');
    Route::get('/pedidos/{pedido}', [OrderController::class, 'show'])->name('pedidos.show');
    Route::patch('/pedidos/{pedido}/estado', [OrderController::class, 'state'])->name('pedidos.state');
    Route::get('/pedidos/{pedido}/recibo', [OrderController::class, 'receipt'])->name('pedidos.receipt');
    Route::get('/reservas', [ReservationController::class, 'index'])->name('reservas.index');
    Route::get('/reservas/crear', [ReservationController::class, 'create'])->name('reservas.create');
    Route::get('/disponibilidad', [ReservationController::class, 'available'])->name('reservas.available');
    Route::post('/reservas', [ReservationController::class, 'store'])->name('reservas.store');
    Route::get('/reservas/{reserva}/editar', [ReservationController::class, 'edit'])->name('reservas.edit');
    Route::put('/reservas/{reserva}', [ReservationController::class, 'update'])->name('reservas.update');
    Route::patch('/reservas/{reserva}/estado', [ReservationController::class, 'state'])->name('reservas.state');
    Route::middleware('rol:administrador,trabajador')->group(function () {
        Route::get('/cocina', [OrderController::class, 'kitchen'])->name('cocina');
        Route::get('/caja', [CashController::class, 'index'])->name('caja.index');
        Route::post('/pedidos/{pedido}/pago', [CashController::class, 'pay'])->name('caja.pay');
    });
    Route::middleware('rol:administrador')->group(function () {
        Route::get('/historial-pedidos', [OrderController::class, 'index'])->name('historial');
        Route::resource('productos', ProductController::class)->except(['show', 'destroy']);
        Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
        Route::patch('/productos/{producto}/activo', [ProductController::class, 'toggle'])->name('productos.toggle');
        Route::get('/categorias', [ProductController::class, 'categories'])->name('categorias.index');
        Route::post('/categorias', [ProductController::class, 'categoryStore'])->name('categorias.store');
        Route::put('/categorias/{categoria}', [ProductController::class, 'categoryUpdate'])->name('categorias.update');
        Route::post('/caja/abrir', [CashController::class, 'open'])->name('caja.open');
        Route::post('/caja/{turno}/cerrar', [CashController::class, 'close'])->name('caja.close');
        Route::patch('/pedidos/{pedido}/descuento', [OrderController::class, 'discount'])->name('pedidos.discount');
        Route::get('/reportes', [ReportController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/ventas.csv', [ReportController::class, 'csv'])->name('reportes.csv');
        Route::get('/auditoria', [ReportController::class, 'audit'])->name('auditoria');
        Route::get('/configuracion', [RestaurantController::class, 'edit'])->name('restaurante.edit');
        Route::put('/configuracion', [RestaurantController::class, 'update'])->name('restaurante.update');
    });
});

// Entrega pública y acotada de fotografías; funciona también sin symlinks en Windows.
Route::get('/imagenes-productos/{archivo}', function (string $archivo) {
    $disk = Storage::disk('public');
    $path = 'productos/'.$archivo;
    abort_unless($disk->exists($path), 404);

    return $disk->response($path, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=86400']);
})->where('archivo', '[A-Za-z0-9_-]+\\.(?:jpg|jpeg|png|webp)')->name('productos.imagen');
