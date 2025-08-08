<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Api\BaseController;
use App\Models\SupportToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TokenController extends BaseController
{
    /**
     * Display a listing of the tokens.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $tokens = SupportToken::all();
        return $this->sendResponse($tokens, 'Tokens retrieved successfully.');
    }

    /**
     * Store a newly created token in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'ticket_id' => 'nullable|exists:support_tickets,id',
            'category' => 'required|string|max:100',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        // Generate a unique token number
        $tokenNumber = SupportToken::generateTokenNumber(tenancy()->company()->id);
        $request->merge(['token_number' => $tokenNumber]);
        $request->merge(['status' => 'open']);

        $token = SupportToken::create($request->all());
        return $this->sendResponse($token, 'Token created successfully.', 201);
    }

    /**
     * Display the specified token.
     *
     * @param  \App\Models\SupportToken  $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(SupportToken $token)
    {
        return $this->sendResponse($token, 'Token retrieved successfully.');
    }

    /**
     * Update the specified token in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SupportToken  $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, SupportToken $token)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:100',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'nullable|in:open,in_progress,resolved,closed',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        $token->update($request->all());
        return $this->sendResponse($token, 'Token updated successfully.');
    }

    /**
     * Remove the specified token from storage.
     *
     * @param  \App\Models\SupportToken  $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(SupportToken $token)
    {
        $token->delete();
        return $this->sendResponse(null, 'Token deleted successfully.');
    }

    /**
     * Mark the specified token as printed.
     *
     * @param  \App\Models\SupportToken  $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function print(SupportToken $token)
    {
        $token->markAsPrinted();
        return $this->sendResponse($token, 'Token marked as printed successfully.');
    }

    /**
     * Change the status of the specified token.
     *
     * @param  \App\Models\SupportToken  $token
     * @param  string  $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(SupportToken $token, $status)
    {
        $validStatuses = ['open', 'in_progress', 'resolved', 'closed'];

        if (!in_array($status, $validStatuses)) {
            return $this->sendError('Invalid status.');
        }

        $token->status = $status;
        $token->save();

        return $this->sendResponse($token, 'Token status updated successfully.');
    }

    /**
     * Get tokens by status.
     *
     * @param  string  $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByStatus($status)
    {
        $tokens = SupportToken::where('status', $status)->get();
        return $this->sendResponse($tokens, 'Tokens retrieved successfully.');
    }

    /**
     * Search tokens by token number.
     *
     * @param  string  $tokenNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByTokenNumber($tokenNumber)
    {
        $tokens = SupportToken::where('token_number', 'like', '%' . $tokenNumber . '%')->get();
        return $this->sendResponse($tokens, 'Tokens retrieved successfully.');
    }
}