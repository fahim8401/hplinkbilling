<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\BillingService;
use Illuminate\Console\Command;

class GenerateInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:generate-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate invoices for companies on their billing day';

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
        $this->info('Generating invoices for companies...');

        // Get companies with billing day matching today
        $companies = Company::where('billing_day', now()->day)->get();

        foreach ($companies as $company) {
            $this->info("Generating invoices for company: {$company->name}");

            // Generate invoices for the company
            $invoiceCount = $billingService->generateInvoicesForCompany($company->id);

            $this->info("Generated {$invoiceCount} invoices for company: {$company->name}");
        }

        $this->info('Invoice generation completed.');
        return 0;
    }
}