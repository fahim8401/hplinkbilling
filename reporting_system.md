# Reporting System Architecture Plan

## Overview
This document outlines the architecture plan for the reporting system in the ISP Billing & CRM system. The system will provide comprehensive financial, usage, and operational reports with export capabilities and real-time dashboards.

## System Components

### 1. Report Types
- Financial Reports (Account statements, Invoice lists, Bill collection, Income/Expense)
- Commission Reports (Manager commission, Reseller commission)
- Usage Reports (Customer usage, Bandwidth consumption)
- Regulatory Reports (BTRC Customer Export)
- Transaction Reports (PGW response logs, Cash-in-hand)
- Custom Reports (User-defined reports)

### 2. Data Aggregation
- Real-time data aggregation
- Batch processing for large datasets
- Caching strategies
- Data warehouse integration (optional)

### 3. Report Generation
- On-demand report generation
- Scheduled report generation
- Export functionality (Excel, PDF, CSV)
- Report templates and customization

### 4. Dashboard System
- Real-time dashboards
- Customizable widgets
- Charting and visualization
- KPI tracking

## Report Types Implementation

### Financial Reports

#### Account Statement Report
```php
class AccountStatementReport
{
    public function generate($companyId, $startDate, $endDate, $customerId = null)
    {
        $query = Invoice::where('company_id', $companyId)
            ->whereBetween('billing_date', [$startDate, $endDate]);
            
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        
        $invoices = $query->with('customer', 'payments')->get();
        
        $reportData = [
            'company' => Company::find($companyId),
            'period' => ['start' => $startDate, 'end' => $endDate],
            'invoices' => $invoices,
            'summary' => $this->calculateSummary($invoices)
        ];
        
        return $reportData;
    }
    
    private function calculateSummary($invoices)
    {
        $totalInvoiced = $invoices->sum('total_amount');
        $totalPaid = $invoices->sum(function ($invoice) {
            return $invoice->payments->where('status', 'paid')->sum('amount');
        });
        $totalOutstanding = $totalInvoiced - $totalPaid;
        
        return [
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'total_outstanding' => $totalOutstanding
        ];
    }
}
```

#### Invoice List Report
```php
class InvoiceListReport
{
    public function generate($companyId, $filters = [])
    {
        $query = Invoice::where('company_id', $companyId)
            ->with('customer', 'package');
            
        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        
        if (!empty($filters['date_range'])) {
            $query->whereBetween('billing_date', $filters['date_range']);
        }
        
        if (!empty($filters['package_id'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('package_id', $filters['package_id']);
            });
        }
        
        return $query->paginate(50);
    }
}
```

#### Bill Collection Report
```php
class BillCollectionReport
{
    public function generate($companyId, $startDate, $endDate)
    {
        $payments = Payment::where('company_id', $companyId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->with('customer', 'invoice')
            ->get();
            
        $reportData = [
            'company' => Company::find($companyId),
            'period' => ['start' => $startDate, 'end' => $endDate],
            'payments' => $payments,
            'summary' => $this->calculateCollectionSummary($payments),
            'daily_breakdown' => $this->calculateDailyBreakdown($payments)
        ];
        
        return $reportData;
    }
    
    private function calculateCollectionSummary($payments)
    {
        return [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'online_payments' => $payments->where('payment_method', 'online')->sum('amount'),
            'cash_payments' => $payments->where('payment_method', 'receive')->sum('amount'),
            'due_payments' => $payments->where('payment_method', 'due')->sum('amount')
        ];
    }
    
    private function calculateDailyBreakdown($payments)
    {
        return $payments->groupBy(function ($payment) {
            return $payment->payment_date->format('Y-m-d');
        })->map(function ($dailyPayments) {
            return [
                'date' => $dailyPayments->first()->payment_date->format('Y-m-d'),
                'amount' => $dailyPayments->sum('amount'),
                'count' => $dailyPayments->count()
            ];
        })->values();
    }
}
```

