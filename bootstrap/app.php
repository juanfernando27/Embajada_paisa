<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\RequireRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        // Apple retorna por POST. El callback valida el estado y nonce de la sesión.
        $middleware->validateCsrfTokens(except: ['auth/apple/callback']);
        $middleware->alias(['activo' => EnsureActiveUser::class, 'rol' => RequireRole::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {})
    ->create();
