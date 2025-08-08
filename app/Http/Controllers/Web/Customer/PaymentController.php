<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Web\BaseController;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{
    /**
     * Display a listing of payments.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $customer = auth()->user();
        $payments = Payment::where('customer_id', $customer->id)->get();
        
        return $this->view('customer.payments.index', compact('payments'));
    }

    /**
     * Show the form for making a payment.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return $this->view('customer.payments.create');
    }

    /**
     * Process an online payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processOnlinePayment(Request $request)
    {
        $customer = auth()->user();
        
        $validator = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_gateway' => 'required|in:bkash,nagad,rocket',
        ]);

        // Process the online payment
        // This would typically involve redirecting to the payment gateway
        // For now, we'll just return a success response
        
        // In a real implementation, you would:
        // 1. Create a payment record with status 'pending'
        // 2. Redirect to the payment gateway with necessary parameters
        // 3. Handle the callback from the payment gateway
        // 4. Update the payment record status based on the gateway response
        
        return $this->redirectSuccess('customer.payments.index', 'Payment processed successfully.');
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