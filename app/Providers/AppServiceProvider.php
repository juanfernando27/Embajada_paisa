<?php

namespace App\Providers;

use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\User;
use App\Observers\LegacyAuditObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Apple\Provider as AppleProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Event::listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event): void {
            $event->extendSocialite('apple', AppleProvider::class);
        });

        Paginator::defaultView('components.pagination');
        foreach ([User::class, Cliente::class, Mesa::class] as $m) {
            $m::observe(LegacyAuditObserver::class);
        }
    }
}
