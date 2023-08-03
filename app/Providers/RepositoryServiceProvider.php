<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->bind(\App\Repositories\Contracts\UsersRepository::class, \App\Repositories\UsersRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\UserDetailRepository::class, \App\Repositories\UserDetailRepositoryEloquent::class);
        $this->app->bind(\App\Repositories\Contracts\RoleRepository::class, \App\Repositories\RoleRepositoryEloquent::class);
        //:end-bindings:
    }
}
