<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ReusableFacadesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('Reusable', function()
        {
            return new \App\Repositories\Reusable;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
