# Reseller Balance and Commission System Plan

## Overview
This document outlines the implementation plan for the reseller balance and commission system in the ISP Billing & CRM system. The system will manage reseller balances, fund transfers, commission calculations, and reporting.

## System Components

### 1. Reseller Balance Management
- Balance tracking for resellers
- Balance tracking for reseller employees
- Fund transfer between entities
- Balance validation for operations

### 2. Commission Calculation
- Percentage-based commission calculation
- Commission tracking and payout
- Commission reporting
- VAT exclusion from commission calculation

### 3. Fund Transfer System
- Transfer between super admin and resellers
- Transfer between resellers and employees
- Commission payout tracking
- Transfer logging

### 4. Balance Validation
- Pre-operation balance checks
- Sufficient balance validation
- Automatic balance deduction

## Reseller Balance Management

### Reseller Balance Model
```php
class ResellerBalance extends Model
{
    protected $fillable = [
        'company_id',
        'reseller_id',
        'balance'
    ];
    
    protected $casts = [
        'balance' => 'decimal:2'
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }
    
    public function addBalance($amount)
    {
        $this->balance = bcadd($this->balance, $amount, 2);
        $this->save();
    }
    
    public function deductBalance($amount)
    {
        if (bccomp($this->balance, $amount, 2) < 0) {
            throw new InsufficientBalanceException('Insufficient balance');
        }
        
        $this->balance = bcsub($this->balance, $amount, 2);
        $this->save();
    }
    
    public function hasSufficientBalance($amount)
    {
        return bccomp($this->balance, $amount, 2) >= 0;
    }
}
```

### Reseller Employee Model
```php
class ResellerEmployee extends Model
{
    protected $fillable = [
        'company_id',
        'reseller_id',
        'employee_id',
        'balance'
    ];
    
    protected $casts = [
        'balance' => 'decimal:2'
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }
    
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
    
    public function addBalance($amount)
    {
        $this->balance = bcadd($this->balance, $amount, 2);
        $this->save();
    }
    
    public function deductBalance($amount)
    {
        if (bccomp($this->balance, $amount, 2) < 0) {
            throw new InsufficientBalanceException('Insufficient balance');
        }
        
        $this->balance = bcsub($this->balance, $amount, 2);
        $this->save();
    }
}
```

### Balance Service
```php
class BalanceService
{
    public function getResellerBalance($reseller)
    {
        $balance = ResellerBalance::firstOrCreate([
            'company_id' => $reseller->company_id,
            'reseller_id' => $reseller->id
        ], [
            'balance' => 0.00
        ]);
        
        return $balance;
    }
    
    public function getEmployeeBalance($employee)
    {
        $balance = ResellerEmployee::firstOrCreate([
            'company_id' => $employee->company_id,
            'reseller_id' => $employee->reseller_id,
            'employee_id' => $employee->id
        ], [
            'balance' => 0.00
        ]);
        
        return $balance;
    }
    
    public function addResellerBalance($reseller, $amount, $notes = '')
    {
        $balance = $this->getResellerBalance($reseller);
        $balance->addBalance($amount);
        
        // Log fund transfer
        $this->logFundTransfer($reseller->company_id, null, $reseller->id, 'from_admin_to_reseller', $amount, $notes);
        
        return $balance;
    }
    
    public function transferToEmployee($reseller, $employee, $amount, $notes = '')
    {
        // Check reseller has sufficient balance
        $resellerBalance = $this->getResellerBalance($reseller);
        
        if (!$resellerBalance->hasSufficientBalance($amount)) {
            throw new InsufficientBalanceException('Reseller has insufficient balance');
        }
        
        // Deduct from reseller
        $resellerBalance->deductBalance($amount);
        
        // Add to employee
        $employeeBalance = $this->getEmployeeBalance($employee);
        $employeeBalance->addBalance($amount);
        
        // Log fund transfer
        $this->logFundTransfer($reseller->company_id, $reseller->id, $employee->id, 'reseller_to_employee', $amount, $notes);
        
        return [
            'reseller_balance' => $resellerBalance,
            'employee_balance' => $employeeBalance
        ];
    }
    
    public function validateBalanceForRecharge($user, $amount)
    {
        // For resellers, check their balance
        if ($user->hasRole('reseller')) {
            $balance = $this->getResellerBalance($user);
            return $balance->hasSufficientBalance($amount);
        }
        
        // For reseller employees, check their balance
        if ($user->hasRole('reseller_employee')) {
            $balance = $this->getEmployeeBalance($user);
            return $balance->hasSufficientBalance($amount);
        }
        
        // For other users (admin, etc.), no balance check needed
        return true;
    }
    
    public function deductBalanceForRecharge($user, $amount, $notes = '')
    {
        // For resellers, deduct from their balance
        if ($user->hasRole('reseller')) {
            $balance = $this->getResellerBalance($user);
            $balance->deductBalance($amount);
            
            // Log fund transfer
            $this->logFundTransfer($user->company_id, $user->id, null, 'reseller_recharge', $amount, $notes);
            
            return $balance;
        }
        
        // For reseller employees, deduct from their balance
        if ($user->hasRole('reseller_employee')) {
            $balance = $this->getEmployeeBalance($user);
            $balance->deductBalance($amount);
            
            // Log fund transfer
            $this->logFundTransfer($user->company_id, $user->id, null, 'employee_recharge', $amount, $notes);
            
            return $balance;
        }
        
        // For other users, no balance deduction needed
        return null;
    }
}
```

