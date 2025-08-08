<?php

namespace App\Providers;

use App\Services\BillingService;
use App\Services\BulkOperationService;
use App\Services\ReportService;
use App\Services\ResellerService;
use App\Services\RouterOSService;
use App\Services\SMSService;
use App\Services\SupportTicketService;
use Illuminate\Support\ServiceProvider;
use RouterOS\Client;

class CustomServicesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BillingService::class, function ($app) {
            return new BillingService();
        });

        $this->app->singleton(BulkOperationService::class, function ($app) {
            return new BulkOperationService();
        });

        $this->app->singleton(ReportService::class, function ($app) {
            return new ReportService();
        });

        $this->app->singleton(ResellerService::class, function ($app) {
            return new ResellerService();
        });

        $this->app->singleton(RouterOSService::class, function ($app) {
            // This would need to be configured with the appropriate connection details
            // For now, we'll create a placeholder client
            $client = new Client([
                'host' => config('routeros.host', '192.168.1.1'),
                'user' => config('routeros.user', 'admin'),
                'pass' => config('routeros.pass', 'password'),
                'port' => config('routeros.port', 8728),
            ]);
            
            return new RouterOSService($client);
        });

        $this->app->singleton(SMSService::class, function ($app) {
            return new SMSService();
        });

        $this->app->singleton(SupportTicketService::class, function ($app) {
            return new SupportTicketService();
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