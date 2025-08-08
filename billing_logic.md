# Billing Logic Implementation Plan

## Overview
This document outlines the implementation plan for the billing logic in the ISP Billing & CRM system. The billing logic includes company-level bill generation, customer monthly billing rules, recharge types, auto-expiry actions, and related functionality.

## Company-Level Bill Generation

### Billing Day Configuration
- Each company has a configurable `billing_day` (default: 10)
- Super Admin can set billing day for each company
- Bills are generated on the specified day each month

### Billing Run Process
1. **Trigger**: Cron job runs daily to check for companies with matching billing_day
2. **Validation**: Check if billing run already executed for current month
3. **Customer Selection**: Identify active customers on billing date
4. **Invoice Generation**: Create invoices for selected customers
5. **Notification**: Send billing notifications to customers

### Cron Job Implementation
```php
// Daily cron job
// app/Console/Commands/GenerateInvoicesCommand.php

public function handle()
{
    $companies = Company::where('billing_day', now()->day)->get();
    
    foreach ($companies as $company) {
        // Check if billing run already executed for current month
        if (!$this->billingRunExists($company, now()->format('Y-m'))) {
            $this->generateCompanyInvoices($company);
        }
    }
}
```

## Customer Monthly Billing Rules

### Expiry Date Calculation
- Calendar-based billing (preserve day-of-month when possible)
- Special handling for months with fewer days

#### Algorithm
```php
function calculateNextExpiryDate($currentExpiryDate) {
    $currentDate = new DateTime($currentExpiryDate);
    $nextMonth = clone $currentDate;
    $nextMonth->modify('first day of next month');
    $nextMonth->modify('+' . ($currentDate->format('d') - 1) . ' days');
    
    // If the calculated day doesn't exist in the next month, 
    // use the last day of the next month
    if ($nextMonth->format('m') != $currentDate->format('m')) {
        $nextMonth = clone $currentDate;
        $nextMonth->modify('last day of next month');
    }
    
    return $nextMonth;
}
```

### Examples
1. Customer activation date: 2025-01-31 → Next expiry: 2025-02-28 (or 29 in leap year)
2. Customer activation date: 2025-03-15 → Next expiry: 2025-04-15
3. Customer activation date: 2025-05-01 → Next expiry: 2025-06-01

## Recharge Types Implementation

### RECEIVE (Paid) Recharge
```php
class ReceiveRechargeService
{
    public function processRecharge($customer, $amount, $operator)
    {
        // Determine new expiry date
        if ($customer->expiry_date < now()) {
            // Customer expired, start from today
            $newExpiryDate = $this->calculateNextExpiryDate(now());
        } else {
            // Customer still active, extend from current expiry
            $newExpiryDate = $this->calculateNextExpiryDate($customer->expiry_date);
        }
        
        // Update customer expiry date
        $customer->expiry_date = $newExpiryDate;
        $customer->save();
        
        // Create payment record
        $payment = Payment::create([
            'customer_id' => $customer->id,
            'amount' => $amount,
            'payment_method' => 'receive',
            'operator_id' => $operator->id,
            'payment_date' => now(),
            'status' => 'paid'
        ]);
        
        // Mark invoice as paid if exists
        $this->markInvoiceAsPaid($customer, $amount);
        
        // Calculate and record reseller commission
        $this->calculateResellerCommission($customer, $amount);
        
        return $payment;
    }
}
```

### DUE (Unpaid Extension) Recharge
```php
class DueRechargeService
{
    public function processRecharge($customer, $amount, $operator)
    {
        // Determine new expiry date (same rules as RECEIVE)
        if ($customer->expiry_date < now()) {
            $newExpiryDate = $this->calculateNextExpiryDate(now());
        } else {
            $newExpiryDate = $this->calculateNextExpiryDate($customer->expiry_date);
        }
        
        // Update customer expiry date
        $customer->expiry_date = $newExpiryDate;
        $customer->save();
        
        // Create payment record marked as unpaid
        $payment = Payment::create([
            'customer_id' => $customer->id,
            'amount' => $amount,
            'payment_method' => 'due',
            'operator_id' => $operator->id,
            'payment_date' => now(),
            'status' => 'unpaid'
        ]);
        
        // Create invoice marked as unpaid
        $invoice = $this->createUnpaidInvoice($customer, $amount);
        
        return $payment;
    }
}
```

## Auto-Expiry Actions

### Customer Type Handling
1. **home/corporate**: Disable service when expired and unpaid
2. **vip**: Continue service but show invoice as due
3. **free**: Never disable service, never generate bills

