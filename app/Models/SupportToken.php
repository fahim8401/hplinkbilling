<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportToken extends TenantModel
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
        'ticket_id',
        'token_number',
        'category',
        'assigned_to',
        'status',
        'printed',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'printed' => 'boolean',
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
     * Get the company that owns the token.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the customer for this token.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the ticket for this token.
     */
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class);
    }

    /**
     * Get the user assigned to this token.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Check if token is open.
     */
    public function isOpen()
    {
        return $this->status === 'open';
    }

    /**
     * Check if token is in progress.
     */
    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if token is resolved.
     */
    public function isResolved()
    {
        return $this->status === 'resolved';
    }

    /**
     * Check if token is closed.
     */
    public function isClosed()
    {
        return $this->status === 'closed';
    }

    /**
     * Check if token has been printed.
     */
    public function isPrinted()
    {
        return $this->printed;
    }

    /**
     * Mark token as printed.
     */
    public function markAsPrinted()
    {
        $this->printed = true;
        $this->save();
    }

    /**
     * Generate a unique token number.
     */
    public static function generateTokenNumber($companyId)
    {
        $date = now()->format('Ymd');
        $lastToken = self::where('company_id', $companyId)
                        ->whereDate('created_at', now()->toDateString())
                        ->orderBy('id', 'desc')
                        ->first();

        $sequence = $lastToken ? intval(substr($lastToken->token_number, -4)) + 1 : 1;
        return $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}