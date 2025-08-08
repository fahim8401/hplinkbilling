<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MikrotikRouter extends TenantModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'pop_id',
        'name',
        'ip_address',
        'port',
        'username',
        'password',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'port' => 'integer',
        'password' => 'encrypted',
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
     * Get the company that owns the router.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the POP for the router.
     */
    public function pop()
    {
        return $this->belongsTo(POP::class);
    }

    /**
     * Get the profiles for the router.
     */
    public function profiles()
    {
        return $this->hasMany(MikrotikProfile::class);
    }

    /**
     * Get the customers on this router.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'router_id');
    }

    /**
     * Check if router is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if router is inactive.
     */
    public function isInactive()
    {
        return $this->status === 'inactive';
    }
}