<?php

namespace App\Http\Controllers\Api\V1\SMS;

use App\Http\Controllers\Api\BaseController;
use App\Models\SMSLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SMSController extends BaseController
{
    /**
     * Display a listing of the SMS logs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $smsLogs = SMSLog::all();
        return $this->sendResponse($smsLogs, 'SMS logs retrieved successfully.');
    }

    /**
     * Display the specified SMS log.
     *
     * @param  \App\Models\SMSLog  $smsLog
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(SMSLog $smsLog)
    {
        return $this->sendResponse($smsLog, 'SMS log retrieved successfully.');
    }

    /**
     * Get SMS logs by status.
     *
     * @param  string  $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByStatus($status)
    {
        $validStatuses = ['pending', 'sent', 'failed'];

        if (!in_array($status, $validStatuses)) {
            return $this->sendError('Invalid status.');
        }

        $smsLogs = SMSLog::where('status', $status)->get();
        return $this->sendResponse($smsLogs, 'SMS logs retrieved successfully.');
    }

    /**
     * Get SMS logs by customer.
     *
     * @param  int  $customerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByCustomer($customerId)
    {
        $smsLogs = SMSLog::where('customer_id', $customerId)->get();
        return $this->sendResponse($smsLogs, 'SMS logs retrieved successfully.');
    }

    /**
     * Get SMS logs by gateway.
     *
     * @param  int  $gatewayId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByGateway($gatewayId)
    {
        $smsLogs = SMSLog::where('gateway_id', $gatewayId)->get();
        return $this->sendResponse($smsLogs, 'SMS logs retrieved successfully.');
    }

    /**
     * Get successful SMS logs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSuccessful()
    {
        $smsLogs = SMSLog::successful()->get();
        return $this->sendResponse($smsLogs, 'Successful SMS logs retrieved successfully.');
    }

    /**
     * Get failed SMS logs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFailed()
    {
        $smsLogs = SMSLog::failed()->get();
        return $this->sendResponse($smsLogs, 'Failed SMS logs retrieved successfully.');
    }

    /**
     * Get pending SMS logs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPending()
    {
        $smsLogs = SMSLog::pending()->get();
        return $this->sendResponse($smsLogs, 'Pending SMS logs retrieved successfully.');
    }

    /**
     * Retry sending failed SMS.
     *
     * @param  \App\Models\SMSLog  $smsLog
     * @return \Illuminate\Http\JsonResponse
     */
    public function retry(SMSLog $smsLog)
    {
        if ($smsLog->isSent()) {
            return $this->sendError('SMS has already been sent successfully.');
        }

        // This would typically involve actually retrying to send the SMS
        // using the SMS service. For now, we'll just update the status
        // as an example.
        $smsLog->status = 'pending';
        $smsLog->save();

        return $this->sendResponse($smsLog, 'SMS retry initiated successfully.');
    }
}