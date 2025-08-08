<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Web\BaseController;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportController extends BaseController
{
    protected $supportTicketService;

    public function __construct(SupportTicketService $supportTicketService)
    {
        parent::__construct();
        $this->supportTicketService = $supportTicketService;
    }

    /**
     * Display a listing of support tickets.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $customer = auth()->user();
        $tickets = SupportTicket::where('customer_id', $customer->id)->get();
        
        return $this->view('customer.support.index', compact('tickets'));
    }

    /**
     * Show the form for creating a new support ticket.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return $this->view('customer.support.create');
    }

    /**
     * Store a newly created support ticket.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $customer = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:100',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Create the support ticket
        $ticketData = $request->only(['subject', 'description', 'category', 'priority']);
        $ticketData['customer_id'] = $customer->id;
        $ticketData['company_id'] = $customer->company_id;
        
        $ticket = $this->supportTicketService->createTicket($ticketData, $customer);

        return $this->redirectSuccess('customer.support.index', 'Support ticket created successfully.');
    }

    /**
     * Display the specified support ticket.
     *
     * @param  \App\Models\SupportTicket  $ticket
     * @return \Illuminate\Contracts\View\View
     */
    public function show(SupportTicket $ticket)
    {
        $customer = auth()->user();
        
        // Ensure the ticket belongs to the current customer
        if ($ticket->customer_id !== $customer->id) {
            abort(404);
        }

        // Load ticket with comments
        $ticket->load('comments.user');
        
        return $this->view('customer.support.show', compact('ticket'));
    }

    /**
     * Add a comment to the specified support ticket.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SupportTicket  $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addComment(Request $request, SupportTicket $ticket)
    {
        $customer = auth()->user();
        
        // Ensure the ticket belongs to the current customer
        if ($ticket->customer_id !== $customer->id) {
            abort(404);
        }

        $validator = $request->validate([
            'comment' => 'required|string',
        ]);

        // Add comment to the ticket
        $this->supportTicketService->addComment($ticket, $customer, $request->comment);

        return $this->backSuccess('Comment added successfully.');
    }
}