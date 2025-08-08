<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerController extends BaseController
{
    /**
     * Get the authenticated customer's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        $customer = $request->user();
        
        // Load customer with related data
        $customer->load(['package', 'pop', 'router']);
        
        return $this->sendResponse('Customer profile retrieved successfully', $customer);
    }

    /**
     * Update the authenticated customer's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $customer = $request->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'email' => 'nullable|email|max:255|unique:customers,email,' . $customer->id,
            'nid' => 'nullable|string|max:50',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|max:17',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        // Update the customer
        $customer->update($request->only(['name', 'phone', 'email', 'nid', 'ip_address', 'mac_address']));

        return $this->sendResponse('Profile updated successfully', $customer);
    }

    /**
     * Change the authenticated customer's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $customer = $request->user();
        
        $validator = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Check if current password is correct
        if (!Hash::check($request->current_password, $customer->password)) {
            return $this->sendError('Current password is incorrect', [], 400);
        }

        // Update the password
        $customer->password = Hash::make($request->password);
        $customer->save();

        return $this->sendResponse('Password updated successfully');
    }

    /**
     * Get the authenticated customer's usage data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function usage(Request $request)
    {
        $customer = $request->user();
        
        // Get customer usage data
        $usage = $customer->usage()->orderBy('usage_date', 'desc')->get();
        
        return $this->sendResponse('Customer usage data retrieved successfully', $usage);
    }

    /**
     * Get the authenticated customer's live session data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function liveSession(Request $request)
    {
        $customer = $request->user();
        
        // Get customer's live session data
        $session = $customer->sessions()->whereNull('logout_time')->first();
        
        if (!$session) {
            return $this->sendError('No active session found', [], 404);
        }
        
        return $this->sendResponse('Live session data retrieved successfully', $session);
    }
}