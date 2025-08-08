<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_id',
        'amount',
        'payment_method',
        'payment_gateway',
        'payment_gateway_transaction_id',
        'operator_id',
        'transaction_id',
        'gateway_transaction_id',
        'gateway_customer_id',
        'gateway_mobile_no',
        'gateway_datetime',
        'payment_date',
        'notes',
        'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}