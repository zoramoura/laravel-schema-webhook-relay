<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Horizon authentication for local/dev dashboard.
        Horizon::auth(function ($request) {
            return true; // Local-only dashboard; protect behind auth in production.
        });
    }
}