## Commission Calculation System

### Reseller Commission Model
```php
class ResellerCommission extends Model
{
    protected $fillable = [
        'company_id',
        'reseller_id',
        'customer_id',
        'invoice_id',
        'base_amount',
        'commission_percent',
        'commission_amount',
        'status',
        'paid_at'
    ];
    
    protected $casts = [
        'base_amount' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'paid_at' => 'datetime'
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
```

### Commission Service
```php
class CommissionService
{
    public function calculateCommission($reseller, $invoice)
    {
        // Get reseller commission percentage
        $commissionPercent = $reseller->commission_percent ?? 0;
        
        if ($commissionPercent <= 0) {
            return null;
        }
        
        // Calculate commission on base price (excluding VAT)
        $baseAmount = $invoice->base_price;
        $commissionAmount = bcmul($baseAmount, bcdiv($commissionPercent, 100, 4), 2);
        
        // Create commission record
        $commission = ResellerCommission::create([
            'company_id' => $invoice->company_id,
            'reseller_id' => $reseller->id,
            'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id,
            'base_amount' => $baseAmount,
            'commission_percent' => $commissionPercent,
            'commission_amount' => $commissionAmount,
            'status' => 'pending'
        ]);
        
        return $commission;
    }
    
    public function payoutCommission($commission, $immediate = false)
    {
        if ($commission->status !== 'pending') {
            throw new Exception('Commission is not in pending status');
        }
        
        if ($immediate) {
            // Add commission to reseller's balance immediately
            $balanceService = new BalanceService();
            $balanceService->addResellerBalance($commission->reseller, $commission->commission_amount, 'Commission payout');
            
            $commission->status = 'paid';
            $commission->paid_at = now();
            $commission->save();
        } else {
            // Mark as ready for payout (will be processed in batch)
            $commission->status = 'ready_for_payout';
            $commission->save();
        }
        
        return $commission;
    }
    
    public function batchPayoutCommissions($reseller)
    {
        $pendingCommissions = ResellerCommission::where('reseller_id', $reseller->id)
            ->where('status', 'ready_for_payout')
            ->get();
        
        $totalAmount = 0;
        
        foreach ($pendingCommissions as $commission) {
            $totalAmount = bcadd($totalAmount, $commission->commission_amount, 2);
        }
        
        if ($totalAmount > 0) {
            // Add total commission to reseller's balance
            $balanceService = new BalanceService();
            $balanceService->addResellerBalance($reseller, $totalAmount, 'Batch commission payout');
            
            // Update all commissions as paid
            ResellerCommission::where('reseller_id', $reseller->id)
                ->where('status', 'ready_for_payout')
                ->update([
                    'status' => 'paid',
                    'paid_at' => now()
                ]);
        }
        
        return $totalAmount;
    }
    
    public function getCommissionReport($reseller, $startDate, $endDate)
    {
        return ResellerCommission::where('reseller_id', $reseller->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_commissions,
                SUM(commission_amount) as total_earned,
                SUM(CASE WHEN status = "pending" THEN commission_amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = "paid" THEN commission_amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status = "ready_for_payout" THEN commission_amount ELSE 0 END) as ready_for_payout_amount
            ')
            ->first();
    }
}
```

## Fund Transfer System

### Fund Transfer Model
```php
class FundTransfer extends Model
{
    protected $fillable = [
        'company_id',
        'from_user_id',
        'to_user_id',
        'transfer_type',
        'amount',
        'notes'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2'
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }
    
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
```

### Fund Transfer Service
```php
class FundTransferService
{
    public function logFundTransfer($companyId, $fromUserId, $toUserId, $transferType, $amount, $notes = '')
    {
        return FundTransfer::create([
            'company_id' => $companyId,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'transfer_type' => $transferType,
            'amount' => $amount,
            'notes' => $notes
        ]);
    }
    
    public function getTransferHistory($user, $limit = 50)
    {
        return FundTransfer::where('company_id', $user->company_id)
            ->where(function ($query) use ($user) {
                $query->where('from_user_id', $user->id)
                    ->orWhere('to_user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    public function getTransferReport($company, $startDate, $endDate)
    {
        return FundTransfer::where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                transfer_type,
                COUNT(*) as count,
                SUM(amount) as total_amount
            ')
            ->groupBy('transfer_type')
            ->get();
    }
}
```

## Integration with Billing System