#### Income/Expense Report
```php
class IncomeExpenseReport
{
    public function generate($companyId, $startDate, $endDate)
    {
        $income = $this->calculateIncome($companyId, $startDate, $endDate);
        $expenses = $this->calculateExpenses($companyId, $startDate, $endDate);
        
        $reportData = [
            'company' => Company::find($companyId),
            'period' => ['start' => $startDate, 'end' => $endDate],
            'income' => $income,
            'expenses' => $expenses,
            'net_income' => $income['total'] - $expenses['total']
        ];
        
        return $reportData;
    }
    
    private function calculateIncome($companyId, $startDate, $endDate)
    {
        // Customer payments
        $customerPayments = Payment::where('company_id', $companyId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('amount');
            
        // Reseller commissions paid
        $resellerCommissions = ResellerCommission::where('company_id', $companyId)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('commission_amount');
            
        return [
            'customer_payments' => $customerPayments,
            'reseller_commissions' => $resellerCommissions,
            'total' => $customerPayments + $resellerCommissions
        ];
    }
    
    private function calculateExpenses($companyId, $startDate, $endDate)
    {
        // Bandwidth purchases
        $bandwidthPurchases = BandwidthPurchase::where('company_id', $companyId)
            ->whereBetween('purchase_date', [$startDate, $endDate])
            ->sum('price');
            
        // SMS gateway expenses
        $smsExpenses = SMSLog::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'sent')
            ->sum('cost'); // Assuming cost field exists
            
        return [
            'bandwidth_purchases' => $bandwidthPurchases,
            'sms_expenses' => $smsExpenses,
            'total' => $bandwidthPurchases + $smsExpenses
        ];
    }
}
```

### Commission Reports

#### Reseller Commission Report
```php
class ResellerCommissionReport
{
    public function generate($companyId, $startDate, $endDate, $resellerId = null)
    {
        $query = ResellerCommission::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('reseller', 'customer', 'invoice');
            
        if ($resellerId) {
            $query->where('reseller_id', $resellerId);
        }
        
        $commissions = $query->get();
        
        $reportData = [
            'company' => Company::find($companyId),
            'period' => ['start' => $startDate, 'end' => $endDate],
            'commissions' => $commissions,
            'summary' => $this->calculateCommissionSummary($commissions)
        ];
        
        return $reportData;
    }
    
    private function calculateCommissionSummary($commissions)
    {
        return [
            'total_commissions' => $commissions->count(),
            'total_amount' => $commissions->sum('commission_amount'),
            'paid_amount' => $commissions->where('status', 'paid')->sum('commission_amount'),
            'pending_amount' => $commissions->where('status', 'pending')->sum('commission_amount'),
            'reseller_count' => $commissions->unique('reseller_id')->count()
        ];
    }
}
```

#### Manager Commission Report
```php
class ManagerCommissionReport
{
    public function generate($companyId, $startDate, $endDate)
    {
        // Assuming manager commissions are calculated differently
        // This would depend on business logic
        $managerCommissions = ManagerCommission::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('manager', 'reseller')
            ->get();
            
        $reportData = [
            'company' => Company::find($companyId),
            'period' => ['start' => $startDate, 'end' => $endDate],
            'commissions' => $managerCommissions,
            'summary' => $this->calculateManagerCommissionSummary($managerCommissions)
        ];
        
        return $reportData;
    }
    
    private function calculateManagerCommissionSummary($commissions)
    {
        return [
            'total_commissions' => $commissions->count(),
            'total_amount' => $commissions->sum('commission_amount'),
            'paid_amount' => $commissions->where('status', 'paid')->sum('commission_amount'),
            'pending_amount' => $commissions->where('status', 'pending')->sum('commission_amount')
        ];
    }
}
```

### Usage Reports

