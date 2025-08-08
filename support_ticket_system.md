# Support Ticket and Token System Implementation Plan

## Overview
This document outlines the implementation plan for the support ticket and token system in the ISP Billing & CRM system. The system will provide customers with a way to create support requests, generate tokens for in-person support, and track all support interactions.

## System Components

### 1. Ticket Management
- Ticket creation from customer panel
- Ticket assignment to support staff/managers
- Ticket history attached to customer
- Escalation to manager/reseller
- Ticket categories and priorities
- Ticket status tracking

### 2. Token System
- Token generation per customer
- Token assignment to staff
- Token closure and printing
- Token search and filtering
- Token status tracking

### 3. Support Staff Management
- Support staff assignment
- Workload balancing
- Performance tracking
- Notification system

### 4. Reporting and Analytics
- Ticket volume and resolution time
- Staff performance metrics
- Customer satisfaction tracking
- Token usage statistics

## Ticket Management Implementation

### Ticket Model
```php
class SupportTicket extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'assigned_to',
        'category',
        'subject',
        'description',
        'priority',
        'status',
        'resolution',
        'resolved_at',
        'created_by'
    ];
    
    protected $casts = [
        'resolved_at' => 'datetime'
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }
    
    public function logs()
    {
        return $this->hasMany(TicketLog::class);
    }
    
    public function tokens()
    {
        return $this->hasMany(SupportToken::class);
    }
    
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
    
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }
    
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }
    
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
}
```

### Ticket Service
```php
class TicketService
{
    public function createTicket($data, $companyId, $createdBy = null)
    {
        $ticket = SupportTicket::create(array_merge($data, [
            'company_id' => $companyId,
            'created_by' => $createdBy ?? auth()->id(),
            'status' => 'open'
        ]));
        
        // Log ticket creation
        $this->logTicketAction($ticket, 'created', 'Ticket created', $createdBy);
        
        // Send notification to assigned staff
        if (!empty($data['assigned_to'])) {
            $this->notifyAssignee($ticket);
        }
        
        // Send notification to customer
        $this->notifyCustomer($ticket, 'created');
        
        return $ticket;
    }
    
    public function updateTicket($ticket, $data, $updatedBy = null)
    {
        $oldStatus = $ticket->status;
        $ticket->update($data);
        
        // Log ticket update
        $this->logTicketAction($ticket, 'updated', 'Ticket updated', $updatedBy);
        
        // Check if status changed
        if ($oldStatus !== $ticket->status) {
            $this->logTicketAction($ticket, 'status_changed', "Status changed from {$oldStatus} to {$ticket->status}", $updatedBy);
            
            // Send notification if ticket is resolved or closed
            if (in_array($ticket->status, ['resolved', 'closed'])) {
                $this->notifyCustomer($ticket, 'resolved');
            }
        }
        
        // Notify assignee if changed
        if (isset($data['assigned_to']) && $data['assigned_to'] != $ticket->getOriginal('assigned_to')) {
            $this->notifyAssignee($ticket);
        }
        
        return $ticket;
    }
    
    public function assignTicket($ticket, $assigneeId, $assignedBy = null)
    {
        $ticket->update(['assigned_to' => $assigneeId]);
        
        // Log assignment
        $this->logTicketAction($ticket, 'assigned', "Assigned to user ID {$assigneeId}", $assignedBy);
        
        // Notify assignee
        $this->notifyAssignee($ticket);
        
        return $ticket;
    }
    
    public function escalateTicket($ticket, $escalationType, $escalatedBy = null)
    {
        // Escalation logic depends on type
        switch ($escalationType) {
            case 'manager':
                // Assign to manager
                $manager = $this->getManagerForTicket($ticket);
                $ticket->update(['assigned_to' => $manager->id]);
                $this->logTicketAction($ticket, 'escalated', "Escalated to manager", $escalatedBy);
                break;
                
            case 'reseller':
                // Assign to reseller
                if ($ticket->customer && $ticket->customer->reseller_id) {
                    $ticket->update(['assigned_to' => $ticket->customer->reseller_id]);
                    $this->logTicketAction($ticket, 'escalated', "Escalated to reseller", $escalatedBy);
                }
                break;
        }
        
        return $ticket;
    }
    
    public function addAttachment($ticket, $file, $uploadedBy = null)
    {
        $path = $file->store('ticket-attachments', 'private');
        
        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'uploaded_by' => $uploadedBy ?? auth()->id()
        ]);
        
        // Log attachment addition
        $this->logTicketAction($ticket, 'attachment_added', "Attachment added: {$file->getClientOriginalName()}", $uploadedBy);
        
        return $attachment;
    }
    
    public function addComment($ticket, $comment, $commentedBy = null)
    {
        $log = $this->logTicketAction($ticket, 'commented', $comment, $commentedBy);
        
        // Notify relevant parties
        $this->notifyTicketComment($ticket, $comment, $commentedBy);
        
        return $log;
    }
    
    private function logTicketAction($ticket, $action, $description, $userId = null)
    {
        return TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'description' => $description
        ]);
    }
    
    private function notifyAssignee($ticket)
    {
        if ($ticket->assignee) {
            // Send notification to assignee
            Notification::send($ticket->assignee, new TicketAssignedNotification($ticket));
        }
    }
    
    private function notifyCustomer($ticket, $eventType)
    {
        if ($ticket->customer && $ticket->customer->email) {
            // Send notification to customer
            switch ($eventType) {
                case 'created':
                    Notification::send($ticket->customer, new TicketCreatedCustomerNotification($ticket));
                    break;
                case 'resolved':
                    Notification::send($ticket->customer, new TicketResolvedCustomerNotification($ticket));
                    break;
            }
        }
    }
    
    private function notifyTicketComment($ticket, $comment, $commentedBy)
    {
        // Notify assignee and customer about comment
        if ($ticket->assignee) {
            Notification::send($ticket->assignee, new TicketCommentNotification($ticket, $comment, $commentedBy));
        }
        
        if ($ticket->customer && $ticket->customer->email) {
            Notification::send($ticket->customer, new TicketCommentCustomerNotification($ticket, $comment, $commentedBy));
        }
    }
    
    private function getManagerForTicket($ticket)
    {
        // Logic to find appropriate manager
        // This could be based on department, location, etc.
        return User::where('company_id', $ticket->company_id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'admin_support')
                    ->orWhere('name', 'company_admin');
            })
            ->first();
    }
}
```

