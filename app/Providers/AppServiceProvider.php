<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Passport::authorizationView('auth.oauth.authorize');

        Passport::tokensCan([
            'portfolio:read' => 'Read published projects and categories',
        ]);

        Passport::defaultScopes([]);
    }
}