<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class FundTransfer extends TenantModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'from_user_id',
        'to_user_id',
        'transfer_type',
        'amount',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at',
    ];

    /**
     * Get the company that owns the fund transfer.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who sent the funds.
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Get the user who received the funds.
     */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * Check if transfer is from admin to reseller.
     */
    public function isFromAdminToReseller()
    {
        return $this->transfer_type === 'from_admin_to_reseller';
    }

    /**
     * Check if transfer is from reseller to employee.
     */
    public function isFromResellerToEmployee()
    {
        return $this->transfer_type === 'reseller_to_employee';
    }

    /**
     * Check if transfer is a reseller commission payout.
     */
    public function isResellerCommissionPayout()
    {
        return $this->transfer_type === 'reseller_commission_payouts';
    }

    /**
     * Check if transfer is a reseller recharge.
     */
    public function isResellerRecharge()
    {
        return $this->transfer_type === 'reseller_recharge';
    }

    /**
     * Check if transfer is an employee recharge.
     */
    public function isEmployeeRecharge()
    {
        return $this->transfer_type === 'employee_recharge';
    }
}