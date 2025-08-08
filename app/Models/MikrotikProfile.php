<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikrotikProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'router_id',
        'profile_name',
        'profile_id',
    ];

    /**
     * Get the router that owns the profile.
     */
    public function router()
    {
        return $this->belongsTo(MikrotikRouter::class);
    }

    /**
     * Get the packages using this profile.
     */
    public function packages()
    {
        return $this->hasMany(Package::class, 'mikrotik_profile_id');
    }
}