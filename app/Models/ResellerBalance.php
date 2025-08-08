<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerBalance extends TenantModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'reseller_id',
        'balance',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'balance' => 'decimal:2',
    ];

    /**
     * Get the company that owns the balance.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the reseller for this balance.
     */
    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    /**
     * Add amount to balance.
     */
    public function addBalance($amount)
    {
        $this->balance = bcadd($this->balance, $amount, 2);
        $this->save();
    }

    /**
     * Deduct amount from balance.
     */
    public function deductBalance($amount)
    {
        if (bccomp($this->balance, $amount, 2) < 0) {
            throw new \Exception('Insufficient balance');
        }

        $this->balance = bcsub($this->balance, $amount, 2);
        $this->save();
    }

    /**
     * Check if has sufficient balance.
     */
    public function hasSufficientBalance($amount)
    {
        return bccomp($this->balance, $amount, 2) >= 0;
    }
}