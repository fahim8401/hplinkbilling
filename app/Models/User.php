<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends TenantModel implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'username',
        'password',
        'user_type',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'email_verified_at',
    ];

    /**
     * Get the company that owns the user.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if user is super admin.
     */
    public function isSuperAdmin()
    {
        return $this->user_type === 'super_admin';
    }

    /**
     * Check if user is company admin.
     */
    public function isCompanyAdmin()
    {
        return $this->user_type === 'company_admin';
    }

    /**
     * Check if user is reseller.
     */
    public function isReseller()
    {
        return $this->user_type === 'reseller';
    }

    /**
     * Check if user is customer.
     */
    public function isCustomer()
    {
        return $this->user_type === 'customer';
    }

    /**
     * Check if user is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is suspended.
     */
    public function isSuspended()
    {
        return $this->status === 'suspended';
    }

    /**
     * Get the reseller's customers.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'reseller_id');
    }

    /**
     * Get the reseller's balance.
     */
    public function balance()
    {
        return $this->hasOne(ResellerBalance::class, 'reseller_id');
    }

    /**
     * Get the reseller's employees.
     */
    public function employees()
    {
        return $this->hasMany(User::class, 'reseller_id')->where('user_type', 'reseller_employee');
    }
}