### Expiry Processing Cron Job
```php
// Daily cron job to process expirations
// app/Console/Commands/ProcessExpirationsCommand.php

public function handle()
{
    // Find customers who have expired
    $expiredCustomers = Customer::where('expiry_date', '<', now())
        ->where('status', 'active')
        ->get();
    
    foreach ($expiredCustomers as $customer) {
        $this->processCustomerExpiry($customer);
    }
}

private function processCustomerExpiry($customer)
{
    switch ($customer->customer_type) {
        case 'home':
        case 'corporate':
            // Check if invoice is unpaid
            if ($this->hasUnpaidInvoice($customer)) {
                // Change to expired package
                $customer->package_id = $this->getExpiredPackageId($customer->company_id);
                $customer->status = 'expired';
                $customer->save();
                
                // Disable PPPoE user via MikroTik API
                $this->disablePPPoEUser($customer);
            }
            break;
            
        case 'vip':
            // Do not disable, just show invoice as due
            break;
            
        case 'free':
            // Do nothing, never disable
            break;
    }
}
```

## Invoice Structure Implementation

### Invoice Model
```php
class Invoice extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_number',
        'billing_date',
        'due_date',
        'base_price',
        'vat_amount',
        'total_amount',
        'status',
        'payment_date',
        'notes'
    ];
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
```

### Invoice Generation Service
```php
class InvoiceGenerationService
{
    public function generateInvoice($customer, $billingDate)
    {
        // Calculate billing period
        $billingPeriodStart = $this->getBillingPeriodStart($customer, $billingDate);
        $billingPeriodEnd = $this->getBillingPeriodEnd($customer, $billingDate);
        
        // Get customer package
        $package = $customer->package;
        
        // Calculate base price
        $basePrice = $package->price;
        
        // Calculate VAT
        $vatPercent = $package->vat_percent ?? $customer->company->vat_percent ?? 0;
        $vatAmount = $basePrice * ($vatPercent / 100);
        
        // Calculate total
        $totalAmount = $basePrice + $vatAmount;
        
        // Generate invoice number
        $invoiceNumber = $this->generateInvoiceNumber($customer->company_id);
        
        // Create invoice
        $invoice = Invoice::create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'invoice_number' => $invoiceNumber,
            'billing_date' => $billingDate,
            'due_date' => $billingDate->copy()->addDays(15), // 15 days grace period
            'base_price' => $basePrice,
            'vat_amount' => $vatAmount,
            'total_amount' => $totalAmount,
            'status' => 'unpaid'
        ]);
        
        return $invoice;
    }
}
```

## Proration Rules Implementation

### Proration Service
```php
class ProrationService
{
    public function calculateProration($oldPackage, $newPackage, $changeDate)
    {
        // Calculate unused days in current billing cycle
        $billingPeriodEnd = $this->getCurrentBillingPeriodEnd($changeDate);
        $unusedDays = $changeDate->diffInDays($billingPeriodEnd);
        $totalDaysInPeriod = $changeDate->daysInMonth;
        
        // Credit for unused days from old package
        $dailyRateOld = $oldPackage->price / $totalDaysInPeriod;
        $creditAmount = $dailyRateOld * $unusedDays;
        
        // Charge for new package from change date to period end
        $dailyRateNew = $newPackage->price / $totalDaysInPeriod;
        $chargeAmount = $dailyRateNew * $unusedDays;
        
        // Net amount (charge - credit)
        $netAmount = $chargeAmount - $creditAmount;
        
        return [
            'credit_amount' => $creditAmount,
            'charge_amount' => $chargeAmount,
            'net_amount' => $netAmount,
            'unused_days' => $unusedDays
        ];
    }
}
```

## Cron Job Scheduling

### Kernel Schedule
```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Daily billing run
    $schedule->command('billing:generate-invoices')
             ->dailyAt('02:00');
             
    // Daily expiry processing
    $schedule->command('billing:process-expirations')
             ->dailyAt('03:00');
             
    // Monthly commission calculation
    $schedule->command('reseller:calculate-commissions')
             ->monthlyOn(1, '04:00');
             
    // Daily SMS notifications
    $schedule->command('sms:send-notifications')
             ->dailyAt('09:00');
}
```

## Payment Processing Implementation

### Payment Service
```php
class PaymentService
{
    public function processOnlinePayment($customer, $amount, $gateway, $transactionId)
    {
        // Create payment record
        $payment = Payment::create([
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
            'amount' => $amount,
            'payment_method' => 'online',
            'payment_gateway' => $gateway,
            'transaction_id' => $transactionId,
            'payment_date' => now(),
            'status' => 'paid'
        ]);
        
        // Update customer balance if applicable
        $this->updateCustomerBalance($customer, $amount);
        
        // Mark associated invoice as paid
        $this->markInvoiceAsPaid($customer, $amount);
        
        // Calculate and record reseller commission
        $this->calculateResellerCommission($customer, $amount);
        
        // Send payment confirmation notification
        $this->sendPaymentConfirmation($customer, $payment);
        
        return $payment;
    }
}
```