#### Customer Usage Report
```php
class CustomerUsageReport
{
    public function generate($companyId, $startDate, $endDate, $customerId = null)
    {
        $query = CustomerUsage::where('company_id', $companyId)
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->with('customer');
            
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        
        $usages = $query->get();
        
        $reportData = [
            'company' => Company::find($companyId),
            'period' => ['start' => $startDate, 'end' => $endDate],
            'usages' => $usages,
            'summary' => $this->calculateUsageSummary($usages),
            'daily_breakdown' => $this->calculateDailyUsageBreakdown($usages)
        ];
        
        return $reportData;
    }
    
    private function calculateUsageSummary($usages)
    {
        return [
            'total_download' => $usages->sum('download_bytes'),
            'total_upload' => $usages->sum('upload_bytes'),
            'total_data' => $usages->sum('download_bytes') + $usages->sum('upload_bytes'),
            'customer_count' => $usages->unique('customer_id')->count()
        ];
    }
    
    private function calculateDailyUsageBreakdown($usages)
    {
        return $usages->groupBy(function ($usage) {
            return $usage->usage_date->format('Y-m-d');
        })->map(function ($dailyUsages) {
            return [
                'date' => $dailyUsages->first()->usage_date->format('Y-m-d'),
                'download_bytes' => $dailyUsages->sum('download_bytes'),
                'upload_bytes' => $dailyUsages->sum('upload_bytes'),
                'total_bytes' => $dailyUsages->sum('download_bytes') + $dailyUsages->sum('upload_bytes')
            ];
        })->values();
    }
}
```

#### Bandwidth Consumption Report
```php
class BandwidthConsumptionReport
{
    public function generate($companyId, $startDate, $endDate)
    {
        // Aggregate customer usage data
        $usageSummary = CustomerUsage::where('company_id', $companyId)
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->selectRaw('
                SUM(download_bytes) as total_download,
                SUM(upload_bytes) as total_upload,
                COUNT(DISTINCT customer_id) as active_customers
            ')
            ->first();
            
        // Get bandwidth purchases for comparison
        $bandwidthPurchases = BandwidthPurchase::where('company_id', $companyId)
            ->whereBetween('purchase_date', [$startDate, $endDate])
            ->sum('bandwidth');
            
        $reportData = [
            'company' => Company::find($companyId),
            'period' => ['start' => $startDate, 'end' => $endDate],
            'usage_summary' => $usageSummary,
            'bandwidth_purchases' => $bandwidthPurchases,
            'utilization_percentage' => $this->calculateUtilizationPercentage($usageSummary, $bandwidthPurchases)
        ];
        
        return $reportData;
    }
    
    private function calculateUtilizationPercentage($usageSummary, $bandwidthPurchases)
    {
        if ($bandwidthPurchases <= 0) {
            return 0;
        }
        
        // Convert bytes to Mbps for comparison
        $totalUsageMbps = ($usageSummary->total_download + $usageSummary->total_upload) / (1024 * 1024);
        
        return ($totalUsageMbps / $bandwidthPurchases) * 100;
    }
}
```

### Regulatory Reports

#### BTRC Customer Export Report
```php
class BTRCCustomerExportReport
{
    public function generate($companyId)
    {
        $customers = Customer::where('company_id', $companyId)
            ->where('status', 'active')
            ->with('package')
            ->get();
            
        $reportData = [
            'company' => Company::find($companyId),
            'generated_at' => now(),
            'customers' => $customers,
            'btrc_format_data' => $this->formatForBTRC($customers)
        ];
        
        return $reportData;
    }
    
    private function formatForBTRC($customers)
    {
        // Format data according to BTRC requirements
        return $customers->map(function ($customer) {
            return [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'nid' => $customer->nid,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address, // Assuming this field exists
                'package' => $customer->package->name ?? 'N/A',
                'activation_date' => $customer->activation_date,
                'status' => $customer->status
            ];
        });
    }
}
```

### Transaction Reports

#### PGW Response Log Report
```php
class PGWResponseLogReport
{
    public function generate($companyId, $startDate, $endDate)
    {
        $pgwLogs = PGWResponseLog::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('customer')
            ->get();
            
        $reportData = [
            'company' => Company::find($companyId),
            'period' => ['start' => $startDate, 'end' => $endDate],
            'pgw_logs' => $pgwLogs,
            'summary' => $this->calculatePGWSummary($pgwLogs)
        ];
        
        return $reportData;
    }
    
    private function calculatePGWSummary($pgwLogs)
    {
        return [
            'total_transactions' => $pgwLogs->count(),
            'successful_transactions' => $pgwLogs->where('status', 'success')->count(),
            'failed_transactions' => $pgwLogs->where('status', 'failed')->count(),
            'total_amount' => $pgwLogs->sum('amount'),
            'success_rate' => $pgwLogs->count() > 0 ? 
                ($pgwLogs->where('status', 'success')->count() / $pgwLogs->count()) * 100 : 0
        ];
    }
}
```

