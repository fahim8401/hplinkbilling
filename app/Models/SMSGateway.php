<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SMSGateway extends TenantModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'name',
        'gateway_url',
        'http_method',
        'params',
        'headers',
        'is_active',
        'balance',
        'default_sender_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'params' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean',
        'balance' => 'decimal:2',
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
     * Get the company that owns the SMS gateway.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the SMS templates for this gateway.
     */
    public function templates()
    {
        return $this->hasMany(SMSTemplate::class);
    }

    /**
     * Get the SMS logs for this gateway.
     */
    public function logs()
    {
        return $this->hasMany(SMSLog::class);
    }

    /**
     * Check if gateway is active.
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * Check if gateway has sufficient balance.
     */
    public function hasSufficientBalance($cost)
    {
        return $this->balance >= $cost;
    }

    /**
     * Deduct balance from gateway.
     */
    public function deductBalance($amount)
    {
        $this->balance -= $amount;
        $this->save();
    }
}