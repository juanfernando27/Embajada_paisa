<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SprintOneTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $rol = 'administrador'): User
    {
        return User::factory()->create(['rol' => $rol]);
    }

    private function clientData(array $overrides = []): array
    {
        return array_replace(['tipo_documento' => 'CC', 'numero_documento' => 'DEMO9001', 'nombre' => 'Ana', 'apellido' => 'Prueba', 'email' => 'ana@example.test', 'telefono' => '300 000 0000'], $overrides);
    }

    private function client(array $overrides = []): Cliente
    {
        return Cliente::create($this->clientData($overrides));
    }

    private function tableData(array $overrides = []): array
    {
        return array_replace(['numero' => 1, 'capacidad' => 4, 'ubicacion' => 'Salón principal', 'habilitada' => 1, 'descripcion' => 'Cerca de la ventana'], $overrides);
    }

    private function accountData(array $overrides = []): array
    {
        return array_replace(['name' => 'Cuenta Prueba', 'email' => 'cuenta@example.test', 'rol' => 'trabajador', 'activo' => 1, 'password' => 'ClaveNueva2026!', 'password_confirmation' => 'ClaveNueva2026!'], $overrides);
    }

    public function test_login_page_and_root_redirect(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Qué gusto verte.');
    }

    public static function protectedPages(): array
    {
        return array_map(fn ($path) => [$path], ['/inicio', '/clientes', '/clientes/create', '/mesas', '/mesas/crear', '/usuarios', '/usuarios/create', '/mi-perfil']);
    }

    #[DataProvider('protectedPages')]
    public function test_guest_cannot_access_protected_pages(string $path): void
    {
        $this->get($path)->assertRedirect('/login');
    }

    public function test_active_user_can_login_with_normalized_email(): void
    {
        $user = $this->user();
        $this->post('/login', ['email' => strtoupper($user->email), 'password' => 'Embajada2026!'])->assertRedirect('/inicio');
        $this->assertAuthenticatedAs($user);
        $this->get('/inicio')->assertOk();
    }

    public function test_invalid_login_is_rejected(): void
    {
        $user = $this->user();
        $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create(['activo' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'Embajada2026!'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_limited_after_five_failures(): void
    {
        $user = $this->user();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta']);
        }
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'Embajada2026!']);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_logout_removes_access(): void
    {
        $this->actingAs($this->user())->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/inicio')->assertRedirect('/login');
    }

    public function test_deactivation_revokes_an_existing_session(): void
    {
        $user = $this->user('trabajador');
        $this->actingAs($user)->get('/inicio')->assertOk();
        $user->update(['activo' => false]);
        $this->get('/clientes')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_can_open_all_main_forms(): void
    {
        $this->actingAs($this->user());
        foreach (['/inicio', '/clientes', '/clientes/create', '/mesas', '/mesas/crear', '/usuarios', '/usuarios/create'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_worker_can_read_clients_and_tables_but_not_users(): void
    {
        $this->actingAs($this->user('trabajador'));
        foreach (['/inicio', '/clientes', '/clientes/create', '/mesas'] as $path) {
            $this->get($path)->assertOk();
        }
        foreach (['/usuarios', '/usuarios/create', '/mesas/crear', '/mi-perfil'] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    public function test_client_is_restricted_to_own_profile(): void
    {
        $user = $this->user('cliente');
        $own = $this->client(['user_id' => $user->id]);
        $other = $this->client(['numero_documento' => 'OTRO0001', 'nombre' => 'OtroNombreSecreto', 'email' => 'otro@example.test']);
        $this->actingAs($user)->get('/mi-perfil')->assertOk()->assertSee($own->nombre_completo)->assertDontSee('OtroNombreSecreto');
        $this->get('/inicio')->assertOk()->assertDontSee('OtroNombreSecreto');
        foreach (['/clientes', '/clientes/'.$other->id, '/mesas', '/usuarios'] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    public function test_client_without_association_gets_clear_empty_state(): void
    {
        $this->actingAs($this->user('cliente'))->get('/mi-perfil')->assertOk()->assertSee('Tu perfil está por asociar');
    }

    public function test_worker_cannot_mutate_tables_or_users_by_direct_request(): void
    {
        $mesa = Mesa::create($this->tableData());
        $target = $this->user('cliente');
        $this->actingAs($this->user('trabajador'));
        $this->post('/mesas', $this->tableData(['numero' => 2]))->assertForbidden();
        $this->put('/mesas/'.$mesa->id, $this->tableData(['capacidad' => 10]))->assertForbidden();
        $this->delete('/mesas/'.$mesa->id)->assertForbidden();
        $this->post('/usuarios', $this->accountData())->assertForbidden();
        $this->put('/usuarios/'.$target->id, $this->accountData(['rol' => 'administrador']))->assertForbidden();
        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'capacidad' => 4]);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'rol' => 'cliente']);
    }

    public function test_client_cannot_post_new_clients_or_users(): void
    {
        $this->actingAs($this->user('cliente'))->post('/clientes', $this->clientData())->assertForbidden();
        $this->post('/usuarios', $this->accountData())->assertForbidden();
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_worker_registers_client_with_validated_fields_only(): void
    {
        $worker = $this->user('trabajador');
        $this->actingAs($worker)->post('/clientes', $this->clientData(['email' => 'ANA@EXAMPLE.TEST', 'numero_documento' => 'demo9001', 'user_id' => $worker->id]))->assertRedirect();
        $this->assertDatabaseHas('clientes', ['email' => 'ana@example.test', 'numero_documento' => 'DEMO9001', 'user_id' => null]);
    }

    public function test_client_document_and_email_must_be_unique(): void
    {
        $this->client();
        $this->actingAs($this->user());
        $this->post('/clientes', $this->clientData())->assertSessionHasErrors(['numero_documento', 'email']);
        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_client_contact_and_required_fields_are_validated(): void
    {
        $this->actingAs($this->user())->post('/clientes', $this->clientData(['nombre' => '', 'telefono' => 'abc', 'email' => 'correo-invalido', 'tipo_documento' => 'NIT', 'numero_documento' => '!']))->assertSessionHasErrors(['nombre', 'telefono', 'email', 'tipo_documento', 'numero_documento']);
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_client_email_is_optional(): void
    {
        $this->actingAs($this->user())->post('/clientes', $this->clientData(['email' => '']))->assertRedirect();
        $this->assertDatabaseHas('clientes', ['email' => null]);
    }

    public function test_client_search_filters_and_preserves_query_on_pagination(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs($this->user());
        $this->get('/clientes?buscar=DEMO1001')->assertOk()->assertSee('Camila Rojas')->assertDontSee('Andrés Molina');
        $this->get('/clientes?buscar=DEMO')->assertOk()->assertSee('buscar=DEMO', false)->assertSee('Siguiente');
        $this->get('/clientes?buscar=INEXISTENTE')->assertOk()->assertSee('No encontramos coincidencias');
    }

    public function test_client_detail_escapes_user_supplied_markup(): void
    {
        $cliente = $this->client(['nombre' => '<script>alert(1)</script>']);
        $this->actingAs($this->user())->get('/clientes/'.$cliente->id)->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_client_edit_and_delete_are_now_supported_without_history(): void
    {
        $cliente = $this->client();
        $this->actingAs($this->user());
        $this->put('/clientes/'.$cliente->id, $this->clientData())->assertRedirect();
        $this->delete('/clientes/'.$cliente->id)->assertRedirect('/clientes');
    }

    public function test_admin_can_create_update_and_delete_a_table(): void
    {
        $this->actingAs($this->user())->post('/mesas', $this->tableData())->assertRedirect();
        $mesa = Mesa::firstOrFail();
        $this->get('/mesas/'.$mesa->id.'/editar')->assertOk();
        $this->put('/mesas/'.$mesa->id, $this->tableData(['capacidad' => 6, 'habilitada' => 0]))->assertRedirect('/mesas/'.$mesa->id);
        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'capacidad' => 6, 'habilitada' => false]);
        $this->get('/mesas?estado=fuera_servicio')->assertOk()->assertSee('Mesa 1');
        $this->delete('/mesas/'.$mesa->id)->assertRedirect('/mesas');
        $this->assertDatabaseMissing('mesas', ['id' => $mesa->id]);
        $this->get('/mesas/'.$mesa->id)->assertNotFound();
    }

    public function test_table_number_is_unique_but_can_be_kept_on_edit(): void
    {
        $mesa = Mesa::create($this->tableData());
        $this->actingAs($this->user());
        $this->post('/mesas', $this->tableData())->assertSessionHasErrors('numero');
        $this->put('/mesas/'.$mesa->id, $this->tableData())->assertSessionHasNoErrors();
    }

    public function test_table_fields_reject_out_of_range_and_missing_values(): void
    {
        $this->actingAs($this->user())->post('/mesas', $this->tableData(['numero' => 0, 'capacidad' => 31, 'ubicacion' => '', 'habilitada' => 'incorrecto']))->assertSessionHasErrors(['numero', 'capacidad', 'ubicacion', 'habilitada']);
        $this->assertDatabaseCount('mesas', 0);
    }

    public function test_dashboard_capacity_counts_only_enabled_tables(): void
    {
        Mesa::create($this->tableData(['capacidad' => 7]));
        Mesa::create($this->tableData(['numero' => 2, 'capacidad' => 20, 'habilitada' => 0]));
        $this->actingAs($this->user())->get('/inicio')->assertOk()->assertViewHas('capacidad', 7);
    }

    public function test_admin_creates_account_with_hashed_password(): void
    {
        $this->actingAs($this->user())->post('/usuarios', $this->accountData())->assertRedirect('/usuarios');
        $user = User::where('email', 'cuenta@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('ClaveNueva2026!', $user->password));
        $this->assertNotEquals('ClaveNueva2026!', $user->password);
        $this->assertSame('trabajador', $user->rol);
    }

    public function test_invalid_role_and_weak_or_unconfirmed_password_are_rejected(): void
    {
        $this->actingAs($this->user())->post('/usuarios', $this->accountData(['rol' => 'superadmin', 'password' => 'abc', 'password_confirmation' => 'xyz']))->assertSessionHasErrors(['rol', 'password']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_account_email_must_be_unique_after_normalization(): void
    {
        $user = $this->user();
        $this->actingAs($user)->post('/usuarios', $this->accountData(['email' => strtoupper($user->email)]))->assertSessionHasErrors('email');
    }

    public function test_admin_can_edit_user_without_changing_password(): void
    {
        $target = $this->user('trabajador');
        $hash = $target->password;
        $this->actingAs($this->user())->put('/usuarios/'.$target->id, $this->accountData(['email' => $target->email, 'name' => 'Nuevo nombre', 'password' => '', 'password_confirmation' => '']))->assertRedirect('/usuarios');
        $target->refresh();
        $this->assertSame($hash, $target->password);
        $this->assertSame('Nuevo nombre', $target->name);
    }

    public function test_admin_can_reset_password_and_deactivate_another_user(): void
    {
        $target = $this->user('trabajador');
        $this->actingAs($this->user())->put('/usuarios/'.$target->id, $this->accountData(['email' => $target->email, 'activo' => 0]))->assertRedirect('/usuarios');
        $target->refresh();
        $this->assertFalse($target->activo);
        $this->assertTrue(Hash::check('ClaveNueva2026!', $target->password));
    }

    public function test_admin_cannot_deactivate_or_demote_their_own_account(): void
    {
        $admin = $this->user();
        $this->actingAs($admin);
        $this->put('/usuarios/'.$admin->id, $this->accountData(['email' => $admin->email, 'rol' => 'trabajador']))->assertSessionHasErrors('rol');
        $this->put('/usuarios/'.$admin->id, $this->accountData(['email' => $admin->email, 'rol' => 'administrador', 'activo' => 0]))->assertSessionHasErrors('rol');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'rol' => 'administrador', 'activo' => 1]);
    }

    public function test_admin_can_create_and_edit_a_client_account_with_profile_association(): void
    {
        $cliente = $this->client();
        $this->actingAs($this->user());
        $this->post('/usuarios', $this->accountData(['rol' => 'cliente', 'cliente_id' => $cliente->id]))->assertRedirect('/usuarios');
        $user = User::where('email', 'cuenta@example.test')->firstOrFail();
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id, 'user_id' => $user->id]);
        $this->get('/usuarios/'.$user->id.'/edit')->assertOk()->assertSee('Ana Prueba');
    }

    public function test_client_profile_cannot_be_assigned_to_two_users_and_transaction_rolls_back(): void
    {
        $existing = $this->user('cliente');
        $cliente = $this->client(['user_id' => $existing->id]);
        $this->actingAs($this->user())->post('/usuarios', $this->accountData(['rol' => 'cliente', 'cliente_id' => $cliente->id]))->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseMissing('users', ['email' => 'cuenta@example.test']);
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id, 'user_id' => $existing->id]);
    }

    public function test_non_client_role_cannot_be_associated_to_a_customer(): void
    {
        $cliente = $this->client();
        $this->actingAs($this->user())->post('/usuarios', $this->accountData(['cliente_id' => $cliente->id]))->assertSessionHasErrors('cliente_id');
    }

    public function test_changing_role_removes_client_profile_access(): void
    {
        $target = $this->user('cliente');
        $cliente = $this->client(['user_id' => $target->id]);
        $this->actingAs($this->user())->put('/usuarios/'.$target->id, $this->accountData(['email' => $target->email, 'rol' => 'trabajador']))->assertRedirect('/usuarios');
        $this->assertNull($cliente->fresh()->user_id);
    }

    public function test_demo_seed_is_repeatable_without_resetting_passwords(): void
    {
        $this->seed(DemoSeeder::class);
        $user = User::where('email', 'admin@embajada.test')->firstOrFail();
        $user->update(['password' => 'OtraClave2026!']);
        $this->seed(DemoSeeder::class);
        $this->assertDatabaseCount('users', 7);
        $this->assertDatabaseCount('clientes', 12);
        $this->assertDatabaseCount('mesas', 8);
        $this->assertTrue(Hash::check('OtraClave2026!', $user->fresh()->password));
    }

    public function test_unknown_records_return_not_found(): void
    {
        $this->actingAs($this->user());
        foreach (['/clientes/99999', '/mesas/99999', '/usuarios/99999/edit'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_password_reset_invalidates_an_existing_session(): void
    {
        $user = $this->user('trabajador');
        $this->actingAs($user)->withSession(['password_hash_web' => $user->password])->get('/inicio')->assertOk();
        $user->update(['password' => 'CambioSeguro2026!']);
        $this->get('/inicio')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_changed_role_applies_to_the_next_request(): void
    {
        $user = $this->user('trabajador');
        $this->actingAs($user)->get('/clientes')->assertOk();
        $user->update(['rol' => 'cliente']);
        $this->get('/clientes')->assertForbidden();
        $this->get('/mi-perfil')->assertOk();
    }

    public function test_password_length_is_checked_in_bytes_for_bcrypt(): void
    {
        $password = 'Clave'.str_repeat('ñ', 34).'A1';
        $this->actingAs($this->user())->post('/usuarios', $this->accountData([
            'password' => $password, 'password_confirmation' => $password,
        ]))->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'cuenta@example.test']);
    }
}
