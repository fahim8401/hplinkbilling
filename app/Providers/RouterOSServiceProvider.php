<?php

namespace App\Providers;

use App\Services\RouterOSService;
use Illuminate\Support\ServiceProvider;

class RouterOSServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(RouterOSService::class, function ($app) {
            return new RouterOSService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}