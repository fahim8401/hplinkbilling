<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Api\BaseController;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketController extends BaseController
{
    /**
     * Display a listing of the tickets.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $tickets = SupportTicket::all();
        return $this->sendResponse($tickets, 'Tickets retrieved successfully.');
    }

    /**
     * Store a newly created ticket in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'category' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        $request->merge(['status' => 'open']);

        $ticket = SupportTicket::create($request->all());
        return $this->sendResponse($ticket, 'Ticket created successfully.', 201);
    }

    /**
     * Display the specified ticket.
     *
     * @param  \App\Models\SupportTicket  $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(SupportTicket $ticket)
    {
        return $this->sendResponse($ticket, 'Ticket retrieved successfully.');
    }

    /**
     * Update the specified ticket in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SupportTicket  $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, SupportTicket $ticket)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:open,in_progress,resolved,closed',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        $ticket->update($request->all());
        return $this->sendResponse($ticket, 'Ticket updated successfully.');
    }

    /**
     * Remove the specified ticket from storage.
     *
     * @param  \App\Models\SupportTicket  $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(SupportTicket $ticket)
    {
        $ticket->delete();
        return $this->sendResponse(null, 'Ticket deleted successfully.');
    }

    /**
     * Assign the specified ticket to a user.
     *
     * @param  \App\Models\SupportTicket  $ticket
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assign(SupportTicket $ticket, $userId)
    {
        $ticket->assigned_to = $userId;
        $ticket->save();

        return $this->sendResponse($ticket, 'Ticket assigned successfully.');
    }

    /**
     * Change the status of the specified ticket.
     *
     * @param  \App\Models\SupportTicket  $ticket
     * @param  string  $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(SupportTicket $ticket, $status)
    {
        $validStatuses = ['open', 'in_progress', 'resolved', 'closed'];

        if (!in_array($status, $validStatuses)) {
            return $this->sendError('Invalid status.');
        }

        $ticket->status = $status;
        $ticket->save();

        return $this->sendResponse($ticket, 'Ticket status updated successfully.');
    }

    /**
     * Get tickets by status.
     *
     * @param  string  $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByStatus($status)
    {
        $tickets = SupportTicket::where('status', $status)->get();
        return $this->sendResponse($tickets, 'Tickets retrieved successfully.');
    }

    /**
     * Get tickets assigned to a user.
     *
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAssignedTo($userId)
    {
        $tickets = SupportTicket::where('assigned_to', $userId)->get();
        return $this->sendResponse($tickets, 'Tickets retrieved successfully.');
    }
}