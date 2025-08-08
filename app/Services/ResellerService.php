<?php

namespace App\Services;

use App\Models\User;
use App\Models\ResellerBalance;
use App\Models\ResellerEmployee;
use App\Models\FundTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResellerService
{
    /**
     * Get or create a reseller balance record.
     *
     * @param User $reseller
     * @return ResellerBalance
     */
    public function getResellerBalance(User $reseller)
    {
        return ResellerBalance::firstOrCreate([
            'company_id' => $reseller->company_id,
            'reseller_id' => $reseller->id
        ], [
            'balance' => 0.00
        ]);
    }

    /**
     * Get or create a reseller employee balance record.
     *
     * @param User $employee
     * @return ResellerEmployee
     */
    public function getEmployeeBalance(User $employee)
    {
        return ResellerEmployee::firstOrCreate([
            'company_id' => $employee->company_id,
            'reseller_id' => $employee->reseller_id,
            'employee_id' => $employee->id
        ], [
            'balance' => 0.00
        ]);
    }

    /**
     * Add balance to a reseller.
     *
     * @param User $reseller
     * @param float $amount
     * @param string|null $notes
     * @return ResellerBalance
     */
    public function addResellerBalance(User $reseller, $amount, $notes = null)
    {
        $balance = $this->getResellerBalance($reseller);
        $balance->addBalance($amount);

        // Log the fund transfer
        $this->logFundTransfer($reseller->company_id, null, $reseller->id, 'from_admin_to_reseller', $amount, $notes);

        return $balance;
    }

    /**
     * Transfer balance from reseller to employee.
     *
     * @param User $reseller
     * @param User $employee
     * @param float $amount
     * @param string|null $notes
     * @return array
     * @throws \Exception
     */
    public function transferToEmployee(User $reseller, User $employee, $amount, $notes = null)
    {
        // Check if reseller has sufficient balance
        $resellerBalance = $this->getResellerBalance($reseller);
        
        if (!$resellerBalance->hasSufficientBalance($amount)) {
            throw new \Exception('Reseller has insufficient balance');
        }

        // Deduct from reseller
        $resellerBalance->deductBalance($amount);

        // Add to employee
        $employeeBalance = $this->getEmployeeBalance($employee);
        $employeeBalance->addBalance($amount);

        // Log the fund transfer
        $this->logFundTransfer($reseller->company_id, $reseller->id, $employee->id, 'reseller_to_employee', $amount, $notes);

        return [
            'reseller_balance' => $resellerBalance,
            'employee_balance' => $employeeBalance
        ];
    }

    /**
     * Validate if a user has sufficient balance for a recharge.
     *
     * @param User $user
     * @param float $amount
     * @return bool
     */
    public function validateBalanceForRecharge(User $user, $amount)
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

    /**
     * Deduct balance for a recharge operation.
     *
     * @param User $user
     * @param float $amount
     * @param string|null $notes
     * @return ResellerBalance|ResellerEmployee|null
     * @throws \Exception
     */
    public function deductBalanceForRecharge(User $user, $amount, $notes = null)
    {
        // For resellers, deduct from their balance
        if ($user->hasRole('reseller')) {
            $balance = $this->getResellerBalance($user);
            
            if (!$balance->hasSufficientBalance($amount)) {
                throw new \Exception('Reseller has insufficient balance');
            }
            
            $balance->deductBalance($amount);

            // Log the fund transfer
            $this->logFundTransfer($user->company_id, $user->id, null, 'reseller_recharge', $amount, $notes);

            return $balance;
        }

        // For reseller employees, deduct from their balance
        if ($user->hasRole('reseller_employee')) {
            $balance = $this->getEmployeeBalance($user);
            
            if (!$balance->hasSufficientBalance($amount)) {
                throw new \Exception('Employee has insufficient balance');
            }
            
            $balance->deductBalance($amount);

            // Log the fund transfer
            $this->logFundTransfer($user->company_id, $user->id, null, 'employee_recharge', $amount, $notes);

            return $balance;
        }

        // For other users, no balance deduction needed
        return null;
    }

    /**
     * Payout commission to a reseller.
     *
     * @param User $reseller
     * @param bool $immediate
     * @return void
     */
    public function payoutCommission(User $reseller, $immediate = false)
    {
        $commissions = $reseller->commissions()
            ->where('status', 'pending')
            ->get();

        $totalAmount = 0;

        foreach ($commissions as $commission) {
            $totalAmount += $commission->commission_amount;
        }

        if ($totalAmount > 0) {
            if ($immediate) {
                // Add commission to reseller's balance immediately
                $this->addResellerBalance($reseller, $totalAmount, 'Commission payout');
            }

            // Update all commissions as paid or ready for payout
            $status = $immediate ? 'paid' : 'ready_for_payout';
            $reseller->commissions()
                ->where('status', 'pending')
                ->update([
                    'status' => $status,
                    'paid_at' => $immediate ? now() : null
                ]);

            // Log the fund transfer
            $this->logFundTransfer($reseller->company_id, null, $reseller->id, 'reseller_commission_payouts', $totalAmount, 'Commission payout');
        }
    }

    /**
     * Log a fund transfer.
     *
     * @param int $companyId
     * @param int|null $fromUserId
     * @param int|null $toUserId
     * @param string $transferType
     * @param float $amount
     * @param string|null $notes
     * @return FundTransfer
     */
    public function logFundTransfer($companyId, $fromUserId, $toUserId, $transferType, $amount, $notes = null)
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

    /**
     * Get fund transfer history for a user.
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTransferHistory(User $user, $limit = 50)
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
}