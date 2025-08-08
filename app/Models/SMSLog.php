<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SMSLog extends TenantModel
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
        'gateway_id',
        'template_id',
        'phone_number',
        'message',
        'status',
        'response',
        'sent_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'sent_at',
        'deleted_at',
    ];

    /**
     * Get the company that owns the SMS log.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the customer for this SMS.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the gateway used for this SMS.
     */
    public function gateway()
    {
        return $this->belongsTo(SMSGateway::class);
    }

    /**
     * Get the template used for this SMS.
     */
    public function template()
    {
        return $this->belongsTo(SMSTemplate::class);
    }

    /**
     * Check if SMS was sent successfully.
     */
    public function isSent()
    {
        return $this->status === 'sent';
    }

    /**
     * Check if SMS failed to send.
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Check if SMS is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Scope a query to only include successful SMS logs.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope a query to only include failed SMS logs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include pending SMS logs.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}