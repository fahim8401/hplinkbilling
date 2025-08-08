<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'domain',
        'subdomain',
        'status',
        'billing_day',
        'vat_percent',
        'currency',
        'timezone',
        'logo_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'billing_day' => 'integer',
        'vat_percent' => 'decimal:2',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the users for the company.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the customers for the company.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the packages for the company.
     */
    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    /**
     * Get the settings for the company.
     */
    public function settings()
    {
        return $this->hasMany(CompanySetting::class);
    }

    /**
     * Check if the company is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if the company uses subdomain-based identification.
     *
     * @return bool
     */
    public function isSubdomainBased()
    {
        return !empty($this->subdomain) && empty($this->domain);
    }
}