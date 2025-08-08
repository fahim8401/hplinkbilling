<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\SMSService;
use Illuminate\Console\Command;

class SendSMSNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS notifications for various events';

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
        $this->info('Sending SMS notifications...');

        // Send expiry warnings (7 days before expiry)
        $this->sendExpiryWarnings($smsService);

        // Send suspension notices (on expiry date)
        $this->sendSuspensionNotices($smsService);

        $this->info('SMS notifications sent.');
        return 0;
    }

    /**
     * Send expiry warnings to customers.
     *
     * @param SMSService $smsService
     * @return void
     */
    protected function sendExpiryWarnings(SMSService $smsService)
    {
        $this->info('Sending expiry warnings...');

        // Find customers expiring in 7 days
        $customers = Customer::where('expiry_date', now()->addDays(7))
            ->where('status', 'active')
            ->get();

        foreach ($customers as $customer) {
            // Get the expiry warning template
            $template = $customer->company->smsTemplates()
                ->where('category', 'expiry_warning')
                ->first();

            if ($template) {
                // Send the SMS
                $smsService->sendSMSTemplate($template, $customer->phone, [
                    'name' => $customer->name,
                    'package' => $customer->package->name ?? 'N/A',
                    'expiry_date' => $customer->expiry_date->format('Y-m-d'),
                ]);
            }
        }

        $this->info('Sent expiry warnings to ' . $customers->count() . ' customers.');
    }

    /**
     * Send suspension notices to customers.
     *
     * @param SMSService $smsService
     * @return void
     */
    protected function sendSuspensionNotices(SMSService $smsService)
    {
        $this->info('Sending suspension notices...');

        // Find customers expiring today
        $customers = Customer::where('expiry_date', now())
            ->where('status', 'active')
            ->get();

        foreach ($customers as $customer) {
            // Get the suspension notice template
            $template = $customer->company->smsTemplates()
                ->where('category', 'suspension_notice')
                ->first();

            if ($template) {
                // Send the SMS
                $smsService->sendSMSTemplate($template, $customer->phone, [
                    'name' => $customer->name,
                    'package' => $customer->package->name ?? 'N/A',
                ]);
            }
        }

        $this->info('Sent suspension notices to ' . $customers->count() . ' customers.');
    }
}