<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\BkashPaymentService;
use App\Services\NagadPaymentService;

class PaymentGatewayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BkashPaymentService::class, function ($app) {
            return new BkashPaymentService();
        });
        
        $this->app->singleton(NagadPaymentService::class, function ($app) {
            return new NagadPaymentService();
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