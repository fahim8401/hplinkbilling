<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'router_id',
        'session_id',
        'ip_address',
        'mac_address',
        'login_time',
        'logout_time',
        'download_bytes',
        'upload_bytes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'login_time' => 'datetime',
        'logout_time' => 'datetime',
        'download_bytes' => 'integer',
        'upload_bytes' => 'integer',
    ];

    /**
     * Get the customer for this session.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the router for this session.
     */
    public function router()
    {
        return $this->belongsTo(MikrotikRouter::class);
    }

    /**
     * Check if session is active.
     */
    public function isActive()
    {
        return $this->logout_time === null;
    }

    /**
     * Get the duration of the session in seconds.
     */
    public function duration()
    {
        if ($this->login_time && $this->logout_time) {
            return $this->logout_time->getTimestamp() - $this->login_time->getTimestamp();
        }

        return null;
    }

    /**
     * Get the total data usage (download + upload) in bytes.
     */
    public function totalBytes()
    {
        return $this->download_bytes + $this->upload_bytes;
    }

    /**
     * Get the download speed in bytes per second.
     */
    public function downloadSpeed()
    {
        $duration = $this->duration();
        if ($duration > 0) {
            return $this->download_bytes / $duration;
        }

        return 0;
    }

    /**
     * Get the upload speed in bytes per second.
     */
    public function uploadSpeed()
    {
        $duration = $this->duration();
        if ($duration > 0) {
            return $this->upload_bytes / $duration;
        }

        return 0;
    }
}