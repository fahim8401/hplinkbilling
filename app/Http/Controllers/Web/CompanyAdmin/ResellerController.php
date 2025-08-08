<?php

namespace App\Http\Controllers\Web\CompanyAdmin;

use App\Http\Controllers\Web\BaseController;
use App\Models\User;
use App\Models\Customer;
use App\Services\ResellerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ResellerController extends BaseController
{
    protected $resellerService;

    public function __construct(ResellerService $resellerService)
    {
        parent::__construct();
        $this->resellerService = $resellerService;
    }

    /**
     * Display a listing of resellers.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $resellers = User::where('company_id', tenancy()->company()->id)
            ->where('user_type', 'reseller')
            ->get();
            
        return $this->view('companyadmin.resellers.index', compact('resellers'));
    }

    /**
     * Show the form for creating a new reseller.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return $this->view('companyadmin.resellers.create');
    }

    /**
     * Store a newly created reseller.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Create the reseller
        $reseller = User::create([
            'company_id' => tenancy()->company()->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'user_type' => 'reseller',
            'commission_percent' => $request->commission_percent,
        ]);

        return $this->redirectSuccess('companyadmin.resellers.index', 'Reseller created successfully.');
    }

    /**
     * Display the specified reseller.
     *
     * @param  \App\Models\User  $reseller
     * @return \Illuminate\Contracts\View\View
     */
    public function show(User $reseller)
    {
        // Ensure the reseller belongs to the current company
        if ($reseller->company_id !== tenancy()->company()->id || $reseller->user_type !== 'reseller') {
            abort(404);
        }

        // Get the reseller's balance
        $balance = $this->resellerService->getResellerBalance($reseller);

        // Get the reseller's customers
        $customers = Customer::where('reseller_id', $reseller->id)->get();

        return $this->view('companyadmin.resellers.show', compact('reseller', 'balance', 'customers'));
    }

    /**
     * Show the form for editing the specified reseller.
     *
     * @param  \App\Models\User  $reseller
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(User $reseller)
    {
        // Ensure the reseller belongs to the current company
        if ($reseller->company_id !== tenancy()->company()->id || $reseller->user_type !== 'reseller') {
            abort(404);
        }

        return $this->view('companyadmin.resellers.edit', compact('reseller'));
    }

    /**
     * Update the specified reseller.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $reseller
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $reseller)
    {
        // Ensure the reseller belongs to the current company
        if ($reseller->company_id !== tenancy()->company()->id || $reseller->user_type !== 'reseller') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $reseller->id,
            'phone' => 'required|string|max:20|unique:users,phone,' . $reseller->id,
            'password' => 'nullable|string|min:6|confirmed',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Update the reseller
        $reseller->name = $request->name;
        $reseller->email = $request->email;
        $reseller->phone = $request->phone;
        
        if ($request->has('password')) {
            $reseller->password = Hash::make($request->password);
        }
        
        $reseller->commission_percent = $request->commission_percent;
        $reseller->save();

        return $this->redirectSuccess('companyadmin.resellers.index', 'Reseller updated successfully.');
    }

    /**
     * Remove the specified reseller.
     *
     * @param  \App\Models\User  $reseller
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $reseller)
    {
        // Ensure the reseller belongs to the current company
        if ($reseller->company_id !== tenancy()->company()->id || $reseller->user_type !== 'reseller') {
            abort(404);
        }

        // Delete the reseller
        $reseller->delete();

        return $this->redirectSuccess('companyadmin.resellers.index', 'Reseller deleted successfully.');
    }

    /**
     * Add balance to the specified reseller.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $reseller
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addBalance(Request $request, User $reseller)
    {
        // Ensure the reseller belongs to the current company
        if ($reseller->company_id !== tenancy()->company()->id || $reseller->user_type !== 'reseller') {
            abort(404);
        }

        $validator = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        // Add balance to the reseller
        $this->resellerService->addResellerBalance($reseller, $request->amount, 'Manual balance addition by admin');

        return $this->backSuccess('Balance added to reseller successfully.');
    }

    /**
     * Get the balance of the specified reseller.
     *
     * @param  \App\Models\User  $reseller
     * @return \Illuminate\Http\JsonResponse
     */
    public function balance(User $reseller)
    {
        // Ensure the reseller belongs to the current company
        if ($reseller->company_id !== tenancy()->company()->id || $reseller->user_type !== 'reseller') {
            abort(404);
        }

        // Get the reseller's balance
        $balance = $this->resellerService->getResellerBalance($reseller);

        return $this->jsonSuccess('Reseller balance retrieved successfully.', [
            'balance' => $balance->balance,
        ]);
    }

    /**
     * Get the commission report for the specified reseller.
     *
     * @param  \App\Models\User  $reseller
     * @return \Illuminate\Contracts\View\View
     */
    public function commissionReport(User $reseller)
    {
        // Ensure the reseller belongs to the current company
        if ($reseller->company_id !== tenancy()->company()->id || $reseller->user_type !== 'reseller') {
            abort(404);
        }

        // Get the commission report
        $startDate = now()->subMonth();
        $endDate = now();
        $report = $this->resellerService->generateCommissionReport($reseller, $startDate, $endDate);

        return $this->view('companyadmin.resellers.commission-report', compact('reseller', 'report', 'startDate', 'endDate'));
    }
}