### Payment Processing with Commission
```php
class PaymentService
{
    public function processPayment($customer, $amount, $paymentMethod, $operator = null)
    {
        // Process the payment
        $payment = Payment::create([
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'operator_id' => $operator ? $operator->id : null,
            'payment_date' => now(),
            'status' => 'paid'
        ]);
        
        // If customer has a reseller, calculate commission
        if ($customer->reseller_id) {
            $reseller = User::find($customer->reseller_id);
            
            if ($reseller) {
                // Create invoice for the payment
                $invoice = Invoice::create([
                    'company_id' => $customer->company_id,
                    'customer_id' => $customer->id,
                    'base_price' => $amount,
                    'vat_amount' => 0, // Assuming VAT is handled separately
                    'total_amount' => $amount,
                    'status' => 'paid',
                    'payment_date' => now()
                ]);
                
                // Calculate and record reseller commission
                $commissionService = new CommissionService();
                $commission = $commissionService->calculateCommission($reseller, $invoice);
                
                // Optionally payout commission immediately
                if ($commission) {
                    $commissionService->payoutCommission($commission, true);
                }
            }
        }
        
        // If payment was made by reseller/employee, deduct from their balance
        if ($operator && in_array($operator->user_type, ['reseller', 'reseller_employee'])) {
            $balanceService = new BalanceService();
            
            try {
                $balanceService->deductBalanceForRecharge($operator, $amount, 'Customer payment');
            } catch (InsufficientBalanceException $e) {
                // Log warning but don't stop payment processing
                Log::warning('Insufficient balance for reseller/employee during payment', [
                    'operator_id' => $operator->id,
                    'amount' => $amount,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $payment;
    }
}
```

## Reporting System

### Commission Reports
```php
class CommissionReportService
{
    public function getResellerCommissionSummary($reseller, $period = 'monthly')
    {
        $query = ResellerCommission::where('reseller_id', $reseller->id);
        
        switch ($period) {
            case 'daily':
                $query->selectRaw('
                    DATE(created_at) as period,
                    COUNT(*) as total_commissions,
                    SUM(commission_amount) as total_earned
                ')
                ->groupBy('period')
                ->orderBy('period');
                break;
                
            case 'monthly':
                $query->selectRaw('
                    DATE_FORMAT(created_at, "%Y-%m") as period,
                    COUNT(*) as total_commissions,
                    SUM(commission_amount) as total_earned
                ')
                ->groupBy('period')
                ->orderBy('period');
                break;
                
            case 'yearly':
                $query->selectRaw('
                    YEAR(created_at) as period,
                    COUNT(*) as total_commissions,
                    SUM(commission_amount) as total_earned
                ')
                ->groupBy('period')
                ->orderBy('period');
                break;
        }
        
        return $query->get();
    }
    
    public function getResellerBalanceHistory($reseller, $limit = 30)
    {
        return FundTransfer::where('to_user_id', $reseller->id)
            ->where('transfer_type', 'from_admin_to_reseller')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
```

## Security Considerations

### Access Control
```php
class ResellerPolicy
{
    public function manageBalance(User $user, Reseller $reseller)
    {
        // Only super admin and company admin can manage reseller balances
        if ($user->hasRole('super_admin') || $user->hasRole('company_admin')) {
            return $user->company_id === $reseller->company_id;
        }
        
        return false;
    }
    
    public function viewCommissions(User $user, Reseller $reseller)
    {
        // Reseller can view their own commissions
        // Admins can view all commissions
        if ($user->id === $reseller->id) {
            return true;
        }
        
        if ($user->hasRole('super_admin') || $user->hasRole('company_admin')) {
            return $user->company_id === $reseller->company_id;
        }
        
        return false;
    }
}
```

## Error Handling

### Custom Exceptions
```php
class InsufficientBalanceException extends Exception
{
    // Custom exception for insufficient balance scenarios
}

class InvalidTransferException extends Exception
{
    // Custom exception for invalid fund transfers
}

class CommissionCalculationException extends Exception
{
    // Custom exception for commission calculation errors
}
```

## Testing Strategy

### Unit Tests
```php
class ResellerBalanceTest extends TestCase
{
    public function test_add_balance()
    {
        $reseller = User::factory()->create(['user_type' => 'reseller']);
        $balanceService = new BalanceService();
        
        $balance = $balanceService->getResellerBalance($reseller);
        $this->assertEquals(0.00, $balance->balance);
        
        $balanceService->addResellerBalance($reseller, 100.00);
        
        $balance = $balanceService->getResellerBalance($reseller);
        $this->assertEquals(100.00, $balance->balance);
    }
    
    public function test_deduct_balance()
    {
        $reseller = User::factory()->create(['user_type' => 'reseller']);
        $balanceService = new BalanceService();
        
        $balanceService->addResellerBalance($reseller, 100.00);
        
        $balance = $balanceService->getResellerBalance($reseller);
        $balance->deductBalance(50.00);
        
        $balance = $balanceService->getResellerBalance($reseller);
        $this->assertEquals(50.00, $balance->balance);
    }
    
    public function test_insufficient_balance()
    {
        $this->expectException(InsufficientBalanceException::class);
        
        $reseller = User::factory()->create(['user_type' => 'reseller']);
        $balanceService = new BalanceService();
        
        $balance = $balanceService->getResellerBalance($reseller);
        $balance->deductBalance(50.00); // Should throw exception
    }
}
```

This comprehensive reseller balance and commission system plan provides a solid foundation for implementing the financial aspects of the reseller functionality in the ISP Billing & CRM system.