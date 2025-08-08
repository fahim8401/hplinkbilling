<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends TenantModel
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
        'speed',
        'price',
        'vat_percent',
        'fup_limit',
        'duration',
        'is_expired_package',
        'mikrotik_profile_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'decimal:2',
        'vat_percent' => 'decimal:2',
        'fup_limit' => 'integer',
        'duration' => 'integer',
        'is_expired_package' => 'boolean',
    ];

    /**
     * Get the company that owns the package.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the MikroTik profile for the package.
     */
    public function mikrotikProfile()
    {
        return $this->belongsTo(MikrotikProfile::class);
    }

    /**
     * Get the customers with this package.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the resellers assigned to this package.
     */
    public function resellers()
    {
        return $this->belongsToMany(User::class, 'package_reseller', 'package_id', 'reseller_id')
                    ->where('user_type', 'reseller');
    }

    /**
     * Check if this is the expired package.
     */
    public function isExpiredPackage()
    {
        return $this->is_expired_package;
    }

    /**
     * Get the total price including VAT.
     */
    public function totalPrice()
    {
        $vatAmount = $this->price * ($this->vat_percent / 100);
        return $this->price + $vatAmount;
    }

    /**
     * Get the VAT amount.
     */
    public function vatAmount()
    {
        return $this->price * ($this->vat_percent / 100);
    }
}