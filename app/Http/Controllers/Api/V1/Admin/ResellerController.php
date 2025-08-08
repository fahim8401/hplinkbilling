<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResellerController extends BaseController
{
    /**
     * Display a listing of the resellers.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $resellers = User::where('user_type', 'reseller')->get();
        return $this->sendResponse($resellers, 'Resellers retrieved successfully.');
    }

    /**
     * Store a newly created reseller in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'username' => 'required|string|max:100|unique:users',
            'password' => 'required|string|min:6',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        // Hash the password
        $request->merge(['password' => bcrypt($request->password)]);
        $request->merge(['user_type' => 'reseller']);

        $reseller = User::create($request->all());
        return $this->sendResponse($reseller, 'Reseller created successfully.', 201);
    }

    /**
     * Display the specified reseller.
     *
     * @param  \App\Models\User  $reseller
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $reseller)
    {
        if ($reseller->user_type !== 'reseller') {
            return $this->sendError('User is not a reseller.');
        }

        return $this->sendResponse($reseller, 'Reseller retrieved successfully.');
    }

    /**
     * Update the specified reseller in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $reseller
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, User $reseller)
    {
        if ($reseller->user_type !== 'reseller') {
            return $this->sendError('User is not a reseller.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $reseller->id,
            'phone' => 'required|string|max:20',
            'username' => 'required|string|max:100|unique:users,username,' . $reseller->id,
            'password' => 'nullable|string|min:6',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        // Hash the password if provided
        if ($request->has('password')) {
            $request->merge(['password' => bcrypt($request->password)]);
        }

        $reseller->update($request->all());
        return $this->sendResponse($reseller, 'Reseller updated successfully.');
    }

    /**
     * Remove the specified reseller from storage.
     *
     * @param  \App\Models\User  $reseller
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $reseller)
    {
        if ($reseller->user_type !== 'reseller') {
            return $this->sendError('User is not a reseller.');
        }

        $reseller->delete();
        return $this->sendResponse(null, 'Reseller deleted successfully.');
    }

    /**
     * Get reseller's balance.
     *
     * @param  \App\Models\User  $reseller
     * @return \Illuminate\Http\JsonResponse
     */
    public function balance(User $reseller)
    {
        if ($reseller->user_type !== 'reseller') {
            return $this->sendError('User is not a reseller.');
        }

        $balance = $reseller->balance;
        return $this->sendResponse($balance, 'Reseller balance retrieved successfully.');
    }

    /**
     * Get reseller's commission report.
     *
     * @param  \App\Models\User  $reseller
     * @return \Illuminate\Http\JsonResponse
     */
    public function commissionReport(User $reseller)
    {
        if ($reseller->user_type !== 'reseller') {
            return $this->sendError('User is not a reseller.');
        }

        $commissions = $reseller->commissions;
        return $this->sendResponse($commissions, 'Reseller commission report retrieved successfully.');
    }
}