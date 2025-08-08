<?php

namespace App\Http\Controllers\Web\Reseller;

use App\Http\Controllers\Web\BaseController;
use App\Models\ResellerCommission;
use App\Services\ResellerService;
use Illuminate\Http\Request;

class CommissionController extends BaseController
{
    protected $resellerService;

    public function __construct(ResellerService $resellerService)
    {
        parent::__construct();
        $this->resellerService = $resellerService;
    }

    /**
     * Display a listing of commissions.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $reseller = auth()->user();
        $commissions = ResellerCommission::where('reseller_id', $reseller->id)->get();
        
        return $this->view('reseller.commissions.index', compact('reseller', 'commissions'));
    }

    /**
     * Display the specified commission.
     *
     * @param  \App\Models\ResellerCommission  $commission
     * @return \Illuminate\Contracts\View\View
     */
    public function show(ResellerCommission $commission)
    {
        $reseller = auth()->user();
        
        // Ensure the commission belongs to the current reseller
        if ($commission->reseller_id !== $reseller->id) {
            abort(404);
        }

        return $this->view('reseller.commissions.show', compact('commission'));
    }

    /**
     * Generate a commission report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function report(Request $request)
    {
        $reseller = auth()->user();
        
        // Get date range from request or use defaults
        $startDate = $request->input('start_date', now()->subMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        
        // Generate commission report
        $report = $this->resellerService->generateCommissionReport($reseller, $startDate, $endDate);
        
        return $this->view('reseller.commissions.report', compact('reseller', 'report', 'startDate', 'endDate'));
    }

    /**
     * Request payout for pending commissions.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function requestPayout()
    {
        $reseller = auth()->user();
        
        try {
            // Payout commissions for this reseller (not immediate)
            $this->resellerService->payoutCommission($reseller, false);
            
            return $this->backSuccess('Commission payout request submitted successfully.');
        } catch (\Exception $e) {
            return $this->backError('Failed to request commission payout: ' . $e->getMessage());
        }
    }

    /**
     * Get commission statistics as JSON.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $reseller = auth()->user();
        
        // Get commission statistics
        $stats = $this->resellerService->getCommissionStats($reseller);
        
        return $this->jsonSuccess('Commission statistics retrieved successfully.', $stats);
    }
}