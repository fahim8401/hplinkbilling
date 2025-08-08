<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerUsage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'usage_date',
        'download_bytes',
        'upload_bytes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'usage_date' => 'date',
        'download_bytes' => 'integer',
        'upload_bytes' => 'integer',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'usage_date',
    ];

    /**
     * Get the customer for this usage record.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the total data usage (download + upload) in bytes.
     */
    public function totalBytes()
    {
        return $this->download_bytes + $this->upload_bytes;
    }

    /**
     * Scope a query to only include usage for a specific month.
     */
    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('usage_date', $year)
                    ->whereMonth('usage_date', $month);
    }

    /**
     * Scope a query to only include usage for a specific date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('usage_date', [$startDate, $endDate]);
    }

    /**
     * Get the download speed in Mbps.
     */
    public function downloadSpeedMbps()
    {
        // Assuming this is daily usage, convert bytes to Mbps
        // 1 byte = 8 bits, 1 day = 86400 seconds
        return ($this->download_bytes * 8) / (86400 * 1000000);
    }

    /**
     * Get the upload speed in Mbps.
     */
    public function uploadSpeedMbps()
    {
        // Assuming this is daily usage, convert bytes to Mbps
        // 1 byte = 8 bits, 1 day = 86400 seconds
        return ($this->upload_bytes * 8) / (86400 * 1000000);
    }
}