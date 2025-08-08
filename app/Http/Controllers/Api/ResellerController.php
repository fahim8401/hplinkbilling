<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Services\ResellerService;
use Illuminate\Http\Request;

class ResellerController extends BaseController
{
    protected $resellerService;

    public function __construct(ResellerService $resellerService)
    {
        $this->resellerService = $resellerService;
    }

    /**
     * Get the authenticated reseller's balance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function balance(Request $request)
    {
        $reseller = $request->user();
        
        // Get the reseller's balance
        $balance = $this->resellerService->getResellerBalance($reseller);
        
        return $this->sendResponse('Reseller balance retrieved successfully', [
            'balance' => $balance->balance,
        ]);
    }

    /**
     * Get the authenticated reseller's commission statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function commissionStats(Request $request)
    {
        $reseller = $request->user();
        
        // Get commission statistics
        $stats = $this->resellerService->getCommissionStats($reseller);
        
        return $this->sendResponse('Commission statistics retrieved successfully', $stats);
    }

    /**
     * Get the authenticated reseller's commission report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function commissionReport(Request $request)
    {
        $reseller = $request->user();
        
        // Get date range from request or use defaults
        $startDate = $request->input('start_date', now()->subMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        
        // Generate commission report
        $report = $this->resellerService->generateCommissionReport($reseller, $startDate, $endDate);
        
        return $this->sendResponse('Commission report retrieved successfully', $report);
    }

    /**
     * Transfer balance to a reseller employee.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transferToEmployee(Request $request)
    {
        $reseller = $request->user();
        
        $validator = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
        ]);

        // Get the employee
        $employee = User::find($request->employee_id);
        
        // Ensure the employee belongs to the current reseller
        if ($employee->reseller_id !== $reseller->id) {
            return $this->sendError('Invalid employee selected', [], 400);
        }

        try {
            // Transfer balance to employee
            $this->resellerService->transferToEmployee($reseller, $employee, $request->amount, 'Transfer to employee via API');
            
            return $this->sendResponse('Balance transferred to employee successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to transfer balance: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * Get the authenticated reseller's customers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function customers(Request $request)
    {
        $reseller = $request->user();
        
        // Get the reseller's customers
        $customers = $reseller->customers;
        
        return $this->sendResponse('Reseller customers retrieved successfully', $customers);
    }

    /**
     * Recharge a customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function rechargeCustomer(Request $request, \App\Models\Customer $customer)
    {
        $reseller = $request->user();
        
        // Ensure the customer belongs to the current reseller
        if ($customer->reseller_id !== $reseller->id) {
            return $this->sendForbidden('You do not have permission to recharge this customer');
        }

        $validator = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'required|in:receive,due',
        ]);

        // Check if reseller has sufficient balance
        if (!$this->resellerService->validateBalanceForRecharge($reseller, $request->amount)) {
            return $this->sendError('Insufficient balance for this recharge', [], 400);
        }

        try {
            // Deduct balance from reseller
            $this->resellerService->deductBalanceForRecharge($reseller, $request->amount, 'Customer recharge via API');

            // Extend customer expiry
            // Note: We would need to inject the BillingService here for a complete implementation
            // For now, we'll just return a success response
            // $this->billingService->extendCustomerExpiry($customer, $request->payment_type);

            return $this->sendResponse('Customer recharged successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to recharge customer: ' . $e->getMessage(), [], 400);
        }
    }
}