#### Cash-in-Hand Report
```php
class CashInHandReport
{
    public function generate($companyId)
    {
        // Calculate current cash position
        $cashInHand = $this->calculateCashInHand($companyId);
        
        $reportData = [
            'company' => Company::find($companyId),
            'generated_at' => now(),
            'cash_in_hand' => $cashInHand,
            'breakdown' => $this->getCashBreakdown($companyId)
        ];
        
        return $reportData;
    }
    
    private function calculateCashInHand($companyId)
    {
        // Sum of all payments received minus expenses
        $totalPayments = Payment::where('company_id', $companyId)
            ->where('status', 'paid')
            ->sum('amount');
            
        $totalExpenses = $this->calculateTotalExpenses($companyId);
        
        return $totalPayments - $totalExpenses;
    }
    
    private function calculateTotalExpenses($companyId)
    {
        // Sum of all expenses (bandwidth, SMS, etc.)
        $bandwidthExpenses = BandwidthPurchase::where('company_id', $companyId)->sum('price');
        $smsExpenses = SMSLog::where('company_id', $companyId)->where('status', 'sent')->sum('cost');
        
        return $bandwidthExpenses + $smsExpenses;
    }
    
    private function getCashBreakdown($companyId)
    {
        return [
            'customer_payments' => Payment::where('company_id', $companyId)
                ->where('status', 'paid')
                ->sum('amount'),
            'bandwidth_purchases' => BandwidthPurchase::where('company_id', $companyId)->sum('price'),
            'sms_expenses' => SMSLog::where('company_id', $companyId)->where('status', 'sent')->sum('cost'),
            'reseller_commissions_paid' => ResellerCommission::where('company_id', $companyId)
                ->where('status', 'paid')
                ->sum('commission_amount')
        ];
    }
}
```

## Data Aggregation and Caching

### Report Data Aggregator
```php
class ReportDataAggregator
{
    public function aggregateDailyData($companyId, $date)
    {
        // Aggregate daily data for performance
        $dailyReport = DailyReport::firstOrCreate([
            'company_id' => $companyId,
            'report_date' => $date
        ], [
            'financial_data' => $this->getDailyFinancialData($companyId, $date),
            'usage_data' => $this->getDailyUsageData($companyId, $date),
            'commission_data' => $this->getDailyCommissionData($companyId, $date)
        ]);
        
        return $dailyReport;
    }
    
    private function getDailyFinancialData($companyId, $date)
    {
        return [
            'invoices_generated' => Invoice::where('company_id', $companyId)
                ->whereDate('billing_date', $date)
                ->count(),
            'payments_received' => Payment::where('company_id', $companyId)
                ->whereDate('payment_date', $date)
                ->where('status', 'paid')
                ->sum('amount'),
            'new_customers' => Customer::where('company_id', $companyId)
                ->whereDate('created_at', $date)
                ->count()
        ];
    }
    
    private function getDailyUsageData($companyId, $date)
    {
        $usage = CustomerUsage::where('company_id', $companyId)
            ->whereDate('usage_date', $date)
            ->selectRaw('SUM(download_bytes + upload_bytes) as total_bytes')
            ->first();
            
        return [
            'total_data_usage' => $usage->total_bytes ?? 0
        ];
    }
    
    private function getDailyCommissionData($companyId, $date)
    {
        return [
            'commissions_earned' => ResellerCommission::where('company_id', $companyId)
                ->whereDate('created_at', $date)
                ->sum('commission_amount')
        ];
    }
}
```

### Report Caching Service
```php
class ReportCachingService
{
    public function cacheReport($reportKey, $data, $ttl = 3600)
    {
        // Cache report data with tenant awareness
        $cacheKey = $this->getTenantCacheKey($reportKey);
        return cache()->put($cacheKey, $data, $ttl);
    }
    
    public function getCachedReport($reportKey)
    {
        $cacheKey = $this->getTenantCacheKey($reportKey);
        return cache()->get($cacheKey);
    }
    
    public function invalidateReportCache($reportKey)
    {
        $cacheKey = $this->getTenantCacheKey($reportKey);
        return cache()->forget($cacheKey);
    }
    
    private function getTenantCacheKey($reportKey)
    {
        if (tenancy()->isInitialized()) {
            return 'tenant:' . tenancy()->company()->id . ':report:' . $reportKey;
        }
        
        return 'global:report:' . $reportKey;
    }
}
```

