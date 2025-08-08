<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportController extends BaseController
{
    protected $supportTicketService;

    public function __construct(SupportTicketService $supportTicketService)
    {
        $this->supportTicketService = $supportTicketService;
    }

    /**
     * Get the authenticated customer's support tickets.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $customer = $request->user();
        $tickets = SupportTicket::where('customer_id', $customer->id)->get();
        
        return $this->sendResponse('Support tickets retrieved successfully', $tickets);
    }

    /**
     * Create a new support ticket.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $customer = $request->user();
        
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:100',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        // Create the support ticket
        $ticketData = $request->only(['subject', 'description', 'category', 'priority']);
        $ticketData['customer_id'] = $customer->id;
        $ticketData['company_id'] = $customer->company_id;
        
        $ticket = $this->supportTicketService->createTicket($ticketData, $customer);

        return $this->sendResponse('Support ticket created successfully', $ticket, 201);
    }

    /**
     * Get the specified support ticket.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SupportTicket  $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, SupportTicket $ticket)
    {
        $customer = $request->user();
        
        // Ensure the ticket belongs to the current customer
        if ($ticket->customer_id !== $customer->id) {
            return $this->sendForbidden('You do not have permission to view this ticket');
        }

        // Load ticket with comments
        $ticket->load('comments.user');
        
        return $this->sendResponse('Support ticket retrieved successfully', $ticket);
    }

    /**
     * Add a comment to the specified support ticket.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SupportTicket  $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function addComment(Request $request, SupportTicket $ticket)
    {
        $customer = $request->user();
        
        // Ensure the ticket belongs to the current customer
        if ($ticket->customer_id !== $customer->id) {
            return $this->sendForbidden('You do not have permission to comment on this ticket');
        }

        $validator = $request->validate([
            'comment' => 'required|string',
        ]);

        // Add comment to the ticket
        $comment = $this->supportTicketService->addComment($ticket, $customer, $request->comment);

        return $this->sendResponse('Comment added successfully', $comment);
    }

    /**
     * Close the specified support ticket.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SupportTicket  $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function close(Request $request, SupportTicket $ticket)
    {
        $customer = $request->user();
        
        // Ensure the ticket belongs to the current customer
        if ($ticket->customer_id !== $customer->id) {
            return $this->sendForbidden('You do not have permission to close this ticket');
        }

        // Close the ticket
        $ticket->status = 'closed';
        $ticket->save();

        // Log the closure
        $this->supportTicketService->logTicketAction($ticket, $customer, 'closed', 'Ticket closed by customer');

        return $this->sendResponse('Ticket closed successfully');
    }
}