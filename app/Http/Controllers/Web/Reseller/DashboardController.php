<?php

namespace App\Http\Controllers\Web\Reseller;

use App\Http\Controllers\Web\BaseController;
use App\Models\Customer;
use App\Services\ResellerService;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    protected $resellerService;

    public function __construct(ResellerService $resellerService)
    {
        parent::__construct();
        $this->resellerService = $resellerService;
    }

    /**
     * Display the Reseller dashboard.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        // Get the authenticated reseller
        $reseller = auth()->user();

        // Get the reseller's balance
        $balance = $this->resellerService->getResellerBalance($reseller);

        // Get the reseller's customers
        $customers = Customer::where('reseller_id', $reseller->id)->get();

        // Get recent activities
        $recentActivities = $this->getRecentActivities();

        return $this->view('reseller.dashboard', [
            'reseller' => $reseller,
            'balance' => $balance,
            'customers' => $customers,
            'recentActivities' => $recentActivities,
        ]);
    }
    
    /**
     * Get recent activities for the reseller.
     *
     * @return array
     */
    protected function getRecentActivities()
    {
        // This would retrieve recent activities from the database
        // For now, we'll return a placeholder
        return [
            [
                'title' => 'New customer assigned',
                'description' => 'John Doe has been assigned to you as a new customer.',
                'time' => '2 hours ago',
            ],
            [
                'title' => 'Commission earned',
                'description' => 'You earned $10.00 commission from Jane Smith\'s payment.',
                'time' => '5 hours ago',
            ],
            [
                'title' => 'Balance added',
                'description' => 'Admin added $100.00 to your balance.',
                'time' => '1 day ago',
            ],
        ];
    }
}