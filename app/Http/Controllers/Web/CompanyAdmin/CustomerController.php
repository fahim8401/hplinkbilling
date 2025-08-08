<?php

namespace App\Http\Controllers\Web\CompanyAdmin;

use App\Http\Controllers\Web\BaseController;
use App\Models\Customer;
use App\Models\Package;
use App\Models\POP;
use App\Models\MikrotikRouter;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class CustomerController extends BaseController
{
    protected $billingService;

    public function __construct(BillingService $billingService)
    {
        parent::__construct();
        $this->billingService = $billingService;
    }

    /**
     * Display a listing of customers.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $customers = Customer::where('company_id', tenancy()->company()->id)->get();
        return $this->view('companyadmin.customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new customer.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        $packages = Package::where('company_id', tenancy()->company()->id)->get();
        $pops = POP::where('company_id', tenancy()->company()->id)->get();
        $routers = MikrotikRouter::where('company_id', tenancy()->company()->id)->get();
        
        return $this->view('companyadmin.customers.create', compact('packages', 'pops', 'routers'));
    }

    /**
     * Store a newly created customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
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
            'pop_id' => 'required|exists:pops,id',
            'router_id' => 'required|exists:mikrotik_routers,id',
            'customer_type' => 'required|in:home,free,vip,corporate',
            'activation_date' => 'required|date',
            'expiry_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Hash the password
        $request->merge(['password' => Hash::make($request->password)]);

        // Add company ID to the request
        $request->merge(['company_id' => tenancy()->company()->id]);

        // Create the customer
        $customer = Customer::create($request->all());

        return $this->redirectSuccess('companyadmin.customers.index', 'Customer created successfully.');
    }

    /**
     * Display the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Customer $customer)
    {
        // Ensure the customer belongs to the current company
        if ($customer->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        return $this->view('companyadmin.customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Customer $customer)
    {
        // Ensure the customer belongs to the current company
        if ($customer->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        $packages = Package::where('company_id', tenancy()->company()->id)->get();
        $pops = POP::where('company_id', tenancy()->company()->id)->get();
        $routers = MikrotikRouter::where('company_id', tenancy()->company()->id)->get();
        
        return $this->view('companyadmin.customers.edit', compact('customer', 'packages', 'pops', 'routers'));
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
        // Ensure the customer belongs to the current company
        if ($customer->company_id !== tenancy()->company()->id) {
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
            'pop_id' => 'required|exists:pops,id',
            'router_id' => 'required|exists:mikrotik_routers,id',
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

        return $this->redirectSuccess('companyadmin.customers.index', 'Customer updated successfully.');
    }

    /**
     * Remove the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Customer $customer)
    {
        // Ensure the customer belongs to the current company
        if ($customer->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        // Delete the customer
        $customer->delete();

        return $this->redirectSuccess('companyadmin.customers.index', 'Customer deleted successfully.');
    }

    /**
     * Suspend the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function suspend(Customer $customer)
    {
        // Ensure the customer belongs to the current company
        if ($customer->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        // Suspend the customer
        $customer->status = 'suspended';
        $customer->save();

        return $this->backSuccess('Customer suspended successfully.');
    }

    /**
     * Enable the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enable(Customer $customer)
    {
        // Ensure the customer belongs to the current company
        if ($customer->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        // Enable the customer
        $customer->status = 'active';
        $customer->save();

        return $this->backSuccess('Customer enabled successfully.');
    }

    /**
     * Extend the expiry date of the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @param  string  $paymentType
     * @return \Illuminate\Http\RedirectResponse
     */
    public function extendExpiry(Customer $customer, $paymentType = 'receive')
    {
        // Ensure the customer belongs to the current company
        if ($customer->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        // Extend the customer's expiry date
        $this->billingService->extendCustomerExpiry($customer, $paymentType);

        return $this->backSuccess('Customer expiry date extended successfully.');
    }
}