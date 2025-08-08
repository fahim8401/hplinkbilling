<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Web\BaseController;
use App\Models\Company;
use App\Services\ReportService;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        parent::__construct();
        $this->reportService = $reportService;
    }

    /**
     * Display the Super Admin dashboard.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        // Get statistics for the dashboard
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('status', 'active')->count();
        $inactiveCompanies = Company::where('status', 'inactive')->count();
        
        // Get recent companies
        $recentCompanies = Company::orderBy('created_at', 'desc')->limit(5)->get();
        
        // Get company summary data for charts
        $companySummary = $this->getCompanySummaryData();
        
        return $this->view('superadmin.dashboard', [
            'totalCompanies' => $totalCompanies,
            'activeCompanies' => $activeCompanies,
            'inactiveCompanies' => $inactiveCompanies,
            'recentCompanies' => $recentCompanies,
            'companySummary' => $companySummary,
        ]);
    }
    
    /**
     * Get company summary data for charts.
     *
     * @return array
     */
    protected function getCompanySummaryData()
    {
        $companies = Company::all();
        $summary = [];
        
        foreach ($companies as $company) {
            $summary[] = $this->reportService->generateCompanySummary($company);
        }
        
        return $summary;
    }
}