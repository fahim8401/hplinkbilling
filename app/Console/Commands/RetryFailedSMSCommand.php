<?php

namespace App\Console\Commands;

use App\Services\SMSService;
use Illuminate\Console\Command;

class RetryFailedSMSCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:retry-failed {--limit=10 : Number of failed SMS to retry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry sending failed SMS messages';

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
    public function handle(SMSService $smsService)
    {
        $limit = $this->option('limit');
        $this->info("Retrying failed SMS messages (limit: {$limit})...");

        // Retry failed SMS messages
        $retryCount = $smsService->retryFailedSMS($limit);

        $this->info("Retried {$retryCount} failed SMS messages.");
        return 0;
    }
}