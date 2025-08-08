<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends TenantModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
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
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'billing_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'datetime',
        'base_price' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'billing_date',
        'due_date',
        'payment_date',
        'deleted_at',
    ];

    /**
     * Get the company that owns the invoice.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the customer for the invoice.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the payments for the invoice.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the reseller commission for this invoice.
     */
    public function commission()
    {
        return $this->hasOne(ResellerCommission::class);
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is unpaid.
     */
    public function isUnpaid()
    {
        return $this->status === 'unpaid';
    }

    /**
     * Check if invoice is partially paid.
     */
    public function isPartiallyPaid()
    {
        return $this->status === 'partial';
    }

    /**
     * Check if invoice is cancelled.
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get the total amount paid for this invoice.
     */
    public function totalPaid()
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Get the remaining balance for this invoice.
     */
    public function remainingBalance()
    {
        return $this->total_amount - $this->totalPaid();
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isPaid();
    }
}