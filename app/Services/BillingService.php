<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Package;
use App\Models\ResellerCommission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    /**
     * Generate invoices for all active customers of a company.
     *
     * @param int $companyId
     * @return int
     */
    public function generateInvoicesForCompany($companyId)
    {
        $customers = Customer::where('company_id', $companyId)
            ->where('status', 'active')
            ->where('customer_type', '!=', 'free')
            ->get();

        $invoiceCount = 0;

        foreach ($customers as $customer) {
            // Skip customers with no package
            if (!$customer->package) {
                continue;
            }

            // Generate invoice for the customer
            $invoice = $this->generateInvoiceForCustomer($customer);
            
            if ($invoice) {
                $invoiceCount++;
            }
        }

        return $invoiceCount;
    }

    /**
     * Generate an invoice for a customer.
     *
     * @param Customer $customer
     * @return Invoice|null
     */
    public function generateInvoiceForCustomer(Customer $customer)
    {
        // Skip if customer is free type
        if ($customer->customer_type === 'free') {
            return null;
        }

        // Skip if customer has no package
        if (!$customer->package) {
            return null;
        }

        $package = $customer->package;
        
        // Calculate billing period
        $billingDate = now();
        $dueDate = $billingDate->copy()->addDays(15); // 15 days grace period
        
        // Calculate base price
        $basePrice = $package->price;
        
        // Calculate VAT
        $vatPercent = $package->vat_percent ?? $customer->company->vat_percent ?? 0;
        $vatAmount = $basePrice * ($vatPercent / 100);
        
        // Calculate total
        $totalAmount = $basePrice + $vatAmount;
        
        // Generate invoice number
        $invoiceNumber = $this->generateInvoiceNumber($customer->company_id);

        // Create the invoice
        $invoice = Invoice::create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'invoice_number' => $invoiceNumber,
            'billing_date' => $billingDate,
            'due_date' => $dueDate,
            'base_price' => $basePrice,
            'vat_amount' => $vatAmount,
            'total_amount' => $totalAmount,
            'status' => 'unpaid'
        ]);

        return $invoice;
    }

    /**
     * Generate a unique invoice number.
     *
     * @param int $companyId
     * @return string
     */
    protected function generateInvoiceNumber($companyId)
    {
        $date = now()->format('Ymd');
        $lastInvoice = Invoice::where('company_id', $companyId)
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? intval(substr($lastInvoice->invoice_number, -4)) + 1 : 1;
        return 'INV-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Process a payment for a customer.
     *
     * @param Customer $customer
     * @param float $amount
     * @param string $paymentMethod
     * @param string|null $gateway
     * @param string|null $transactionId
     * @param int|null $operatorId
     * @return Payment
     */
    public function processPayment(Customer $customer, $amount, $paymentMethod, $gateway = null, $transactionId = null, $operatorId = null)
    {
        // Create payment record
        $payment = Payment::create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'payment_gateway' => $gateway,
            'transaction_id' => $transactionId,
            'operator_id' => $operatorId,
            'payment_date' => now(),
        ]);

        // If customer has a reseller, calculate and record commission
        if ($customer->reseller_id) {
            $this->calculateAndRecordCommission($customer, $amount);
        }

        return $payment;
    }

    /**
     * Calculate and record reseller commission.
     *
     * @param Customer $customer
     * @param float $amount
     * @return ResellerCommission|null
     */
    public function calculateAndRecordCommission(Customer $customer, $amount)
    {
        // Skip if customer has no reseller
        if (!$customer->reseller_id) {
            return null;
        }

        $reseller = $customer->reseller;
        
        // Skip if reseller has no commission percentage
        if (!$reseller->commission_percent) {
            return null;
        }

        // Get the customer's package for base price calculation
        $package = $customer->package;
        
        if (!$package) {
            return null;
        }

        // Calculate commission on base price (excluding VAT)
        $baseAmount = $package->price;
        $commissionPercent = $reseller->commission_percent;
        $commissionAmount = $baseAmount * ($commissionPercent / 100);

        // Create commission record
        $commission = ResellerCommission::create([
            'company_id' => $customer->company_id,
            'reseller_id' => $reseller->id,
            'customer_id' => $customer->id,
            'base_amount' => $baseAmount,
            'commission_percent' => $commissionPercent,
            'commission_amount' => $commissionAmount,
            'status' => 'pending'
        ]);

        return $commission;
    }

    /**
     * Process customer expiry and take appropriate actions.
     *
     * @param Customer $customer
     * @return void
     */
    public function processCustomerExpiry(Customer $customer)
    {
        // Skip if customer is free type
        if ($customer->customer_type === 'free') {
            return;
        }

        // Skip if customer has not expired
        if ($customer->expiry_date && $customer->expiry_date->isFuture()) {
            return;
        }

        // For VIP customers, do not disable but show invoice as due
        if ($customer->customer_type === 'vip') {
            return;
        }

        // For home/corporate customers, check if invoice is unpaid
        $unpaidInvoices = $customer->invoices()->where('status', 'unpaid')->get();

        if ($unpaidInvoices->isNotEmpty()) {
            // Change customer package to "Expired" package
            $expiredPackage = Package::where('company_id', $customer->company_id)
                ->where('is_expired_package', true)
                ->first();

            if ($expiredPackage) {
                $customer->package_id = $expiredPackage->id;
            }

            // Disable customer
            $customer->status = 'expired';
            $customer->save();

            // Disable PPPoE user via MikroTik API (this would be implemented in the RouterOSService)
            // $this->routerOSService->disablePPPoEUser($customer->username);
        }
    }

    /**
     * Extend customer expiry date based on payment type.
     *
     * @param Customer $customer
     * @param string $paymentType
     * @return void
     */
    public function extendCustomerExpiry(Customer $customer, $paymentType = 'receive')
    {
        $newExpiryDate = null;

        if ($paymentType === 'receive') {
            // If customer.expiry_date < today (expired):
            if ($customer->expiry_date && $customer->expiry_date->isPast()) {
                // new_expiry = date_add_months(today, 1) preserving day as described.
                $newExpiryDate = $this->calculateNextExpiryDate(now());
            } else {
                // Else (customer still active):
                // new_expiry = date_add_months(customer.expiry_date, 1).
                $newExpiryDate = $this->calculateNextExpiryDate($customer->expiry_date ?? now());
            }
        } elseif ($paymentType === 'due') {
            // Same date rules as RECEIVE for new_expiry, BUT mark bill as UNPAID (due / outstanding) in invoices/payments.
            if ($customer->expiry_date && $customer->expiry_date->isPast()) {
                $newExpiryDate = $this->calculateNextExpiryDate(now());
            } else {
                $newExpiryDate = $this->calculateNextExpiryDate($customer->expiry_date ?? now());
            }
        }

        if ($newExpiryDate) {
            $customer->expiry_date = $newExpiryDate;
            $customer->save();
        }
    }

    /**
     * Calculate next expiry date preserving day of month when possible.
     *
     * @param \Carbon\Carbon $currentExpiryDate
     * @return \Carbon\Carbon
     */
    protected function calculateNextExpiryDate($currentExpiryDate)
    {
        $currentDate = $currentExpiryDate->copy();
        $nextMonth = $currentDate->copy()->addMonth();
        $nextMonth->day($currentDate->day);

        // If the calculated day doesn't exist in the next month,
        // use the last day of the next month
        if ($nextMonth->month != $currentDate->addMonth()->month) {
            $nextMonth = $currentDate->copy()->lastOfMonth();
        }

        return $nextMonth;
    }
}