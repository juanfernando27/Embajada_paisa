<?php

namespace Tests\Feature;

use App\Mail\LoginSucceeded;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_requires_state_and_never_claims_an_existing_admin_by_email(): void
    {
        Mail::fake();
        $this->configureProvider('google');
        $admin = User::factory()->create(['rol' => 'administrador']);
        $flow = $this->start('google');
        $this->get('/auth/google/callback?state=incorrecto&code=falso')->assertRedirect('/login')->assertSessionHasErrors('oauth');
        Socialite::shouldReceive('driver->setHttpClient->user')->once()->andReturn($this->identity($admin->email, ['verified_email' => true]));
        $this->get('/auth/google/callback?'.http_build_query(['state' => $flow['state'], 'code' => 'simulado']))
            ->assertRedirect('/login')->assertSessionHasErrors('oauth');
        $this->assertGuest();
        $this->assertDatabaseCount('social_accounts', 0);
        Mail::assertNothingSent();
    }

    public function test_google_creates_a_customer_and_sends_login_notification(): void
    {
        Mail::fake();
        $this->configureProvider('google');
        $flow = $this->start('google');
        Socialite::shouldReceive('driver->setHttpClient->user')->once()->andReturn($this->identity('google@example.test', ['verified_email' => true]));
        $this->get('/auth/google/callback?'.http_build_query(['state' => $flow['state'], 'code' => 'simulado']))->assertRedirect('/inicio');
        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('cliente', $user->rol);
        $client = $user->cliente;
        $this->assertNotNull($client);
        $this->get('/mi-perfil')->assertOk()->assertSee('google@example.test');
        Mail::assertSentCount(1);
        Mail::assertSent(LoginSucceeded::class, fn ($mail) => $mail->hasTo('google@example.test'));

        $admin = User::factory()->create(['rol' => 'administrador']);
        $this->flushSession()->actingAs($admin)->get('/clientes?buscar=google%40example.test')
            ->assertOk()->assertSee('google@example.test');
        $this->get(route('clientes.show', $client))->assertOk()->assertSee('google@example.test');

        $client->update(['email' => 'contacto@example.test']);
        $this->get(route('clientes.show', $client))->assertOk()
            ->assertSee('contacto@example.test')->assertDontSee('google@example.test');
    }

    public function test_apple_form_post_requires_the_original_state_and_nonce(): void
    {
        Mail::fake();
        $this->configureProvider('apple');
        $this->get('/auth/apple/redirect')->assertRedirect();
        $flow = session('oauth_flow');
        $this->assertNotEmpty($flow['nonce']);
        $this->post('/auth/apple/callback', ['state' => 'otro', 'code' => 'simulado'])->assertRedirect('/login')->assertSessionHasErrors('oauth');
        Socialite::shouldReceive('driver->setHttpClient->user')->once()->andReturn($this->identity('relay@example.test', [
            'email_verified' => 'true', 'nonce' => 'incorrecto',
        ]));
        $this->post('/auth/apple/callback', ['state' => $flow['state'], 'code' => 'simulado'])->assertRedirect('/login')->assertSessionHasErrors('oauth');
        $this->assertGuest();
        $this->assertDatabaseCount('social_accounts', 0);
        Mail::assertNothingSent();
    }

    public function test_apple_accepts_a_matching_nonce_and_links_only_from_authenticated_account(): void
    {
        $this->configureProvider('apple');
        $account = User::factory()->create(['rol' => 'administrador']);
        $redirect = $this->actingAs($account)->post('/auth/apple/vincular')->assertRedirect();
        $this->assertStringContainsString('response_mode=form_post', $redirect->headers->get('Location'));
        $flow = session('oauth_flow');
        Socialite::shouldReceive('driver->setHttpClient->user')->once()->andReturn($this->identity('apple@example.test', [
            'email_verified' => 'true', 'nonce' => $flow['nonce'],
        ]));
        $this->post('/auth/apple/callback', ['state' => $flow['state'], 'code' => 'simulado'])->assertRedirect('/cuenta/conexiones');
        $this->assertDatabaseHas('social_accounts', ['provider' => 'apple', 'user_id' => $account->id]);
        $this->assertSame('administrador', $account->fresh()->rol);
    }

    public function test_apple_creates_a_customer_and_sends_notice_to_the_relay_address(): void
    {
        Mail::fake();
        $this->configureProvider('apple');
        $flow = $this->start('apple');
        $relay = 'cliente@privaterelay.appleid.com';
        Socialite::shouldReceive('driver->setHttpClient->user')->once()->andReturn($this->identity($relay, [
            'email_verified' => 'true', 'nonce' => $flow['nonce'],
        ]));
        $this->post('/auth/apple/callback', ['state' => $flow['state'], 'code' => 'simulado'])->assertRedirect('/inicio');
        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->get('/mi-perfil')->assertOk()->assertSee('Documento pendiente');
        Mail::assertSentCount(1);
        Mail::assertSent(LoginSucceeded::class, fn ($mail) => $mail->hasTo($relay));
    }

    public function test_password_login_sends_notice_only_after_success(): void
    {
        Mail::fake();
        $user = User::factory()->create(['activo' => true, 'password' => 'ClavePrueba2026!']);
        $this->post('/login', ['email' => $user->email, 'password' => 'Incorrecta'])->assertSessionHasErrors('email');
        Mail::assertNothingSent();
        $this->post('/login', ['email' => $user->email, 'password' => 'ClavePrueba2026!'])->assertRedirect('/inicio');
        Mail::assertSentCount(1);
        Mail::assertSent(LoginSucceeded::class, fn ($mail) => $mail->hasTo($user->email));
    }

    private function configureProvider(string $name): void
    {
        config(["services.$name" => [
            'enabled' => true, 'client_id' => 'cliente-de-prueba', 'client_secret' => 'secreto-de-prueba',
            'redirect' => "https://embajada.example.test/auth/$name/callback",
        ]]);
    }

    private function start(string $provider): array
    {
        $this->get("/auth/$provider/redirect")->assertRedirect();

        return session('oauth_flow');
    }

    private function identity(string $email, array $claims): SocialUser
    {
        return (new SocialUser)->setRaw($claims)->map(['id' => 'subject-externo', 'name' => 'Nombre externo', 'email' => $email]);
    }
}
