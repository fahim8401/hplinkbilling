<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\SupportToken;
use App\Models\TicketAttachment;
use App\Models\TicketLog;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class SupportTicketService
{
    /**
     * Create a new support ticket.
     *
     * @param array $data
     * @param User $user
     * @return SupportTicket
     */
    public function createTicket($data, User $user)
    {
        $data['company_id'] = $user->company_id;
        $data['status'] = 'open';
        
        $ticket = SupportTicket::create($data);
        
        // Log the ticket creation
        $this->logTicketAction($ticket, $user, 'created', 'Ticket created');
        
        return $ticket;
    }

    /**
     * Create a new support token.
     *
     * @param array $data
     * @param User $user
     * historyreturn SupportToken
     */
    public function createToken($data, User $user)
    {
        $data['company_id'] = $user->company_id;
        $data['token_number'] = SupportToken::generateTokenNumber($user->company_id);
        $data['status'] = 'open';
        
        $token = SupportToken::create($data);
        
        return $token;
    }

    /**
     * Assign a ticket to a user.
     *
     * @param SupportTicket $ticket
     * @param User $user
     * @param User $assignedTo
     * @return SupportTicket
     */
    public function assignTicket(SupportTicket $ticket, User $user, User $assignedTo)
    {
        $ticket->assigned_to = $assignedTo->id;
        $ticket->save();
        
        // Log the assignment
        $this->logTicketAction($ticket, $user, 'assigned', 'Ticket assigned to ' . $assignedTo->name);
        
        return $ticket;
    }

    /**
     * Change the status of a ticket.
     *
     * @param SupportTicket $ticket
     * @param User $user
     * @param string $status
     * @return SupportTicket
     */
    public function changeTicketStatus(SupportTicket $ticket, User $user, $status)
    {
        $validStatuses = ['open', 'in_progress', 'resolved', 'closed'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid status');
        }
        
        $oldStatus = $ticket->status;
        $ticket->status = $status;
        $ticket->save();
        
        // Log the status change
        $this->logTicketAction($ticket, $user, 'status_changed', 'Status changed from ' . $oldStatus . ' to ' . $status);
        
        return $ticket;
    }

    /**
     * Add a comment to a ticket.
     *
     * @param SupportTicket $ticket
     * @param User $user
     * @param string $comment
     * @return TicketLog
     */
    public function addComment(SupportTicket $ticket, User $user, $comment)
    {
        $log = $this->logTicketAction($ticket, $user, 'comment_added', $comment);
        return $log;
    }

    /**
     * Attach a file to a ticket.
     *
     * @param SupportTicket $ticket
     * @param User $user
     * @param string $filePath
     * @param string $fileName
     * @return TicketAttachment
     */
    public function attachFile(SupportTicket $ticket, User $user, $filePath, $fileName)
    {
        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => Storage::size($filePath),
        ]);
        
        // Log the attachment
        $this->logTicketAction($ticket, $user, 'attachment_added', 'File attached: ' . $fileName);
        
        return $attachment;
    }

    /**
     * Close a ticket.
     *
     * @param SupportTicket $ticket
     * @param User $user
     * @return SupportTicket
     */
    public function closeTicket(SupportTicket $ticket, User $user)
    {
        $ticket->status = 'closed';
        $ticket->save();
        
        // Log the closure
        $this->logTicketAction($ticket, $user, 'closed', 'Ticket closed');
        
        return $ticket;
    }

    /**
     * Reopen a ticket.
     *
     * @param SupportTicket $ticket
     * @param User $user
     * @return SupportTicket
     */
    public function reopenTicket(SupportTicket $ticket, User $user)
    {
        $ticket->status = 'open';
        $ticket->save();
        
        // Log the reopening
        $this->logTicketAction($ticket, $user, 'reopened', 'Ticket reopened');
        
        return $ticket;
    }

    /**
     * Log a ticket action.
     *
     * @param SupportTicket $ticket
     * @param User $user
     * @param string $action
     * @param string $description
     * @return TicketLog
     */
    public function logTicketAction(SupportTicket $ticket, User $user, $action, $description)
    {
        $log = TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
        ]);

        return $log;
    }

    /**
     * Get ticket statistics.
     *
     * @param int $companyId
     * @return array
     */
    public function getTicketStats($companyId)
    {
        $stats = SupportTicket::where('company_id', $companyId)
            ->selectRaw('
                COUNT(*) as total_tickets,
                SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open_tickets,
                SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress_tickets,
                SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) as resolved_tickets,
                SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as closed_tickets
            ')
            ->first();

        return $stats;
    }

    /**
     * Get token statistics.
     *
     * @param int $companyId
     * @return array
     */
    public function getTokenStats($companyId)
    {
        $stats = SupportToken::where('company_id', $companyId)
            ->selectRaw('
                COUNT(*) as total_tokens,
                SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open_tokens,
                SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress_tokens,
                SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) as resolved_tokens,
                SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as closed_tokens
            ')
            ->first();

        return $stats;
    }

    /**
     * Get tickets by priority.
     *
     * @param int $companyId
     * @return array
     */
    public function getTicketsByPriority($companyId)
    {
        $tickets = SupportTicket::where('company_id', $companyId)
            ->selectRaw('
                priority,
                COUNT(*) as count
            ')
            ->groupBy('priority')
            ->get();

        return $tickets;
    }

    /**
     * Get tickets by category.
     *
     * @param int $companyId
     * @return array
     */
    public function getTicketsByCategory($companyId)
    {
        $tickets = SupportTicket::where('company_id', $companyId)
            ->selectRaw('
                category,
                COUNT(*) as count
            ')
            ->groupBy('category')
            ->get();

        return $tickets;
    }
}