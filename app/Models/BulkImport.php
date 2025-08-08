<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulkImport extends TenantModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'file_name',
        'file_path',
        'total_records',
        'success_records',
        'failed_records',
        'status',
        'error_log',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'total_records' => 'integer',
        'success_records' => 'integer',
        'failed_records' => 'integer',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'completed_at',
        'deleted_at',
    ];

    /**
     * Get the company that owns the bulk import.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who initiated the bulk import.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if import is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if import is processing.
     */
    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    /**
     * Check if import is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if import failed.
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Get the success rate percentage.
     */
    public function successRate()
    {
        if ($this->total_records == 0) {
            return 0;
        }

        return ($this->success_records / $this->total_records) * 100;
    }

    /**
     * Get the failure rate percentage.
     */
    public function failureRate()
    {
        if ($this->total_records == 0) {
            return 0;
        }

        return ($this->failed_records / $this->total_records) * 100;
    }

    /**
     * Mark import as completed.
     */
    public function markAsCompleted()
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->save();
    }

    /**
     * Mark import as failed.
     */
    public function markAsFailed($errorLog = null)
    {
        $this->status = 'failed';
        $this->error_log = $errorLog;
        $this->completed_at = now();
        $this->save();
    }
}