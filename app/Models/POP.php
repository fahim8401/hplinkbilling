<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class POP extends TenantModel
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
        'address',
        'contact_person',
        'contact_phone',
        'status',
    ];

    /**
     * Get the company that owns the POP.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the routers at this POP.
     */
    public function routers()
    {
        return $this->hasMany(MikrotikRouter::class, 'pop_id');
    }

    /**
     * Get the customers at this POP.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'pop_id');
    }

    /**
     * Get the resellers assigned to this POP.
     */
    public function resellers()
    {
        return $this->belongsToMany(User::class, 'pop_reseller', 'pop_id', 'reseller_id')
                    ->where('user_type', 'reseller');
    }

    /**
     * Check if POP is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if POP is inactive.
     */
    public function isInactive()
    {
        return $this->status === 'inactive';
    }

    /**
     * Get the count of online customers at this POP.
     */
    public function onlineCustomerCount()
    {
        return $this->customers()->where('status', 'active')->count();
    }

    /**
     * Get the count of offline customers at this POP.
     */
    public function offlineCustomerCount()
    {
        return $this->customers()->where('status', 'suspended')->count();
    }

    /**
     * Get the count of expired customers at this POP.
     */
    public function expiredCustomerCount()
    {
        return $this->customers()->where('status', 'expired')->count();
    }

    /**
     * Get the count of disabled customers at this POP.
     */
    public function disabledCustomerCount()
    {
        return $this->customers()->where('status', 'disabled')->count();
    }
}