## Token System Implementation

### Token Model
```php
class SupportToken extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'ticket_id',
        'token_number',
        'category',
        'assigned_to',
        'status',
        'printed',
        'closed_at',
        'closed_by'
    ];
    
    protected $casts = [
        'printed' => 'boolean',
        'closed_at' => 'datetime'
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($token) {
            if (empty($token->token_number)) {
                $token->token_number = $this->generateTokenNumber();
            }
        });
    }
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class);
    }
    
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
    
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
    
    public function scopePrinted($query)
    {
        return $query->where('printed', true);
    }
    
    private function generateTokenNumber()
    {
        // Generate unique token number
        do {
            $tokenNumber = 'TK' . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('token_number', $tokenNumber)->exists());
        
        return $tokenNumber;
    }
}
```

### Token Service
```php
class TokenService
{
    public function generateToken($data, $companyId)
    {
        $token = SupportToken::create(array_merge($data, [
            'company_id' => $companyId,
            'status' => 'open'
        ]));
        
        // Send notification to assigned staff
        if (!empty($data['assigned_to'])) {
            $this->notifyTokenAssignee($token);
        }
        
        return $token;
    }
    
    public function assignToken($token, $assigneeId, $assignedBy = null)
    {
        $token->update([
            'assigned_to' => $assigneeId,
            'assigned_by' => $assignedBy ?? auth()->id()
        ]);
        
        // Notify assignee
        $this->notifyTokenAssignee($token);
        
        // Log assignment
        $this->logTokenAction($token, 'assigned', "Assigned to user ID {$assigneeId}", $assignedBy);
        
        return $token;
    }
    
    public function closeToken($token, $closedBy = null)
    {
        $token->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $closedBy ?? auth()->id()
        ]);
        
        // Log closure
        $this->logTokenAction($token, 'closed', 'Token closed', $closedBy);
        
        return $token;
    }
    
    public function printToken($token, $printedBy = null)
    {
        $token->update([
            'printed' => true,
            'printed_at' => now(),
            'printed_by' => $printedBy ?? auth()->id()
        ]);
        
        // Log printing
        $this->logTokenAction($token, 'printed', 'Token printed', $printedBy);
        
        return $token;
    }
    
    public function searchTokens($filters)
    {
        $query = SupportToken::query();
        
        // Apply filters
        if (!empty($filters['token_number'])) {
            $query->where('token_number', 'like', '%' . $filters['token_number'] . '%');
        }
        
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['date_range'])) {
            $query->whereBetween('created_at', $filters['date_range']);
        }
        
        return $query->orderBy('created_at', 'desc')->paginate(50);
    }
    
    public function getDailyTokenCount($companyId, $date = null)
    {
        $date = $date ?? now();
        
        return SupportToken::where('company_id', $companyId)
            ->whereDate('created_at', $date)
            ->count();
    }
    
    public function getOpenTokens($companyId)
    {
        return SupportToken::where('company_id', $companyId)
            ->where('status', 'open')
            ->count();
    }
    
    private function notifyTokenAssignee($token)
    {
        if ($token->assignee) {
            // Send notification to assignee
            Notification::send($token->assignee, new TokenAssignedNotification($token));
        }
    }
    
    private function logTokenAction($token, $action, $description, $userId = null)
    {
        return TokenLog::create([
            'token_id' => $token->id,
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'description' => $description
        ]);
    }
}
```

