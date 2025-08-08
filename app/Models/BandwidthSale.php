<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BandwidthSale extends TenantModel
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
        'reseller_id',
        'bandwidth',
        'price',
        'sale_date',
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
        'sale_date' => 'date',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'sale_date',
        'deleted_at',
    ];

    /**
     * Get the company that owns the bandwidth sale.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the customer for this sale.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the reseller for this sale.
     */
    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    /**
     * Get the purchase record for this sale.
     */
    public function purchase()
    {
        return $this->belongsTo(BandwidthPurchase::class);
    }

    /**
     * Get the profit from this sale.
     */
    public function profit()
    {
        // This would need to be calculated based on the purchase cost
        // For now, we'll return the price as a placeholder
        return $this->price;
    }

    /**
     * Get the price per Mbps.
     */
    public function pricePerMbps()
    {
        if ($this->bandwidth == 0) {
            return 0;
        }

        return $this->price / $this->bandwidth;
    }
}