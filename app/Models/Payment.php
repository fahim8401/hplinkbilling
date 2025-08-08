<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends TenantModel
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
        'invoice_id',
        'amount',
        'payment_method',
        'payment_gateway',
        'operator_id',
        'transaction_id',
        'payment_date',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'payment_date',
        'deleted_at',
    ];

    /**
     * Get the company that owns the payment.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the customer for the payment.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the invoice for the payment.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the operator who processed the payment.
     */
    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Check if payment is received (paid).
     */
    public function isReceived()
    {
        return $this->payment_method === 'receive';
    }

    /**
     * Check if payment is due (unpaid extension).
     */
    public function isDue()
    {
        return $this->payment_method === 'due';
    }

    /**
     * Check if payment is online.
     */
    public function isOnline()
    {
        return $this->payment_method === 'online';
    }

    /**
     * Get the reseller commission associated with this payment.
     */
    public function commission()
    {
        return $this->hasOne(ResellerCommission::class);
    }
}