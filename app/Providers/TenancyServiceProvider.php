<?php

namespace App\Providers;

use App\Services\TenancyService;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TenancyService::class, function ($app) {
            return new TenancyService();
        });
        
        $this->app->alias(TenancyService::class, 'tenancy');
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