<?php

namespace App\Providers;

use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\User;
use App\Observers\LegacyAuditObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::defaultView('components.pagination');
        foreach ([User::class, Cliente::class, Mesa::class] as $m) {
            $m::observe(LegacyAuditObserver::class);
        }
    }
}
