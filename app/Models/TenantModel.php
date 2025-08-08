<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Facades\Tenancy;

class TenantModel extends Model
{
    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-assign company_id on create
        static::creating(function ($model) {
            if (Tenancy::isInitialized() && !Tenancy::isSuperAdmin()) {
                $model->company_id = Tenancy::company()->id;
            }
        });

        // Ensure company_id matches tenant on update
        static::updating(function ($model) {
            if (Tenancy::isInitialized() && !Tenancy::isSuperAdmin()) {
                if ($model->company_id !== Tenancy::company()->id) {
                    throw new \Exception('Cannot modify data from another tenant');
                }
            }
        });
    }
}