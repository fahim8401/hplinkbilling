<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends TenantModel
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
        'assigned_to',
        'category',
        'subject',
        'description',
        'priority',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        //
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
     * Get the company that owns the ticket.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the customer for this ticket.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user assigned to this ticket.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the tokens for this ticket.
     */
    public function tokens()
    {
        return $this->hasMany(SupportToken::class);
    }

    /**
     * Get the attachments for this ticket.
     */
    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /**
     * Get the logs for this ticket.
     */
    public function logs()
    {
        return $this->hasMany(TicketLog::class);
    }

    /**
     * Check if ticket is open.
     */
    public function isOpen()
    {
        return $this->status === 'open';
    }

    /**
     * Check if ticket is in progress.
     */
    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if ticket is resolved.
     */
    public function isResolved()
    {
        return $this->status === 'resolved';
    }

    /**
     * Check if ticket is closed.
     */
    public function isClosed()
    {
        return $this->status === 'closed';
    }

    /**
     * Check if ticket is high priority.
     */
    public function isHighPriority()
    {
        return $this->priority === 'high' || $this->priority === 'urgent';
    }

    /**
     * Check if ticket is urgent priority.
     */
    public function isUrgentPriority()
    {
        return $this->priority === 'urgent';
    }
}