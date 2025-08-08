<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    /**
     * Customer login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function customerLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        // Attempt to authenticate the customer
        $credentials = $request->only('username', 'password');
        
        // Check if we're using username or email for login
        $customer = Customer::where('username', $credentials['username'])
            ->orWhere('email', $credentials['username'])
            ->first();

        if (!$customer || !Hash::check($credentials['password'], $customer->password)) {
            return $this->sendError('Invalid credentials', [], 401);
        }

        // Create token for the customer
        $token = $customer->createToken('customer-token')->plainTextToken;

        $data = [
            'token' => $token,
            'customer' => $customer,
        ];

        return $this->sendResponse('Customer login successful', $data);
    }

    /**
     * Reseller login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resellerLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        // Attempt to authenticate the reseller
        $credentials = $request->only('email', 'password');
        
        $reseller = User::where('email', $credentials['email'])
            ->where('user_type', 'reseller')
            ->first();

        if (!$reseller || !Hash::check($credentials['password'], $reseller->password)) {
            return $this->sendError('Invalid credentials', [], 401);
        }

        // Create token for the reseller
        $token = $reseller->createToken('reseller-token')->plainTextToken;

        $data = [
            'token' => $token,
            'reseller' => $reseller,
        ];

        return $this->sendResponse('Reseller login successful', $data);
    }

    /**
     * Admin login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        // Attempt to authenticate the admin
        $credentials = $request->only('email', 'password');
        
        $admin = User::where('email', $credentials['email'])
            ->whereIn('user_type', ['super_admin', 'company_admin'])
            ->first();

        if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
            return $this->sendError('Invalid credentials', [], 401);
        }

        // Create token for the admin
        $token = $admin->createToken('admin-token')->plainTextToken;

        $data = [
            'token' => $token,
            'admin' => $admin,
        ];

        return $this->sendResponse('Admin login successful', $data);
    }

    /**
     * Logout the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return $this->sendResponse('Logout successful');
    }

    /**
     * Get the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return $this->sendResponse('User data retrieved successfully', $request->user());
    }
}