## Support Staff Management

### Staff Assignment Service
```php
class SupportStaffService
{
    public function assignTicketsByRoundRobin($companyId, $tickets, $staffMembers)
    {
        $assignments = [];
        $staffIndex = 0;
        
        foreach ($tickets as $ticket) {
            $staffMember = $staffMembers[$staffIndex];
            $ticket->update(['assigned_to' => $staffMember->id]);
            
            $assignments[] = [
                'ticket_id' => $ticket->id,
                'staff_id' => $staffMember->id
            ];
            
            // Move to next staff member
            $staffIndex = ($staffIndex + 1) % count($staffMembers);
        }
        
        return $assignments;
    }
    
    public function assignTicketsByWorkload($companyId, $tickets)
    {
        // Get support staff with their current workload
        $staffMembers = User::where('company_id', $companyId)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'admin_support')
                    ->orWhere('name', 'company_admin');
            })
            ->withCount(['tickets as open_tickets' => function ($query) {
                $query->where('status', 'open')
                    ->orWhere('status', 'in_progress');
            }])
            ->get();
            
        $assignments = [];
        
        foreach ($tickets as $ticket) {
            // Find staff member with least workload
            $staffMember = $staffMembers->sortBy('open_tickets')->first();
            
            $ticket->update(['assigned_to' => $staffMember->id]);
            
            $assignments[] = [
                'ticket_id' => $ticket->id,
                'staff_id' => $staffMember->id
            ];
            
            // Update workload count for this staff member
            $staffMember->open_tickets++;
        }
        
        return $assignments;
    }
    
    public function getStaffPerformance($staffId, $startDate, $endDate)
    {
        $ticketsHandled = SupportTicket::where('assigned_to', $staffId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $ticketsResolved = SupportTicket::where('assigned_to', $staffId)
            ->where('status', 'resolved')
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->count();
            
        $averageResolutionTime = SupportTicket::where('assigned_to', $staffId)
            ->where('status', 'resolved')
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->avg(DB::raw('EXTRACT(EPOCH FROM (resolved_at - created_at))/3600'));
            
        return [
            'tickets_handled' => $ticketsHandled,
            'tickets_resolved' => $ticketsResolved,
            'resolution_rate' => $ticketsHandled > 0 ? ($ticketsResolved / $ticketsHandled) * 100 : 0,
            'average_resolution_time_hours' => $averageResolutionTime ?? 0
        ];
    }
}
```

## Customer Portal Implementation

### Customer Ticket Controller
```php
class CustomerTicketController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::where('customer_id', auth()->user()->customer->id)
            ->with('assignee')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return response()->json($tickets);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent'
        ]);
        
        $ticketService = new TicketService();
        $ticket = $ticketService->createTicket($request->all(), tenancy()->company()->id);
        
        return response()->json($ticket, 201);
    }
    
    public function show(SupportTicket $ticket)
    {
        // Ensure ticket belongs to customer
        if ($ticket->customer_id !== auth()->user()->customer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $ticket->load(['assignee', 'attachments', 'logs.user']);
        
        return response()->json($ticket);
    }
    
    public function addAttachment(Request $request, SupportTicket $ticket)
    {
        // Ensure ticket belongs to customer
        if ($ticket->customer_id !== auth()->user()->customer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'file' => 'required|file|max:5120' // 5MB max
        ]);
        
        $ticketService = new TicketService();
        $attachment = $ticketService->addAttachment($ticket, $request->file('file'));
        
        return response()->json($attachment, 201);
    }
}
```

## Token Generation and Management

### Token Generation Controller
```php
class TokenController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'category' => 'required|string',
            'assigned_to' => 'nullable|exists:users,id'
        ]);
        
        $tokenService = new TokenService();
        $token = $tokenService->generateToken($request->all(), tenancy()->company()->id);
        
        return response()->json($token, 201);
    }
    
    public function print(SupportToken $token)
    {
        // Ensure token belongs to company
        if ($token->company_id !== tenancy()->company()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $tokenService = new TokenService();
        $tokenService->printToken($token);
        
        // Return printable view
        return response()->json(['message' => 'Token marked as printed']);
    }
    
    public function close(SupportToken $token)
    {
        // Ensure token belongs to company
        if ($token->company_id !== tenancy()->company()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $tokenService = new TokenService();
        $tokenService->closeToken($token);
        
        return response()->json(['message' => 'Token closed successfully']);
    }
    
    public function search(Request $request)
    {
        $filters = $request->all();
        $tokenService = new TokenService();
        $tokens = $tokenService->searchTokens($filters);
        
        return response()->json($tokens);
    }
    
    public function getStats()
    {
        $tokenService = new TokenService();
        $companyId = tenancy()->company()->id;
        
        return response()->json([
            'daily_count' => $tokenService->getDailyTokenCount($companyId),
            'open_tokens' => $tokenService->getOpenTokens($companyId)
        ]);
    }
}
```

