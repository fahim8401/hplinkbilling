<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ResellerService;
use Illuminate\Console\Command;

class CalculateResellerCommissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reseller:calculate-commissions {--immediate : Pay commissions immediately}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and payout reseller commissions';

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
    public function handle(ResellerService $resellerService)
    {
        $immediate = $this->option('immediate');
        $action = $immediate ? 'paying out' : 'calculating';
        
        $this->info("{$action} reseller commissions...");

        // Get all resellers
        $resellers = User::where('user_type', 'reseller')->get();

        foreach ($resellers as $reseller) {
            $this->info("Processing commissions for reseller: {$reseller->name}");

            // Payout commissions for this reseller
            $resellerService->payoutCommission($reseller, $immediate);
        }

        $this->info('Reseller commission processing completed.');
        return 0;
    }
}