<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'action',
        'description',
    ];

    /**
     * Get the ticket for this log entry.
     */
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include logs for a specific action.
     */
    public function scopeForAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include logs for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the formatted action description.
     */
    public function getFormattedActionAttribute()
    {
        $actions = [
            'created' => 'Ticket Created',
            'assigned' => 'Ticket Assigned',
            'status_changed' => 'Status Changed',
            'priority_changed' => 'Priority Changed',
            'category_changed' => 'Category Changed',
            'comment_added' => 'Comment Added',
            'attachment_added' => 'Attachment Added',
            'closed' => 'Ticket Closed',
            'reopened' => 'Ticket Reopened',
        ];

        return $actions[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }
}