## Notification System

### Ticket Notifications
```php
class TicketAssignedNotification extends Notification
{
    protected $ticket;
    
    public function __construct(SupportTicket $ticket)
    {
        $this->ticket = $ticket;
    }
    
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }
    
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("New Support Ticket Assigned: {$this->ticket->subject}")
            ->line("A new support ticket has been assigned to you.")
            ->line("Ticket: {$this->ticket->subject}")
            ->line("Customer: {$this->ticket->customer->name}")
            ->line("Priority: {$this->ticket->priority}")
            ->action('View Ticket', url('/support/tickets/' . $this->ticket->id));
    }
    
    public function toArray($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'customer_name' => $this->ticket->customer->name,
            'priority' => $this->ticket->priority
        ];
    }
}
```

## Reporting and Analytics

### Support Report Service
```php
class SupportReportService
{
    public function getTicketVolumeReport($companyId, $startDate, $endDate)
    {
        $tickets = SupportTicket::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total_tickets,
                COUNT(CASE WHEN status = "resolved" THEN 1 END) as resolved_tickets,
                COUNT(CASE WHEN status = "closed" THEN 1 END) as closed_tickets
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        return $tickets;
    }
    
    public function getStaffPerformanceReport($companyId, $startDate, $endDate)
    {
        $staffPerformance = User::where('company_id', $companyId)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'admin_support')
                    ->orWhere('name', 'company_admin');
            })
            ->withCount(['tickets as total_tickets' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->withCount(['tickets as resolved_tickets' => function ($query) use ($startDate, $endDate) {
                $query->where('status', 'resolved')
                    ->whereBetween('resolved_at', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function ($staff) {
                $resolutionRate = $staff->total_tickets > 0 ? 
                    ($staff->resolved_tickets / $staff->total_tickets) * 100 : 0;
                    
                return [
                    'staff_name' => $staff->name,
                    'total_tickets' => $staff->total_tickets,
                    'resolved_tickets' => $staff->resolved_tickets,
                    'resolution_rate' => round($resolutionRate, 2)
                ];
            });
            
        return $staffPerformance;
    }
    
    public function getTokenUsageReport($companyId, $startDate, $endDate)
    {
        $tokens = SupportToken::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as tokens_generated,
                COUNT(CASE WHEN status = "closed" THEN 1 END) as tokens_closed,
                COUNT(CASE WHEN printed = true THEN 1 END) as tokens_printed
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        return $tokens;
    }
}
```

## Testing Strategy

### Ticket Tests
```php
class TicketTest extends TestCase
{
    public function test_ticket_creation()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $supportStaff = User::factory()->create(['company_id' => $company->id]);
        
        // Initialize tenant context
        tenancy()->initialize($company);
        
        $ticketData = [
            'customer_id' => $customer->id,
            'category' => 'technical',
            'subject' => 'Internet connection issue',
            'description' => 'Customer is experiencing intermittent connection issues',
            'priority' => 'medium'
        ];
        
        $ticketService = new TicketService();
        $ticket = $ticketService->createTicket($ticketData, $company->id);
        
        $this->assertDatabaseHas('support_tickets', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'subject' => 'Internet connection issue',
            'status' => 'open'
        ]);
    }
    
    public function test_ticket_assignment()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $supportStaff = User::factory()->create(['company_id' => $company->id]);
        
        // Initialize tenant context
        tenancy()->initialize($company);
        
        $ticket = SupportTicket::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'subject' => 'Test ticket',
            'status' => 'open'
        ]);
        
        $ticketService = new TicketService();
        $ticketService->assignTicket($ticket, $supportStaff->id);
        
        $ticket->refresh();
        $this->assertEquals($supportStaff->id, $ticket->assigned_to);
    }
    
    public function test_token_generation()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        
        // Initialize tenant context
        tenancy()->initialize($company);
        
        $tokenData = [
            'customer_id' => $customer->id,
            'category' => 'billing'
        ];
        
        $tokenService = new TokenService();
        $token = $tokenService->generateToken($tokenData, $company->id);
        
        $this->assertDatabaseHas('support_tokens', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'status' => 'open'
        ]);
        
        $this->assertNotNull($token->token_number);
        $this->assertStringStartsWith('TK', $token->token_number);
    }
}
```

This comprehensive support ticket and token system implementation plan provides a robust foundation for managing customer support requests and in-person support tokens in the ISP Billing & CRM system.