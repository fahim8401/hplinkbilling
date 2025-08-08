<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{
    /**
     * Get the authenticated customer's payments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $customer = $request->user();
        $payments = Payment::where('customer_id', $customer->id)->get();
        
        return $this->sendResponse('Payments retrieved successfully', $payments);
    }

    /**
     * Get the specified payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Payment $payment)
    {
        $customer = $request->user();
        
        // Ensure the payment belongs to the current customer
        if ($payment->customer_id !== $customer->id) {
            return $this->sendForbidden('You do not have permission to view this payment');
        }

        return $this->sendResponse('Payment retrieved successfully', $payment);
    }

    /**
     * Initiate an online payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiatePayment(Request $request)
    {
        $customer = $request->user();
        
        $validator = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_gateway' => 'required|in:bkash,nagad,rocket',
        ]);

        // In a real implementation, you would:
        // 1. Create a payment record with status 'pending'
        // 2. Generate a redirect URL to the payment gateway
        // 3. Return the redirect URL to the client
        
        // For now, we'll just return a placeholder response
        $data = [
            'amount' => $request->amount,
            'payment_gateway' => $request->payment_gateway,
            'redirect_url' => 'https://example.com/payment-gateway', // Placeholder URL
            'payment_id' => uniqid(), // Placeholder payment ID
        ];

        return $this->sendResponse('Payment initiation successful', $data);
    }

    /**
     * Handle the callback from the payment gateway.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function handleCallback(Request $request)
    {
        // Handle the callback from the payment gateway
        // This would typically involve:
        // 1. Verifying the callback authenticity
        // 2. Finding the payment record
        // 3. Updating the payment record status
        // 4. Returning an appropriate response to the gateway
        
        // For now, we'll just return a simple response
        return response('OK', 200);
    }
}