<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RouterOS\Client;

class RouterOSClientServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            // This is a placeholder implementation
            // In a real application, you would configure the client
            // with the appropriate connection details
            return new Client([
                'host' => config('routeros.host', '192.168.1.1'),
                'user' => config('routeros.user', 'admin'),
                'pass' => config('routeros.pass', 'password'),
                'port' => config('routeros.port', 8728),
            ]);
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