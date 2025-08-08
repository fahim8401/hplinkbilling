<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\BillingService;
use Illuminate\Console\Command;

class ProcessExpirationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:process-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process customer expirations and take appropriate actions';

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
    public function handle(BillingService $billingService)
    {
        $this->info('Processing customer expirations...');

        // Find customers who have expired
        $expiredCustomers = Customer::where('expiry_date', '<', now())
            ->where('status', 'active')
            ->get();

        foreach ($expiredCustomers as $customer) {
            $this->info("Processing expiration for customer: {$customer->name}");

            // Process customer expiration
            $billingService->processCustomerExpiry($customer);
        }

        $this->info('Customer expiration processing completed.');
        return 0;
    }
}