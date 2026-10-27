<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\CajaTurno;
use App\Models\CarritoItem;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\MetodoPago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\ReservationService;
use App\Support\Money;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RestaurantTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $rol = 'cliente'): User
    {
        $u = User::factory()->create(['rol' => $rol]);
        if ($rol === 'cliente') {
            Cliente::create(['tipo_documento' => 'CC', 'numero_documento' => 'T'.Str::upper(Str::random(12)), 'nombre' => $u->name, 'apellido' => 'Prueba', 'email' => $u->email, 'telefono' => '3000000000', 'user_id' => $u->id]);
        }

        return $u;
    }

    private function product(array $more = []): Producto
    {
        $c = Categoria::firstOrCreate(['nombre' => 'Pruebas'], ['activa' => true]);

        return Producto::create(array_merge(['categoria_id' => $c->id, 'nombre' => 'Producto de prueba', 'descripcion' => 'Descripción del producto.', 'precio_centavos' => 4200000, 'stock' => 10, 'activo' => true], $more));
    }

    private function mesa(int $capacity = 4): Mesa
    {
        return Mesa::create(['numero' => Mesa::count() + 1, 'capacidad' => $capacity, 'ubicacion' => 'Salón', 'habilitada' => true]);
    }

    private function data(User $u, int $quantity = 1): array
    {
        $p = $this->product();
        app(CartService::class)->add($u, $p->id, $quantity);
        $s = app(CartService::class)->summary($u);

        return ['token' => (string) Str::uuid(), 'huella' => $s['huella'], 'metodo_pago_id' => MetodoPago::where('codigo', 'efectivo')->firstOrFail()->id];
    }

    private function order(User $u): Pedido
    {
        return app(OrderService::class)->checkout($u, $this->data($u));
    }

    private function booking(User $u, Mesa $m, array $more = []): array
    {
        return array_merge(['fecha' => now()->addDay()->toDateString(), 'hora' => '13:00', 'duracion' => 90, 'comensales' => 2, 'mesa_id' => $m->id], $more);
    }

    public function test_public_menu_detail_and_information_are_available(): void
    {
        $p = $this->product();
        $this->get('/menu')->assertOk()->assertSee('Producto de prueba');
        $this->get('/menu/'.$p->id)->assertOk()->assertSee('Descripción del producto.');
        $this->get('/restaurante')->assertOk()->assertSee('Pasto, Nariño');
    }

    public function test_registration_creates_customer_profile_and_never_allows_role_escalation(): void
    {
        $d = ['nombre' => 'Ana', 'apellido' => 'Registro', 'tipo_documento' => 'CC', 'numero_documento' => 'REG12345', 'telefono' => '3000000000', 'email' => 'new@example.test', 'password' => 'MiClave2026!', 'password_confirmation' => 'MiClave2026!', 'rol' => 'administrador', 'activo' => false];
        $this->postJson('/registro', $d)->assertCreated();
        $u = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertSame('cliente', $u->rol);
        $this->assertTrue($u->activo);
        $this->assertSame($u->id, Cliente::firstOrFail()->user_id);
        $this->assertAuthenticatedAs($u);
    }

    public function test_registration_rejects_duplicates_and_invalid_fields(): void
    {
        $u = $this->user();
        $this->postJson('/registro', ['email' => $u->email, 'password' => 'abc'])->assertUnprocessable()->assertJsonValidationErrors(['nombre', 'apellido', 'numero_documento', 'telefono', 'password', 'email']);
    }

    public function test_login_and_logout_json_reuse_session_authentication(): void
    {
        $u = $this->user();
        $this->postJson('/login', ['email' => $u->email, 'password' => 'Embajada2026!'])->assertOk()->assertJsonPath('user.id', $u->id)->assertJsonMissingPath('user.password');
        $this->postJson('/logout')->assertOk();
        $this->assertGuest();
    }

    public function test_menu_category_filter_and_hidden_products(): void
    {
        $p = $this->product();
        $other = Categoria::create(['nombre' => 'Otra', 'activa' => true]);
        $this->product(['nombre' => 'No coincidente', 'categoria_id' => $other->id]);
        $this->getJson('/menu?categoria='.$p->categoria_id)->assertOk()->assertJsonCount(1, 'productos.data');
        $p->categoria->update(['activa' => false]);
        $this->get('/menu/'.$p->id)->assertNotFound();
        $this->getJson('/menu')->assertJsonCount(1, 'productos.data');
    }

    public function test_admin_product_lifecycle_and_audit(): void
    {
        $admin = $this->user('administrador');
        $c = Categoria::create(['nombre' => 'Entradas', 'activa' => true]);
        $this->actingAs($admin)->postJson('/productos', ['nombre' => 'Nuevo plato', 'descripcion' => 'Una descripción.', 'categoria_id' => $c->id, 'precio' => '19999.99', 'stock' => 5, 'activo' => 1])->assertCreated();
        $p = Producto::firstOrFail();
        $this->assertSame(1999999, $p->precio_centavos);
        $this->patchJson('/productos/'.$p->id.'/activo', ['activo' => 0, 'motivo' => 'Agotado temporalmente'])->assertOk();
        $this->get('/menu/'.$p->id)->assertNotFound();
        $this->assertDatabaseHas('auditorias', ['accion' => 'producto_activacion', 'entidad_id' => $p->id]);
        $this->putJson('/productos/'.$p->id, ['nombre' => 'Plato editado', 'descripcion' => 'Actualizado.', 'categoria_id' => $c->id, 'precio' => '25000', 'stock' => 8, 'activo' => 1])->assertOk();
        $this->assertDatabaseHas('productos', ['nombre' => 'Plato editado', 'precio_centavos' => 2500000]);
    }

    public function test_products_validate_prices_and_stock(): void
    {
        $c = Categoria::create(['nombre' => 'Uno', 'activa' => true]);
        $this->actingAs($this->user('administrador'))->postJson('/productos', ['nombre' => 'Producto', 'descripcion' => 'Texto', 'categoria_id' => $c->id, 'precio' => '0', 'stock' => -2, 'activo' => 1])->assertUnprocessable();
        $this->assertDatabaseCount('productos', 0);
        $this->postJson('/productos', ['nombre' => 'Producto', 'descripcion' => 'Texto', 'categoria_id' => $c->id, 'precio' => '-5', 'activo' => 1])->assertUnprocessable()->assertJsonValidationErrors('precio');
    }

    public function test_categories_are_editable_and_filter_visibility(): void
    {
        $this->actingAs($this->user('administrador'))->postJson('/categorias', ['nombre' => 'Categoría nueva'])->assertCreated();
        $c = Categoria::firstOrFail();
        $this->putJson('/categorias/'.$c->id, ['nombre' => 'Categoría editada', 'activa' => 0])->assertOk();
        $this->getJson('/menu')->assertJsonCount(0, 'categorias');
    }

    public static function restricted(): array
    {
        return array_map(fn ($path) => [$path], ['/productos', '/productos/create', '/categorias', '/usuarios', '/caja', '/cocina', '/reportes', '/auditoria', '/configuracion']);
    }

    #[DataProvider('restricted')]
    public function test_customer_cannot_access_staff_or_admin_routes(string $path): void
    {
        $this->actingAs($this->user())->getJson($path)->assertForbidden();
    }

    public function test_worker_cannot_mutate_admin_resources(): void
    {
        $p = $this->product();
        $this->actingAs($this->user('trabajador'))->patchJson('/productos/'.$p->id.'/activo', ['activo' => 0, 'motivo' => 'Cierre temporal'])->assertForbidden();
        $this->postJson('/caja/abrir', ['inicial' => '0'])->assertForbidden();
        $this->get('/reportes')->assertForbidden();
        $this->get('/auditoria')->assertForbidden();
        $this->assertTrue($p->fresh()->activo);
    }

    public function test_cart_add_update_delete_and_persistence(): void
    {
        $u = $this->user();
        $p = $this->product();
        $this->actingAs($u)->postJson('/carrito', ['producto_id' => $p->id, 'cantidad' => 2])->assertCreated();
        $this->postJson('/carrito', ['producto_id' => $p->id, 'cantidad' => 1])->assertCreated();
        $i = CarritoItem::firstOrFail();
        $this->assertSame(3, $i->cantidad);
        $this->getJson('/carrito')->assertJsonPath('total', 12600000);
        $this->putJson('/carrito/'.$i->id, ['cantidad' => 4])->assertOk();
        $this->deleteJson('/carrito/'.$i->id)->assertOk();
        $this->assertDatabaseCount('carrito_items', 0);
    }

    public static function badQuantities(): array
    {
        return [[0], [-1], [100], ['1.5'], ['abc']];
    }

    #[DataProvider('badQuantities')]
    public function test_cart_rejects_invalid_quantities(mixed $n): void
    {
        $p = $this->product();
        $this->actingAs($this->user())->postJson('/carrito', ['producto_id' => $p->id, 'cantidad' => $n])->assertUnprocessable()->assertJsonValidationErrors('cantidad');
    }

    public function test_cart_ownership_and_unavailable_stock(): void
    {
        $u = $this->user();
        $other = $this->user();
        $p = $this->product(['stock' => 1]);
        app(CartService::class)->add($other, $p->id, 1);
        $i = CarritoItem::firstOrFail();
        $this->actingAs($u)->putJson('/carrito/'.$i->id, ['cantidad' => 1])->assertForbidden();
        $this->deleteJson('/carrito/'.$i->id)->assertForbidden();
        $this->postJson('/carrito', ['producto_id' => $p->id, 'cantidad' => 2])->assertUnprocessable();
        $p->update(['activo' => false]);
        $this->postJson('/carrito', ['producto_id' => $p->id, 'cantidad' => 1])->assertUnprocessable();
    }

    public function test_checkout_snapshots_prices_decrements_stock_and_is_idempotent(): void
    {
        $u = $this->user();
        $d = $this->data($u, 2);
        $this->actingAs($u)->postJson('/pedidos', [...$d, 'total_centavos' => 1, 'user_id' => 999])->assertCreated();
        $p = Pedido::firstOrFail();
        $this->assertSame(8400000, $p->total_centavos);
        $this->assertSame($u->id, $p->user_id);
        $this->assertDatabaseHas('productos', ['stock' => 8]);
        $this->assertDatabaseCount('carrito_items', 0);
        $this->postJson('/pedidos', $d)->assertCreated();
        $this->assertDatabaseCount('pedidos', 1);
        $product = Producto::firstOrFail();
        $product->update(['precio_centavos' => 1, 'nombre' => 'Modificado']);
        $this->assertDatabaseHas('pedido_items', ['nombre' => 'Producto de prueba', 'precio_centavos' => 4200000]);
    }

    public function test_checkout_rejects_stale_cart_and_empty_cart(): void
    {
        $u = $this->user();
        $d = $this->data($u);
        Producto::firstOrFail()->update(['precio_centavos' => 500]);
        $this->actingAs($u)->postJson('/pedidos', $d)->assertUnprocessable();
        $this->assertDatabaseCount('pedidos', 0);
        CarritoItem::query()->delete();
        $this->postJson('/pedidos', $d)->assertUnprocessable();
    }

    public function test_two_customers_cannot_oversell_last_unit(): void
    {
        $u = $this->user();
        $v = $this->user();
        $p = $this->product(['stock' => 1]);
        app(CartService::class)->add($u, $p->id, 1);
        app(CartService::class)->add($v, $p->id, 1);
        foreach ([$u, $v] as $i => $person) {
            $d = ['token' => (string) Str::uuid(), 'huella' => app(CartService::class)->summary($person)['huella'], 'metodo_pago_id' => 1];
            $this->flushSession();
            $res = $this->actingAs($person)->postJson('/pedidos', $d);
            $i === 0 ? $res->assertCreated() : $res->assertUnprocessable();
        }$this->assertDatabaseCount('pedidos', 1);
        $this->assertSame(0, $p->fresh()->stock);
    }

    public function test_customer_only_sees_own_order_and_cannot_skip_states(): void
    {
        $u = $this->user();
        $p = $this->order($u);
        $this->actingAs($this->user())->getJson('/pedidos/'.$p->id)->assertForbidden();
        $this->getJson('/pedidos')->assertJsonCount(0, 'data');
        $this->patchJson('/pedidos/'.$p->id.'/estado', ['estado' => 'entregado', 'estado_esperado' => 'pendiente'])->assertForbidden();
    }

    public function test_staff_order_pipeline_and_history(): void
    {
        $p = $this->order($this->user());
        $this->actingAs($this->user('trabajador'));
        $this->patchJson('/pedidos/'.$p->id.'/estado', ['estado' => 'entregado', 'estado_esperado' => 'pendiente'])->assertUnprocessable();
        foreach (['pendiente' => 'preparando', 'preparando' => 'listo', 'listo' => 'entregado'] as $old => $next) {
            $this->patchJson('/pedidos/'.$p->id.'/estado', ['estado' => $next, 'estado_esperado' => $old])->assertOk();
        }$this->assertSame(4, $p->historial()->count());
        $this->assertDatabaseHas('auditorias', ['accion' => 'pedido_estado']);
    }

    public function test_cancellation_restores_stock_once_and_requires_reason(): void
    {
        $u = $this->user();
        $p = $this->order($u);
        $this->actingAs($u)->patchJson('/pedidos/'.$p->id.'/estado', ['estado' => 'anulado', 'estado_esperado' => 'pendiente'])->assertUnprocessable();
        $this->patchJson('/pedidos/'.$p->id.'/estado', ['estado' => 'anulado', 'estado_esperado' => 'pendiente', 'motivo' => 'Cambio de planes'])->assertOk();
        $this->patchJson('/pedidos/'.$p->id.'/estado', ['estado' => 'anulado', 'estado_esperado' => 'anulado', 'motivo' => 'Repetir cancelación'])->assertUnprocessable();
        $this->assertSame(10, Producto::firstOrFail()->stock);
    }

    public function test_discounts_require_admin_and_cannot_exceed_subtotal(): void
    {
        $p = $this->order($this->user());
        $this->actingAs($this->user('trabajador'))->patchJson('/pedidos/'.$p->id.'/descuento', ['monto' => '1000', 'motivo' => 'Cortesía autorizada'])->assertForbidden();
        $this->flushSession();
        $this->actingAs($this->user('administrador'))->patchJson('/pedidos/'.$p->id.'/descuento', ['monto' => '999999', 'motivo' => 'Cortesía autorizada'])->assertUnprocessable();
        $this->patchJson('/pedidos/'.$p->id.'/descuento', ['monto' => '2000', 'motivo' => 'Cortesía autorizada'])->assertOk();
        $this->assertSame(4000000, $p->fresh()->total_centavos);
    }

    public function test_reservation_overlap_capacity_and_adjacent_times(): void
    {
        $u = $this->user();
        $m = $this->mesa();
        $d = $this->booking($u, $m);
        $this->actingAs($u)->postJson('/reservas', $d)->assertCreated();
        $this->postJson('/reservas', $d)->assertUnprocessable();
        $this->postJson('/reservas', [...$d, 'hora' => '14:30'])->assertCreated();
        $this->postJson('/reservas', [...$d, 'hora' => '17:00', 'comensales' => 5])->assertUnprocessable();
        $this->assertDatabaseCount('reservas', 2);
    }

    public function test_availability_and_edit_ignore_own_reservation(): void
    {
        $u = $this->user();
        $m = $this->mesa();
        $d = $this->booking($u, $m);
        $this->actingAs($u)->postJson('/reservas', $d)->assertCreated();
        $r = Reserva::firstOrFail();
        $this->getJson('/disponibilidad?'.http_build_query($d))->assertOk()->assertJsonCount(0);
        $this->getJson('/disponibilidad?'.http_build_query([...$d, 'reserva_id' => $r->id]))->assertJsonCount(1);
        $this->putJson('/reservas/'.$r->id, [...$d, 'hora' => '15:00'])->assertOk();
    }

    public function test_reservation_ownership_cancellation_and_past_dates(): void
    {
        $u = $this->user();
        $m = $this->mesa();
        $d = $this->booking($u, $m);
        $r = app(ReservationService::class)->save($u, $d);
        $this->actingAs($this->user())->get('/reservas/'.$r->id.'/editar')->assertForbidden();
        $this->getJson('/reservas')->assertJsonCount(0, 'data');
        $this->patchJson('/reservas/'.$r->id.'/estado', ['estado' => 'cancelada', 'motivo' => 'Cambio de planes'])->assertForbidden();
        $this->flushSession();
        $this->actingAs($u)->patchJson('/reservas/'.$r->id.'/estado', ['estado' => 'cancelada', 'motivo' => 'Cambio de planes'])->assertOk();
        $this->postJson('/reservas', [...$d, 'fecha' => now()->subDay()->toDateString()])->assertUnprocessable();
    }

    public function test_disabled_table_and_customer_without_profile_cannot_book(): void
    {
        $u = $this->user();
        $m = $this->mesa();
        $m->update(['habilitada' => false]);
        $this->actingAs($u)->postJson('/reservas', $this->booking($u, $m))->assertUnprocessable();
        $u->cliente->update(['user_id' => null]);
        $m->update(['habilitada' => true]);
        $this->postJson('/reservas', $this->booking($u->fresh(), $m))->assertUnprocessable();
    }

    public function test_staff_checkin_only_inside_reserved_time(): void
    {
        $u = $this->user();
        $r = app(ReservationService::class)->save($u, $this->booking($u, $this->mesa()));
        $this->actingAs($this->user('trabajador'))->patchJson('/reservas/'.$r->id.'/estado', ['estado' => 'en_curso', 'motivo' => 'Ingreso registrado'])->assertUnprocessable();
        $this->travelTo($r->inicio->addMinute());
        $this->patchJson('/reservas/'.$r->id.'/estado', ['estado' => 'en_curso', 'motivo' => 'Ingreso registrado'])->assertOk();
        $this->patchJson('/reservas/'.$r->id.'/estado', ['estado' => 'finalizada', 'motivo' => 'Visita completada'])->assertOk();
    }

    public function test_cash_payment_close_audit_and_report_totals(): void
    {
        $p = $this->order($this->user());
        $admin = $this->user('administrador');
        $this->actingAs($admin)->postJson('/caja/abrir', ['inicial' => '100000'])->assertCreated();
        $this->postJson('/caja/abrir', ['inicial' => '100000'])->assertUnprocessable();
        $d = ['metodo_pago_id' => 1, 'total_esperado' => $p->total_centavos, 'recibido' => '50000'];
        $this->postJson('/pedidos/'.$p->id.'/pago', $d)->assertCreated();
        $this->assertDatabaseHas('pagos', ['monto_centavos' => 4200000, 'cambio_centavos' => 800000]);
        $this->postJson('/pedidos/'.$p->id.'/pago', $d)->assertUnprocessable();
        $this->patchJson('/pedidos/'.$p->id.'/estado', ['estado' => 'anulado', 'estado_esperado' => 'pendiente', 'motivo' => 'Intento posterior al pago'])->assertUnprocessable();
        $t = CajaTurno::firstOrFail();
        $this->assertSame(14200000, $t->esperado());
        $this->postJson('/caja/'.$t->id.'/cerrar', ['contado' => '141000'])->assertUnprocessable();
        $this->postJson('/caja/'.$t->id.'/cerrar', ['contado' => '142000'])->assertOk();
        $this->postJson('/caja/'.$t->id.'/cerrar', ['contado' => '142000'])->assertUnprocessable();
        $this->getJson('/reportes')->assertJsonPath('total', 4200000);
        $this->assertDatabaseHas('auditorias', ['accion' => 'caja_cerrada']);
        $this->get('/pedidos/'.$p->id.'/recibo')->assertOk()->assertSee('No es factura electrónica.');
    }

    public function test_non_cash_reference_total_and_cash_exclusion(): void
    {
        $p = $this->order($this->user());
        $this->actingAs($this->user('administrador'))->postJson('/caja/abrir', ['inicial' => '10000']);
        $d = ['metodo_pago_id' => 2, 'total_esperado' => $p->total_centavos, 'recibido' => '42000'];
        $this->postJson('/pedidos/'.$p->id.'/pago', $d)->assertUnprocessable()->assertJsonValidationErrors('referencia');
        $this->postJson('/pedidos/'.$p->id.'/pago', [...$d, 'recibido' => '50000', 'referencia' => 'REF123'])->assertUnprocessable();
        $this->postJson('/pedidos/'.$p->id.'/pago', [...$d, 'referencia' => 'REF123'])->assertCreated();
        $this->assertSame(1000000, CajaTurno::firstOrFail()->esperado());
    }

    public function test_closed_cash_underpayment_and_stale_total_are_rejected(): void
    {
        $p = $this->order($this->user());
        $this->actingAs($this->user('administrador'));
        $d = ['metodo_pago_id' => 1, 'total_esperado' => $p->total_centavos, 'recibido' => '42000'];
        $this->postJson('/pedidos/'.$p->id.'/pago', $d)->assertUnprocessable();
        $this->postJson('/caja/abrir', ['inicial' => '0'])->assertCreated();
        $this->postJson('/pedidos/'.$p->id.'/pago', [...$d, 'recibido' => '1'])->assertUnprocessable();
        $this->postJson('/pedidos/'.$p->id.'/pago', [...$d, 'total_esperado' => 1])->assertUnprocessable();
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_restaurant_configuration_and_report_csv_validation(): void
    {
        $this->actingAs($this->user('administrador'))->putJson('/configuracion', ['nombre' => 'La Embajada Paisa', 'ciudad' => 'Pasto', 'direccion' => 'Dirección de prueba', 'telefono' => '3000000000', 'horario' => 'Horario de prueba', 'latitud' => '1.2', 'longitud' => '-77.2'])->assertOk();
        $this->get('/restaurante')->assertSee('Dirección de prueba')->assertSee('Ver ubicación en el mapa');
        $this->getJson('/reportes?desde=2026-02-01&hasta=2025-01-01')->assertUnprocessable();
        $this->get('/reportes/ventas.csv')->assertOk()->assertDownload('ventas_embajada.csv');
    }

    public function test_audit_never_contains_passwords_or_hashes(): void
    {
        $u = $this->user();
        $u->update(['password' => 'SuperSecreto2026!']);
        $encoded = Auditoria::all()->toJson();
        $this->assertStringNotContainsString('SuperSecreto', $encoded);
        $this->assertStringNotContainsString('$2y$', $encoded);
        $this->assertStringNotContainsString('remember_token', $encoded);
    }

    public function test_original_table_and_customer_history_are_protected(): void
    {
        $u = $this->user();
        $m = $this->mesa();
        app(ReservationService::class)->save($u, $this->booking($u, $m));
        $this->actingAs($this->user('administrador'))->deleteJson('/mesas/'.$m->id)->assertUnprocessable();
        $this->deleteJson('/clientes/'.$u->cliente->id)->assertUnprocessable();
    }

    public function test_all_admin_screens_render_with_live_fixtures(): void
    {
        $this->seed(DemoSeeder::class);
        $u = $this->user();
        $p = $this->order($u);
        $r = app(ReservationService::class)->save($u, $this->booking($u, $this->mesa()));
        $this->actingAs($this->user('administrador'));
        foreach (['/inicio', '/menu', '/menu/1', '/carrito', '/productos', '/productos/create', '/productos/1/edit', '/categorias', '/pedidos', '/pedidos/'.$p->id, '/cocina', '/reservas', '/reservas/crear', '/reservas/'.$r->id.'/editar', '/caja', '/reportes', '/auditoria', '/restaurante', '/configuracion', '/clientes/'.$u->cliente->id.'/edit'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_money_uses_integer_cents(): void
    {
        $this->assertSame(1999999, Money::parse('19999.99'));
        $this->assertSame(123450, Money::parse('1234,50'));
    }

    public function test_staff_can_book_for_a_customer_and_customer_can_see_it(): void
    {
        $customer = $this->user();
        $staff = $this->user('trabajador');
        $d = $this->booking($customer, $this->mesa(), ['cliente_id' => $customer->cliente->id]);
        $this->actingAs($staff)->postJson('/reservas', $d)->assertCreated();
        $reservation = Reserva::firstOrFail();
        $this->flushSession();
        $this->actingAs($customer)->getJson('/reservas')->assertOk()->assertJsonCount(1, 'data');
        $this->patchJson('/reservas/'.$reservation->id.'/estado', ['estado' => 'cancelada', 'motivo' => 'Cambio de planes'])->assertOk();
    }

    public function test_admin_history_route_and_busy_table_protection(): void
    {
        $this->actingAs($this->user('trabajador'))->get('/historial-pedidos')->assertForbidden();
        $this->flushSession();
        $admin = $this->user('administrador');
        $customer = $this->user();
        $mesa = $this->mesa();
        app(ReservationService::class)->save($customer, $this->booking($customer, $mesa));
        $this->actingAs($admin)->get('/historial-pedidos')->assertOk();
        $this->putJson('/mesas/'.$mesa->id, ['numero' => $mesa->numero, 'capacidad' => 4, 'ubicacion' => 'Salón', 'habilitada' => 0])->assertUnprocessable();
        $this->assertTrue($mesa->fresh()->habilitada);
    }

    public function test_table_capacity_changes_preserve_existing_reservations(): void
    {
        $customer = $this->user();
        $mesa = $this->mesa(4);
        app(ReservationService::class)->save($customer, $this->booking($customer, $mesa, ['comensales' => 4]));
        $this->actingAs($this->user('administrador'));
        $data = ['numero' => $mesa->numero, 'capacidad' => 2, 'ubicacion' => 'Salón', 'habilitada' => 1];
        $this->putJson('/mesas/'.$mesa->id, $data)->assertUnprocessable()->assertJsonValidationErrors('capacidad');
        $this->assertSame(4, $mesa->fresh()->capacidad);
        $this->putJson('/mesas/'.$mesa->id, [...$data, 'capacidad' => 6])->assertRedirect();
        $this->assertSame(6, $mesa->fresh()->capacidad);
    }
}
