<?php

namespace App\Http\Controllers\Web\CompanyAdmin;

use App\Http\Controllers\Web\BaseController;
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
     * Display the Company Admin dashboard.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        // Get statistics for the dashboard
        $company = tenancy()->company();
        $summary = $this->reportService->generateCompanySummary($company);
        
        // Get recent activities
        $recentActivities = $this->getRecentActivities();
        
        return $this->view('companyadmin.dashboard', [
            'summary' => $summary,
            'recentActivities' => $recentActivities,
        ]);
    }
    
    /**
     * Get recent activities for the company.
     *
     * @return array
     */
    protected function getRecentActivities()
    {
        // This would retrieve recent activities from the database
        // For now, we'll return a placeholder
        return [
            [
                'title' => 'New customer registered',
                'description' => 'John Doe has registered as a new customer.',
                'time' => '2 hours ago',
            ],
            [
                'title' => 'Invoice generated',
                'description' => 'Invoice #INV-20230101-0001 has been generated.',
                'time' => '5 hours ago',
            ],
            [
                'title' => 'Payment received',
                'description' => 'Payment of $50.00 received from Jane Smith.',
                'time' => '1 day ago',
            ],
        ];
    }
}