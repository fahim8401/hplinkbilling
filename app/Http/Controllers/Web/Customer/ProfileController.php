<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Web\BaseController;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends BaseController
{
    /**
     * Display the customer's profile.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $customer = auth()->user();
        return $this->view('customer.profile.index', compact('customer'));
    }

    /**
     * Show the form for editing the customer's profile.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function edit()
    {
        $customer = auth()->user();
        return $this->view('customer.profile.edit', compact('customer'));
    }

    /**
     * Update the customer's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $customer = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'email' => 'nullable|email|max:255|unique:customers,email,' . $customer->id,
            'nid' => 'nullable|string|max:50',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|max:17',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Update the customer
        $customer->update($request->only(['name', 'phone', 'email', 'nid', 'ip_address', 'mac_address']));

        return $this->redirectSuccess('customer.profile.index', 'Profile updated successfully.');
    }

    /**
     * Show the form for changing the customer's password.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function changePassword()
    {
        return $this->view('customer.profile.change-password');
    }

    /**
     * Update the customer's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(Request $request)
    {
        $customer = auth()->user();
        
        $validator = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Check if current password is correct
        if (!Hash::check($request->current_password, $customer->password)) {
            return $this->backError('Current password is incorrect.');
        }

        // Update the password
        $customer->password = Hash::make($request->password);
        $customer->save();

        return $this->redirectSuccess('customer.profile.index', 'Password updated successfully.');
    }
}