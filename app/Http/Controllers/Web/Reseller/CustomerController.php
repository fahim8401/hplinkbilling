<?php

namespace App\Http\Controllers\Web\Reseller;

use App\Http\Controllers\Web\BaseController;
use App\Models\Customer;
use App\Models\Package;
use App\Services\BillingService;
use App\Services\ResellerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class CustomerController extends BaseController
{
    protected $billingService;
    protected $resellerService;

    public function __construct(BillingService $billingService, ResellerService $resellerService)
    {
        parent::__construct();
        $this->billingService = $billingService;
        $this->resellerService = $resellerService;
    }

    /**
     * Display a listing of customers.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $reseller = auth()->user();
        $customers = Customer::where('reseller_id', $reseller->id)->get();
        return $this->view('reseller.customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new customer.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        $reseller = auth()->user();
        $packages = Package::where('company_id', $reseller->company_id)->get();
        return $this->view('reseller.customers.create', compact('packages'));
    }

    /**
     * Store a newly created customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $reseller = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers',
            'email' => 'nullable|email|max:255|unique:customers',
            'username' => 'required|string|max:100|unique:customers',
            'password' => 'required|string|min:6',
            'nid' => 'nullable|string|max:50',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|max:17',
            'package_id' => 'required|exists:packages,id',
            'customer_type' => 'required|in:home,free,vip,corporate',
            'activation_date' => 'required|date',
            'expiry_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Check if reseller has sufficient balance for creating a customer
        // This would depend on the business logic - for now we'll assume it's free to create
        
        // Hash the password
        $request->merge(['password' => Hash::make($request->password)]);

        // Add company ID and reseller ID to the request
        $request->merge([
            'company_id' => $reseller->company_id,
            'reseller_id' => $reseller->id,
        ]);

        // Create the customer
        $customer = Customer::create($request->all());

        return $this->redirectSuccess('reseller.customers.index', 'Customer created successfully.');
    }

    /**
     * Display the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Customer $customer)
    {
        $reseller = auth()->user();
        
        // Ensure the customer belongs to the current reseller
        if ($customer->reseller_id !== $reseller->id) {
            abort(404);
        }

        return $this->view('reseller.customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Customer $customer)
    {
        $reseller = auth()->user();
        
        // Ensure the customer belongs to the current reseller
        if ($customer->reseller_id !== $reseller->id) {
            abort(404);
        }

        $packages = Package::where('company_id', $reseller->company_id)->get();
        return $this->view('reseller.customers.edit', compact('customer', 'packages'));
    }

    /**
     * Update the specified customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Customer $customer)
    {
        $reseller = auth()->user();
        
        // Ensure the customer belongs to the current reseller
        if ($customer->reseller_id !== $reseller->id) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'email' => 'nullable|email|max:255|unique:customers,email,' . $customer->id,
            'username' => 'required|string|max:100|unique:customers,username,' . $customer->id,
            'password' => 'nullable|string|min:6',
            'nid' => 'nullable|string|max:50',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|max:17',
            'package_id' => 'required|exists:packages,id',
            'customer_type' => 'required|in:home,free,vip,corporate',
            'activation_date' => 'required|date',
            'expiry_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Hash the password if provided
        if ($request->has('password')) {
            $request->merge(['password' => Hash::make($request->password)]);
        }

        // Update the customer
        $customer->update($request->all());

        return $this->redirectSuccess('reseller.customers.index', 'Customer updated successfully.');
    }

    /**
     * Remove the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Customer $customer)
    {
        $reseller = auth()->user();
        
        // Ensure the customer belongs to the current reseller
        if ($customer->reseller_id !== $reseller->id) {
            abort(404);
        }

        // Delete the customer
        $customer->delete();

        return $this->redirectSuccess('reseller.customers.index', 'Customer deleted successfully.');
    }

    /**
     * Recharge the specified customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function recharge(Request $request, Customer $customer)
    {
        $reseller = auth()->user();
        
        // Ensure the customer belongs to the current reseller
        if ($customer->reseller_id !== $reseller->id) {
            abort(404);
        }

        $validator = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'required|in:receive,due',
        ]);

        // Check if reseller has sufficient balance
        if (!$this->resellerService->validateBalanceForRecharge($reseller, $request->amount)) {
            return $this->backError('Insufficient balance for this recharge.');
        }

        // Deduct balance from reseller
        $this->resellerService->deductBalanceForRecharge($reseller, $request->amount, 'Customer recharge');

        // Extend customer expiry
        $this->billingService->extendCustomerExpiry($customer, $request->payment_type);

        // Record the payment
        $this->billingService->processPayment($customer, $request->amount, $request->payment_type, null, null, $reseller->id);

        return $this->backSuccess('Customer recharged successfully.');
    }
}