## Report Generation and Export

### Report Generator Service
```php
class ReportGeneratorService
{
    protected $cachingService;
    
    public function __construct(ReportCachingService $cachingService)
    {
        $this->cachingService = $cachingService;
    }
    
    public function generateReport($reportType, $companyId, $parameters = [])
    {
        // Check cache first
        $cacheKey = $this->generateCacheKey($reportType, $companyId, $parameters);
        $cachedReport = $this->cachingService->getCachedReport($cacheKey);
        
        if ($cachedReport) {
            return $cachedReport;
        }
        
        // Generate report based on type
        $reportData = $this->generateReportData($reportType, $companyId, $parameters);
        
        // Cache the report
        $this->cachingService->cacheReport($cacheKey, $reportData);
        
        return $reportData;
    }
    
    private function generateReportData($reportType, $companyId, $parameters)
    {
        $reportClass = 'App\\Reports\\' . studly_case($reportType) . 'Report';
        
        if (class_exists($reportClass)) {
            $report = new $reportClass();
            return $report->generate($companyId, $parameters);
        }
        
        throw new Exception("Report type {$reportType} not found");
    }
    
    public function exportReport($reportData, $format = 'pdf')
    {
        switch ($format) {
            case 'pdf':
                return $this->generatePDF($reportData);
            case 'excel':
                return $this->generateExcel($reportData);
            case 'csv':
                return $this->generateCSV($reportData);
            default:
                throw new Exception("Unsupported export format: {$format}");
        }
    }
    
    private function generatePDF($reportData)
    {
        $pdf = PDF::loadView('reports.' . $reportData['template'], $reportData);
        return $pdf->download('report-' . time() . '.pdf');
    }
    
    private function generateExcel($reportData)
    {
        return Excel::download(new ReportExport($reportData), 'report-' . time() . '.xlsx');
    }
    
    private function generateCSV($reportData)
    {
        $filename = 'report-' . time() . '.csv';
        $handle = fopen('php://temp', 'w');
        
        // Write CSV data
        foreach ($reportData['data'] as $row) {
            fputcsv($handle, $row);
        }
        
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        
        return response($content)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
    
    private function generateCacheKey($reportType, $companyId, $parameters)
    {
        $key = $reportType . ':' . $companyId;
        
        foreach ($parameters as $key => $value) {
            $key .= ':' . $key . '=' . serialize($value);
        }
        
        return md5($key);
    }
}
```

## Dashboard System

### Dashboard Widget Service
```php
class DashboardWidgetService
{
    public function getFinancialSummaryWidget($companyId)
    {
        $today = now()->toDateString();
        
        return [
            'title' => 'Today\'s Financial Summary',
            'data' => [
                'payments_received' => Payment::where('company_id', $companyId)
                    ->whereDate('payment_date', $today)
                    ->where('status', 'paid')
                    ->sum('amount'),
                'invoices_generated' => Invoice::where('company_id', $companyId)
                    ->whereDate('billing_date', $today)
                    ->count(),
                'new_customers' => Customer::where('company_id', $companyId)
                    ->whereDate('created_at', $today)
                    ->count()
            ]
        ];
    }
    
    public function getUsageSummaryWidget($companyId)
    {
        $today = now()->toDateString();
        
        $usage = CustomerUsage::where('company_id', $companyId)
            ->whereDate('usage_date', $today)
            ->selectRaw('SUM(download_bytes) as download, SUM(upload_bytes) as upload')
            ->first();
            
        return [
            'title' => 'Today\'s Usage Summary',
            'data' => [
                'download_gb' => round(($usage->download ?? 0) / (1024 * 1024 * 1024), 2),
                'upload_gb' => round(($usage->upload ?? 0) / (1024 * 1024 * 1024), 2),
                'total_gb' => round((($usage->download ?? 0) + ($usage->upload ?? 0)) / (1024 * 1024 * 1024), 2)
            ]
        ];
    }
    
    public function getCommissionSummaryWidget($companyId)
    {
        $thisMonth = now()->startOfMonth();
        
        return [
            'title' => 'This Month\'s Commissions',
            'data' => [
                'total_earned' => ResellerCommission::where('company_id', $companyId)
                    ->where('status', 'paid')
                    ->where('paid_at', '>=', $thisMonth)
                    ->sum('commission_amount'),
                'pending_commissions' => ResellerCommission::where('company_id', $companyId)
                    ->where('status', 'pending')
                    ->sum('commission_amount'),
                'resellers_count' => ResellerCommission::where('company_id', $companyId)
                    ->where('paid_at', '>=', $thisMonth)
                    ->distinct('reseller_id')
                    ->count('reseller_id')
            ]
        ];
    }
}
```

