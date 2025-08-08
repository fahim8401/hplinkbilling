<?php

namespace App\Providers;

use App\Services\ResellerService;
use Illuminate\Support\ServiceProvider;

class ResellerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ResellerService::class, function ($app) {
            return new ResellerService();
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