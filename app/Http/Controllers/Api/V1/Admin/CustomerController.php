<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends BaseController
{
    /**
     * Display a listing of the customers.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $customers = Customer::all();
        return $this->sendResponse($customers, 'Customers retrieved successfully.');
    }

    /**
     * Store a newly created customer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255|unique:customers',
            'username' => 'required|string|max:100|unique:customers',
            'password' => 'required|string|min:6',
            'nid' => 'nullable|string|max:50',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|max:17',
            'package_id' => 'required|exists:packages,id',
            'pop_id' => 'required|exists:pop,id',
            'router_id' => 'required|exists:mikrotik_routers,id',
            'reseller_id' => 'nullable|exists:users,id',
            'customer_type' => 'required|in:home,free,vip,corporate',
            'activation_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        // Hash the password
        $request->merge(['password' => bcrypt($request->password)]);

        $customer = Customer::create($request->all());
        return $this->sendResponse($customer, 'Customer created successfully.', 201);
    }

    /**
     * Display the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Customer $customer)
    {
        return $this->sendResponse($customer, 'Customer retrieved successfully.');
    }

    /**
     * Update the specified customer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Customer $customer)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255|unique:customers,email,' . $customer->id,
            'username' => 'required|string|max:100|unique:customers,username,' . $customer->id,
            'password' => 'nullable|string|min:6',
            'nid' => 'nullable|string|max:50',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|max:17',
            'package_id' => 'required|exists:packages,id',
            'pop_id' => 'required|exists:pop,id',
            'router_id' => 'required|exists:mikrotik_routers,id',
            'reseller_id' => 'nullable|exists:users,id',
            'customer_type' => 'required|in:home,free,vip,corporate',
            'activation_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        // Hash the password if provided
        if ($request->has('password')) {
            $request->merge(['password' => bcrypt($request->password)]);
        }

        $customer->update($request->all());
        return $this->sendResponse($customer, 'Customer updated successfully.');
    }

    /**
     * Remove the specified customer from storage.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();
        return $this->sendResponse(null, 'Customer deleted successfully.');
    }

    /**
     * Suspend the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function suspend(Customer $customer)
    {
        $customer->status = 'suspended';
        $customer->save();
        return $this->sendResponse($customer, 'Customer suspended successfully.');
    }

    /**
     * Enable the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function enable(Customer $customer)
    {
        $customer->status = 'active';
        $customer->save();
        return $this->sendResponse($customer, 'Customer enabled successfully.');
    }
}