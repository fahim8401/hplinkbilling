<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BandwidthPurchase extends TenantModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'provider',
        'bandwidth',
        'price',
        'purchase_date',
        'expiry_date',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'bandwidth' => 'integer',
        'price' => 'decimal:2',
        'purchase_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'purchase_date',
        'expiry_date',
        'deleted_at',
    ];

    /**
     * Get the company that owns the bandwidth purchase.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the sales records for this purchased bandwidth.
     */
    public function sales()
    {
        return $this->hasMany(BandwidthSale::class);
    }

    /**
     * Check if the purchase is expired.
     */
    public function isExpired()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if the purchase is active.
     */
    public function isActive()
    {
        return !$this->isExpired() && (!$this->expiry_date || $this->expiry_date->isFuture());
    }

    /**
     * Get the total bandwidth sold.
     */
    public function totalSold()
    {
        return $this->sales()->sum('bandwidth');
    }

    /**
     * Get the remaining bandwidth.
     */
    public function remainingBandwidth()
    {
        return $this->bandwidth - $this->totalSold();
    }

    /**
     * Check if bandwidth is fully sold.
     */
    public function isFullySold()
    {
        return $this->remainingBandwidth() <= 0;
    }

    /**
     * Get the total revenue from sales.
     */
    public function totalRevenue()
    {
        return $this->sales()->sum('price');
    }

    /**
     * Get the cost per Mbps.
     */
    public function costPerMbps()
    {
        if ($this->bandwidth == 0) {
            return 0;
        }

        return $this->price / $this->bandwidth;
    }
}