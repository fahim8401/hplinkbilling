<?php

namespace App\Http\Controllers\Web\Reseller;

use App\Http\Controllers\Web\BaseController;
use App\Models\User;
use App\Services\ResellerService;
use Illuminate\Http\Request;

class BalanceController extends BaseController
{
    protected $resellerService;

    public function __construct(ResellerService $resellerService)
    {
        parent::__construct();
        $this->resellerService = $resellerService;
    }

    /**
     * Display the reseller's balance.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $reseller = auth()->user();
        $balance = $this->resellerService->getResellerBalance($reseller);
        $transferHistory = $this->resellerService->getTransferHistory($reseller);
        
        return $this->view('reseller.balance.index', compact('reseller', 'balance', 'transferHistory'));
    }

    /**
     * Transfer balance to a reseller employee.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function transferToEmployee(Request $request)
    {
        $reseller = auth()->user();
        
        $validator = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
        ]);

        // Get the employee
        $employee = User::find($request->employee_id);
        
        // Ensure the employee belongs to the current reseller
        if ($employee->reseller_id !== $reseller->id) {
            return $this->backError('Invalid employee selected.');
        }

        try {
            // Transfer balance to employee
            $this->resellerService->transferToEmployee($reseller, $employee, $request->amount, 'Transfer to employee');
            
            return $this->backSuccess('Balance transferred to employee successfully.');
        } catch (\Exception $e) {
            return $this->backError('Failed to transfer balance: ' . $e->getMessage());
        }
    }

    /**
     * Get the reseller's balance as JSON.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalance()
    {
        $reseller = auth()->user();
        $balance = $this->resellerService->getResellerBalance($reseller);
        
        return $this->jsonSuccess('Balance retrieved successfully.', [
            'balance' => $balance->balance,
        ]);
    }
}