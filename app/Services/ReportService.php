<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ResellerCommission;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Generate a company summary report.
     *
     * @param Company $company
     * @return array
     */
    public function generateCompanySummary(Company $company)
    {
        $activeCustomers = Customer::where('company_id', $company->id)
            ->where('status', 'active')
            ->count();

        $totalRevenue = Invoice::where('company_id', $company->id)
            ->where('status', 'paid')
            ->sum('total_amount');

        $totalDue = Invoice::where('company_id', $company->id)
            ->where('status', 'unpaid')
            ->sum('total_amount');

        $resellerStats = $this->generateResellerStats($company);

        return [
            'company_name' => $company->name,
            'active_customers' => $activeCustomers,
            'total_revenue' => $totalRevenue,
            'total_due' => $totalDue,
            'reseller_stats' => $resellerStats,
        ];
    }

    /**
     * Generate reseller statistics for a company.
     *
     * @param Company $company
     * @return array
     */
    protected function generateResellerStats(Company $company)
    {
        $resellers = DB::table('users')
            ->where('company_id', $company->id)
            ->where('user_type', 'reseller')
            ->get();

        $stats = [];

        foreach ($resellers as $reseller) {
            $totalCommission = ResellerCommission::where('reseller_id', $reseller->id)
                ->sum('commission_amount');

            $pendingCommission = ResellerCommission::where('reseller_id', $reseller->id)
                ->where('status', 'pending')
                ->sum('commission_amount');

            $paidCommission = ResellerCommission::where('reseller_id', $reseller->id)
                ->where('status', 'paid')
                ->sum('commission_amount');

            $stats[] = [
                'reseller_id' => $reseller->id,
                'reseller_name' => $reseller->name,
                'total_commission' => $totalCommission,
                'pending_commission' => $pendingCommission,
                'paid_commission' => $paidCommission,
            ];
        }

        return $stats;
    }

    /**
     * Generate an invoice summary report.
     *
     * @param Company $company
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function generateInvoiceSummary(Company $company, $startDate, $endDate)
    {
        return Invoice::where('company_id', $company->id)
            ->whereBetween('billing_date', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_invoices,
                SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_invoices,
                SUM(CASE WHEN status = "unpaid" THEN 1 ELSE 0 END) as unpaid_invoices,
                SUM(total_amount) as total_amount,
                SUM(CASE WHEN status = "paid" THEN total_amount ELSE 0 END) as collected_amount,
                SUM(CASE WHEN status = "unpaid" THEN total_amount ELSE 0 END) as outstanding_amount
            ')
            ->first();
    }

    /**
     * Generate a payment collection report.
     *
     * @param Company $company
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function generatePaymentCollectionReport(Company $company, $startDate, $endDate)
    {
        return Payment::where('company_id', $company->id)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_payments,
                SUM(amount) as total_collected,
                payment_method,
                DATE(payment_date) as payment_date
            ')
            ->groupBy('payment_method', 'payment_date')
            ->orderBy('payment_date')
            ->get();
    }

    /**
     * Generate a reseller commission report.
     *
     * @param Company $company
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function generateResellerCommissionReport(Company $company, $startDate, $endDate)
    {
        return ResellerCommission::where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                reseller_id,
                COUNT(*) as total_commissions,
                SUM(commission_amount) as total_earned,
                SUM(CASE WHEN status = "pending" THEN commission_amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = "paid" THEN commission_amount ELSE 0 END) as paid_amount
            ')
            ->groupBy('reseller_id')
            ->get();
    }

    /**
     * Generate a support ticket report.
     *
     * @param Company $company
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function generateSupportTicketReport(Company $company, $startDate, $endDate)
    {
        return SupportTicket::where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_tickets,
                SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open_tickets,
                SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress_tickets,
                SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) as resolved_tickets,
                SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as closed_tickets,
                category
            ')
            ->groupBy('category')
            ->get();
    }

    /**
     * Generate a customer churn report.
     *
     * @param Company $company
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function generateCustomerChurnReport(Company $company, $startDate, $endDate)
    {
        $customers = Customer::where('company_id', $company->id)
            ->whereBetween('deleted_at', [$startDate, $endDate])
            ->get();

        $churnedCustomers = [];
        $churnedRevenue = 0;

        foreach ($customers as $customer) {
            $totalRevenue = $customer->payments()->sum('amount');
            $churnedRevenue += $totalRevenue;

            $churnedCustomers[] = [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'total_revenue' => $totalRevenue,
                'churn_date' => $customer->deleted_at,
            ];
        }

        return [
            'total_churned_customers' => count($churnedCustomers),
            'total_churned_revenue' => $churnedRevenue,
            'churned_customers' => $churnedCustomers,
        ];
    }

    /**
     * Generate a bandwidth usage report.
     *
     * @param Company $company
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function generateBandwidthUsageReport(Company $company, $startDate, $endDate)
    {
        // This would require implementation based on the actual bandwidth usage data
        // For now, we'll return a placeholder
        return [
            'total_bandwidth_used' => 0,
            'total_bandwidth_purchased' => 0,
            'bandwidth_utilization_percentage' => 0,
        ];
    }

    /**
     * Generate a profit margin report.
     *
     * @param Company $company
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function generateProfitMarginReport(Company $company, $startDate, $endDate)
    {
        // This would require implementation based on the actual financial data
        // For now, we'll return a placeholder
        return [
            'total_revenue' => 0,
            'total_costs' => 0,
            'total_profit' => 0,
            'profit_margin_percentage' => 0,
        ];
    }
}