## Scheduled Reports

### Scheduled Report Service
```php
class ScheduledReportService
{
    public function scheduleReport($companyId, $reportType, $frequency, $parameters = [])
    {
        return ScheduledReport::create([
            'company_id' => $companyId,
            'report_type' => $reportType,
            'frequency' => $frequency, // daily, weekly, monthly
            'parameters' => $parameters,
            'next_run_at' => $this->calculateNextRun($frequency),
            'is_active' => true
        ]);
    }
    
    public function processScheduledReports()
    {
        $dueReports = ScheduledReport::where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->get();
            
        foreach ($dueReports as $scheduledReport) {
            try {
                $this->generateAndSendScheduledReport($scheduledReport);
                $scheduledReport->next_run_at = $this->calculateNextRun($scheduledReport->frequency);
                $scheduledReport->last_run_at = now();
                $scheduledReport->save();
            } catch (Exception $e) {
                Log::error('Scheduled report failed', [
                    'report_id' => $scheduledReport->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    private function generateAndSendScheduledReport($scheduledReport)
    {
        $reportGenerator = new ReportGeneratorService(new ReportCachingService());
        $reportData = $reportGenerator->generateReport(
            $scheduledReport->report_type,
            $scheduledReport->company_id,
            $scheduledReport->parameters
        );
        
        // Send report via email or other configured method
        $this->sendReport($scheduledReport, $reportData);
    }
    
    private function sendReport($scheduledReport, $reportData)
    {
        // Implementation for sending report via email, etc.
        // This would depend on the company's notification preferences
    }
    
    private function calculateNextRun($frequency)
    {
        switch ($frequency) {
            case 'daily':
                return now()->addDay();
            case 'weekly':
                return now()->addWeek();
            case 'monthly':
                return now()->addMonth();
            default:
                return now()->addDay();
        }
    }
}
```

## Testing Strategy

### Report Tests
```php
class ReportTest extends TestCase
{
    public function test_account_statement_report()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        
        // Create test invoices and payments
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-001',
            'billing_date' => now(),
            'due_date' => now()->addDays(15),
            'base_price' => 1000,
            'vat_amount' => 150,
            'total_amount' => 1150,
            'status' => 'paid'
        ]);
        
        Payment::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'amount' => 1150,
            'payment_method' => 'receive',
            'payment_date' => now(),
            'status' => 'paid'
        ]);
        
        // Generate report
        $report = new AccountStatementReport();
        $reportData = $report->generate($company->id, now()->subMonth(), now());
        
        // Verify report data
        $this->assertEquals(1150, $reportData['summary']['total_invoiced']);
        $this->assertEquals(1150, $reportData['summary']['total_paid']);
        $this->assertEquals(0, $reportData['summary']['total_outstanding']);
    }
    
    public function test_btrc_export_format()
    {
        $company = Company::factory()->create();
        
        // Create test customers
        Customer::factory()->count(5)->create(['company_id' => $company->id]);
        
        // Generate BTRC report
        $report = new BTRCCustomerExportReport();
        $reportData = $report->generate($company->id);
        
        // Verify report format
        $this->assertArrayHasKey('btrc_format_data', $reportData);
        $this->assertCount(5, $reportData['btrc_format_data']);
    }
}
```

This comprehensive reporting system architecture provides a robust foundation for generating various reports in the ISP Billing & CRM system, with support for exporting, caching, and scheduled generation.