<?php

namespace App\Console\Commands;

use App\Models\MikrotikRouter;
use App\Services\RouterOSService;
use Illuminate\Console\Command;
use RouterOS\Client;

class SyncMikrotikProfilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:sync-profiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync PPP profiles from MikroTik routers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(RouterOSService $routerOSService)
    {
        $this->info('Syncing PPP profiles from MikroTik routers...');

        // Get all active MikroTik routers
        $routers = MikrotikRouter::where('status', 'active')->get();

        foreach ($routers as $router) {
            $this->info("Syncing profiles from router: {$router->name}");

            // Create a new client for this router
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => decrypt($router->password),
                'port' => $router->port,
            ]);

            // Create a new service instance with this client
            $service = new RouterOSService($client);

            // Get profiles from the router
            $profiles = $service->getProfiles();

            foreach ($profiles as $profile) {
                // Update or create the profile in our database
                $router->profiles()->updateOrCreate(
                    ['profile_id' => $profile['.id']],
                    [
                        'profile_name' => $profile['name'],
                        'rate_limit' => $profile['rate-limit'] ?? null,
                        'session_timeout' => $profile['session-timeout'] ?? null,
                        'idle_timeout' => $profile['idle-timeout'] ?? null,
                    ]
                );
            }

            $this->info("Synced " . count($profiles) . " profiles from router: {$router->name}");
        }

        $this->info('MikroTik profile sync completed.');
        return 0;
    }
}