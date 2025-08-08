<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends BaseController
{
    /**
     * Display a listing of the payments.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $payments = Payment::all();
        return $this->sendResponse($payments, 'Payments retrieved successfully.');
    }

    /**
     * Store a newly created payment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:receive,due,online',
            'payment_gateway' => 'nullable|string|max:50',
            'transaction_id' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        $request->merge(['operator_id' => auth()->id()]);

        $payment = Payment::create($request->all());
        return $this->sendResponse($payment, 'Payment created successfully.', 201);
    }

    /**
     * Display the specified payment.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Payment $payment)
    {
        return $this->sendResponse($payment, 'Payment retrieved successfully.');
    }

    /**
     * Get payments by customer.
     *
     * @param  int  $customerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByCustomer($customerId)
    {
        $payments = Payment::where('customer_id', $customerId)->get();
        return $this->sendResponse($payments, 'Customer payments retrieved successfully.');
    }

    /**
     * Get payments by method.
     *
     * @param  string  $method
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByMethod($method)
    {
        $payments = Payment::where('payment_method', $method)->get();
        return $this->sendResponse($payments, 'Payments retrieved successfully.');
    }
}