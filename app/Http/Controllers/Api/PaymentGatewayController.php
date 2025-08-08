<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Services\BkashPaymentService;
use App\Services\NagadPaymentService;
use App\Models\PaymentGatewayTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentGatewayController extends BaseController
{
    protected $bkashService;
    protected $nagadService;
    
    public function __construct(
        BkashPaymentService $bkashService,
        NagadPaymentService $nagadService
    ) {
        $this->bkashService = $bkashService;
        $this->nagadService = $nagadService;
    }
    
    public function checkBill(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gateway' => 'required|in:bkash,nagad',
            'customer_id' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }
        
        $gateway = $request->gateway;
        $customerId = $request->customer_id;
        
        if ($gateway === 'bkash') {
            $response = $this->bkashService->checkBill($customerId);
        } else {
            $response = $this->nagadService->checkBill($customerId);
        }
        
        // Log the transaction
        PaymentGatewayTransaction::create([
            'company_id' => auth()->user()->company_id,
            'customer_id' => auth()->user()->id,
            'gateway' => $gateway,
            'transaction_type' => 'check_bill',
            'customer_id_gateway' => $customerId,
            'error_code' => $response['ErrorCode'] ?? null,
            'error_message' => $response['ErrorMessage'] ?? null,
            'name' => $response['name'] ?? null,
            'contact' => $response['contact'] ?? null,
            'bill_amount' => $response['bill_amount'] ?? null,
            'raw_response' => json_encode($response)
        ]);
        
        if ($response['ErrorCode'] == '200') {
            return $this->sendResponse($response, 'Bill check successful');
        } else {
            return $this->sendError('Bill check failed', $response);
        }
    }
    
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gateway' => 'required|in:bkash,nagad',
            'customer_id' => 'required|string',
            'amount' => 'required|numeric',
            'mobile_no' => 'required|string',
            'trx_id' => 'required|string',
            'datetime' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }
        
        $gateway = $request->gateway;
        $customerId = $request->customer_id;
        $amount = $request->amount;
        $mobileNo = $request->mobile_no;
        $trxId = $request->trx_id;
        $datetime = $request->datetime;
        
        if ($gateway === 'bkash') {
            $response = $this->bkashService->processPayment($customerId, $amount, $mobileNo, $trxId, $datetime);
        } else {
            $response = $this->nagadService->processPayment($customerId, $amount, $mobileNo, $trxId, $datetime);
        }
        
        // Log the transaction
        PaymentGatewayTransaction::create([
            'company_id' => auth()->user()->company_id,
            'customer_id' => auth()->user()->id,
            'gateway' => $gateway,
            'transaction_type' => 'payment',
            'customer_id_gateway' => $customerId,
            'amount' => $amount,
            'mobile_no' => $mobileNo,
            'transaction_id' => $trxId,
            'datetime' => $datetime,
            'error_code' => $response['ErrorCode'] ?? null,
            'error_message' => $response['ErrorMessage'] ?? null,
            'result' => $response['result'] ?? null,
            'paymentAmount' => $response['paymentAmount'] ?? null,
            'raw_response' => json_encode($response)
        ]);
        
        if ($response['ErrorCode'] == '200') {
            return $this->sendResponse($response, 'Payment processed successfully');
        } else {
            return $this->sendError('Payment processing failed', $response);
        }
    }
    
    public function searchTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gateway' => 'required|in:bkash,nagad',
            'trx_id' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }
        
        $gateway = $request->gateway;
        $trxId = $request->trx_id;
        
        if ($gateway === 'bkash') {
            $response = $this->bkashService->searchTransaction($trxId);
        } else {
            $response = $this->nagadService->searchTransaction($trxId);
        }
        
        // Log the transaction
        PaymentGatewayTransaction::create([
            'company_id' => auth()->user()->company_id,
            'customer_id' => auth()->user()->id,
            'gateway' => $gateway,
            'transaction_type' => 'search',
            'transaction_id' => $trxId,
            'error_code' => $response['ErrorCode'] ?? null,
            'error_message' => $response['ErrorMessage'] ?? null,
            'name' => $response['name'] ?? null,
            'customer_id_gateway' => $response['customer_id'] ?? null,
            'paid_amount' => $response['paid_amount'] ?? null,
            'datetime' => $response['datetime'] ?? null,
            'trx_id' => $response['trxId'] ?? null,
            'raw_response' => json_encode($response)
        ]);
        
        if ($response['ErrorCode'] == '200') {
            return $this->sendResponse($response, 'Transaction search successful');
        } else {
            return $this->sendError('Transaction search failed', $response);
        }
    }
}