<?php

namespace App\Providers;

use App\Services\BulkOperationService;
use Illuminate\Support\ServiceProvider;

class BulkOperationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BulkOperationService::class, function ($app) {
            return new BulkOperationService();
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