<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGatewayTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'payment_id',
        'gateway',
        'transaction_type',
        'customer_id_gateway',
        'amount',
        'mobile_no',
        'transaction_id',
        'datetime',
        'error_code',
        'error_message',
        'result',
        'name',
        'contact',
        'bill_amount',
        'paid_amount',
        'trx_id',
        'raw_response'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}