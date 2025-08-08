<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends TenantModel
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
        'phone',
        'email',
        'username',
        'password',
        'nid',
        'ip_address',
        'mac_address',
        'package_id',
        'pop_id',
        'router_id',
        'reseller_id',
        'customer_type',
        'status',
        'activation_date',
        'expiry_date',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'activation_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'activation_date',
        'expiry_date',
        'deleted_at',
    ];

    /**
     * Get the company that owns the customer.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the package for the customer.
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the POP for the customer.
     */
    public function pop()
    {
        return $this->belongsTo(POP::class);
    }

    /**
     * Get the router for the customer.
     */
    public function router()
    {
        return $this->belongsTo(MikrotikRouter::class, 'router_id');
    }

    /**
     * Get the reseller for the customer.
     */
    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    /**
     * Get the invoices for the customer.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the payments for the customer.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the sessions for the customer.
     */
    public function sessions()
    {
        return $this->hasMany(CustomerSession::class);
    }

    /**
     * Get the usage records for the customer.
     */
    public function usage()
    {
        return $this->hasMany(CustomerUsage::class);
    }

    /**
     * Get the support tickets for the customer.
     */
    public function tickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * Check if customer is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if customer is suspended.
     */
    public function isSuspended()
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if customer is expired.
     */
    public function isExpired()
    {
        return $this->status === 'expired';
    }

    /**
     * Check if customer is deleted.
     */
    public function isDeleted()
    {
        return $this->status === 'deleted';
    }

    /**
     * Check if customer is a free customer.
     */
    public function isFree()
    {
        return $this->customer_type === 'free';
    }

    /**
     * Check if customer is a VIP customer.
     */
    public function isVIP()
    {
        return $this->customer_type === 'vip';
    }

    /**
     * Check if customer is a home customer.
     */
    public function isHome()
    {
        return $this->customer_type === 'home';
    }

    /**
     * Check if customer is a corporate customer.
     */
    public function isCorporate()
    {
        return $this->customer_type === 'corporate';
    }

    /**
     * Check if customer has expired.
     */
    public function hasExpired()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Get the days until expiration.
     */
    public function daysUntilExpiration()
    {
        if (!$this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }
}