## Customer Status Management

### Status Transition Service
```php
class CustomerStatusService
{
    public function updateCustomerStatus($customer, $newStatus)
    {
        $oldStatus = $customer->status;
        $customer->status = $newStatus;
        $customer->save();
        
        // Log status change
        CustomerStatusLog::create([
            'customer_id' => $customer->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id(),
            'reason' => 'System automated process'
        ]);
        
        // Trigger status change events
        event(new CustomerStatusChanged($customer, $oldStatus, $newStatus));
    }
}
```

## Reporting and Analytics

### Billing Reports
1. **Invoice Summary Report**: Total invoices, paid/unpaid status
2. **Payment Collection Report**: Daily/weekly/monthly collections
3. **Outstanding Balance Report**: Unpaid invoices by customer
4. **Revenue Recognition Report**: Revenue by period
5. **Customer Churn Report**: Expired/disabled customers

### Implementation Example
```php
class BillingReportService
{
    public function generateInvoiceSummary($companyId, $startDate, $endDate)
    {
        return Invoice::where('company_id', $companyId)
            ->whereBetween('billing_date', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_invoices,
                SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_invoices,
                SUM(CASE WHEN status = "unpaid" THEN 1 ELSE 0 END) as unpaid_invoices,
                SUM(total_amount) as total_amount,
                SUM(CASE WHEN status = "paid" THEN total_amount ELSE 0 END) as collected_amount,
                SUM(CASE WHEN status = "unpaid" THEN total_amount ELSE 0 END) as outstanding_amount
            ')
            ->first();
    }
}
```

## Error Handling and Logging

### Exception Handling
```php
class BillingException extends Exception
{
    // Custom billing exceptions
}

class BillingService
{
    public function processBilling($customer)
    {
        try {
            // Billing logic
            $this->generateInvoice($customer);
        } catch (BillingException $e) {
            // Log billing-specific errors
            Log::error('Billing error for customer ' . $customer->id, [
                'error' => $e->getMessage(),
                'customer_id' => $customer->id
            ]);
            
            // Notify administrators
            $this->notifyBillingError($customer, $e);
        } catch (Exception $e) {
            // Log general errors
            Log::error('General billing error', [
                'error' => $e->getMessage(),
                'customer_id' => $customer->id
            ]);
        }
    }
}
```

## Testing Strategy

### Unit Tests
1. Expiry date calculation
2. Invoice generation
3. Payment processing
4. Recharge operations
5. Proration calculations

### Integration Tests
1. End-to-end billing cycle
2. Multi-tenant billing isolation
3. Reseller commission calculation
4. SMS notification triggers

### Test Examples
```php
class BillingServiceTest extends TestCase
{
    public function test_next_expiry_date_preserves_day()
    {
        $currentExpiry = '2025-03-15';
        $expectedNextExpiry = '2025-04-15';
        
        $nextExpiry = BillingService::calculateNextExpiryDate($currentExpiry);
        
        $this->assertEquals($expectedNextExpiry, $nextExpiry);
    }
    
    public function test_expiry_date_handles_february_edge_case()
    {
        $currentExpiry = '2025-01-31';
        $expectedNextExpiry = '2025-02-28'; // Not 2025-03-03
        
        $nextExpiry = BillingService::calculateNextExpiryDate($currentExpiry);
        
        $this->assertEquals($expectedNextExpiry, $nextExpiry);
    }
}
```

## Performance Considerations

### Database Optimization
1. Indexes on frequently queried columns
2. Partitioning for large datasets
3. Caching for frequently accessed data

### Batch Processing
```php
class BatchBillingService
{
    public function processCompanyBilling($company, $batchSize = 100)
    {
        $customers = Customer::where('company_id', $company->id)
            ->where('status', 'active')
            ->paginate($batchSize);
            
        foreach ($customers as $customer) {
            $this->processCustomerBilling($customer);
        }
    }
}
```

## Security Considerations

### Data Validation
1. Validate all billing inputs
2. Prevent negative amounts
3. Validate payment methods
4. Audit all billing operations

### Access Control
1. Role-based access to billing functions
2. Multi-tenant data isolation
3. Secure payment processing
4. Encrypted sensitive data

This comprehensive billing logic implementation plan covers all the requirements specified in the project prompt and provides a solid foundation for building the billing system.