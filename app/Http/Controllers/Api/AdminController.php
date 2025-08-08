<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ReportService;
use Illuminate\Http\Request;

class AdminController extends BaseController
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get dashboard statistics for the authenticated admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboardStats(Request $request)
    {
        $admin = $request->user();
        
        // Get statistics based on admin type
        if ($admin->user_type === 'super_admin') {
            return $this->getSuperAdminStats($request);
        } else if ($admin->user_type === 'company_admin') {
            return $this->getCompanyAdminStats($request);
        } else {
            return $this->sendForbidden('You do not have permission to access this resource');
        }
    }

    /**
     * Get statistics for super admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function getSuperAdminStats(Request $request)
    {
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('status', 'active')->count();
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        
        $data = [
            'total_companies' => $totalCompanies,
            'active_companies' => $activeCompanies,
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
        ];
        
        return $this->sendResponse('Super admin statistics retrieved successfully', $data);
    }

    /**
     * Get statistics for company admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function getCompanyAdminStats(Request $request)
    {
        $admin = $request->user();
        $company = $admin->company;
        
        $totalCustomers = Customer::where('company_id', $company->id)->count();
        $activeCustomers = Customer::where('company_id', $company->id)->where('status', 'active')->count();
        
        // Get financial data for the current month
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        
        $totalInvoices = Invoice::where('company_id', $company->id)
            ->whereBetween('billing_date', [$startDate, $endDate])
            ->count();
            
        $totalPayments = Payment::where('company_id', $company->id)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');
        
        $data = [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'total_invoices' => $totalInvoices,
            'total_payments' => $totalPayments,
        ];
        
        return $this->sendResponse('Company admin statistics retrieved successfully', $data);
    }

    /**
     * Get financial report for the authenticated admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function financialReport(Request $request)
    {
        $admin = $request->user();
        
        // Get date range from request or use defaults
        $startDate = $request->input('start_date', now()->subMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        
        // Generate financial report
        if ($admin->user_type === 'super_admin') {
            $report = $this->reportService->generateSuperAdminFinancialReport($startDate, $endDate);
        } else if ($admin->user_type === 'company_admin') {
            $report = $this->reportService->generateCompanyFinancialReport($admin->company, $startDate, $endDate);
        } else {
            return $this->sendForbidden('You do not have permission to access this resource');
        }
        
        return $this->sendResponse('Financial report retrieved successfully', $report);
    }

    /**
     * Get customer report for the authenticated admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function customerReport(Request $request)
    {
        $admin = $request->user();
        
        // Get date range from request or use defaults
        $startDate = $request->input('start_date', now()->subMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        
        // Generate customer report
        if ($admin->user_type === 'super_admin') {
            $report = $this->reportService->generateSuperAdminCustomerReport($startDate, $endDate);
        } else if ($admin->user_type === 'company_admin') {
            $report = $this->reportService->generateCompanyCustomerReport($admin->company, $startDate, $endDate);
        } else {
            return $this->sendForbidden('You do not have permission to access this resource');
        }
        
        return $this->sendResponse('Customer report retrieved successfully', $report);
    }

    /**
     * Get company list (super admin only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function companies(Request $request)
    {
        $admin = $request->user();
        
        if ($admin->user_type !== 'super_admin') {
            return $this->sendForbidden('You do not have permission to access this resource');
        }
        
        $companies = Company::all();
        
        return $this->sendResponse('Companies retrieved successfully', $companies);
    }

    /**
     * Get company details (super admin only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\JsonResponse
     */
    public function companyDetails(Request $request, Company $company)
    {
        $admin = $request->user();
        
        if ($admin->user_type !== 'super_admin') {
            return $this->sendForbidden('You do not have permission to access this resource');
        }
        
        // Load company with related data
        $company->loadCount(['customers', 'users']);
        
        return $this->sendResponse('Company details retrieved successfully', $company);
    }
}