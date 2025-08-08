<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ticket_id',
        'file_name',
        'file_path',
        'file_size',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Get the ticket for this attachment.
     */
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class);
    }

    /**
     * Get the full URL to the attachment.
     */
    public function getUrlAttribute()
    {
        return url('storage/' . $this->file_path);
    }

    /**
     * Get the file size in a human-readable format.
     */
    public function getFormattedSizeAttribute()
    {
        $size = $this->file_size;
        
        if ($size < 1024) {
            return $size . ' B';
        } elseif ($size < 1048576) {
            return round($size / 1024, 2) . ' KB';
        } elseif ($size < 1073741824) {
            return round($size / 1048576, 2) . ' MB';
        } else {
            return round($size / 1073741824, 2) . ' GB';
        }
    }
}