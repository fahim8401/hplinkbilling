<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SMSTemplate extends TenantModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'gateway_id',
        'name',
        'template',
        'variables',
        'is_active',
        'category',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at',
    ];

    /**
     * Get the company that owns the SMS template.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the gateway for this template.
     */
    public function gateway()
    {
        return $this->belongsTo(SMSGateway::class);
    }

    /**
     * Get the SMS logs for this template.
     */
    public function logs()
    {
        return $this->hasMany(SMSLog::class);
    }

    /**
     * Check if template is active.
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * Render the template with variables.
     */
    public function render($variables = [])
    {
        $message = $this->template;
        
        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        return $message;
    }

    /**
     * Get the available variables for this template.
     */
    public function getAvailableVariables()
    {
        $variables = [
            'general' => ['name', 'phone', 'email'],
            'billing' => ['name', 'phone', 'email', 'due_amount', 'due_date', 'invoice_number'],
            'payment' => ['name', 'phone', 'email', 'amount', 'payment_date', 'transaction_id'],
            'expiry' => ['name', 'phone', 'email', 'package_name', 'expiry_date'],
            'suspension' => ['name', 'phone', 'email', 'package_name', 'suspend_date']
        ];

        return $variables[$this->category] ?? $variables['general'];
    }
}