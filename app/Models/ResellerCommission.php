<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResellerCommission extends TenantModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'reseller_id',
        'customer_id',
        'invoice_id',
        'base_amount',
        'commission_percent',
        'commission_amount',
        'status',
        'paid_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'base_amount' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'paid_at',
        'deleted_at',
    ];

    /**
     * Get the company that owns the commission.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the reseller for this commission.
     */
    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    /**
     * Get the customer for this commission.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the invoice for this commission.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the payment associated with this commission.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Check if commission is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if commission is paid.
     */
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    /**
     * Check if commission is ready for payout.
     */
    public function isReadyForPayout()
    {
        return $this->status === 'ready_for_payout';
    }

    /**
     * Mark commission as paid.
     */
    public function markAsPaid()
    {
        $this->status = 'paid';
        $this->paid_at = now();
        $this->save();
    }

    /**
     * Mark commission as ready for payout.
     */
    public function markAsReadyForPayout()
    {
        $this->status = 'ready_for_payout';
        $this->save();
    }
}