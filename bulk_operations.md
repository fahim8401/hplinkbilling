# Bulk Operations Implementation Plan

## Overview
This document outlines the implementation plan for bulk operations in the ISP Billing & CRM system. The system will support customer import via CSV/XLS, bulk date extension, bulk package changes, and bulk enable/disable operations with proper validation and error reporting.

## System Components

### 1. Customer Import
- CSV/XLS file upload and parsing
- Field mapping and validation
- Duplicate detection and handling
- Error reporting and correction
- Queue-based processing for large files

### 2. Bulk Date Extension
- Select customers by filters or selection
- Extend expiry dates by specified period
- Validation of date calculations
- Confirmation and rollback options

### 3. Bulk Package Change
- Select customers by filters or selection
- Change packages with validation
- Proration calculation if applicable
- Confirmation and rollback options

### 4. Bulk Enable/Disable
- Select customers by filters or selection
- Enable or disable customer accounts
- MikroTik integration for service activation
- Status change logging

## Customer Import Implementation

### Import Model
```php
class BulkImport extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'file_name',
        'file_path',
        'total_records',
        'success_records',
        'failed_records',
        'status',
        'error_log'
    ];
    
    protected $casts = [
        'total_records' => 'integer',
        'success_records' => 'integer',
        'failed_records' => 'integer'
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
```

### Import Service
```php
class CustomerImportService
{
    public function processImport(BulkImport $import)
    {
        try {
            $import->update(['status' => 'processing']);
            
            // Parse the uploaded file
            $data = $this->parseFile($import->file_path);
            
            $import->update(['total_records' => count($data)]);
            
            $successCount = 0;
            $failedCount = 0;
            $errors = [];
            
            // Process each row
            foreach ($data as $index => $row) {
                try {
                    $this->processRow($row, $import->company_id);
                    $successCount++;
                } catch (Exception $e) {
                    $failedCount++;
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }
            
            // Update import record
            $import->update([
                'success_records' => $successCount,
                'failed_records' => $failedCount,
                'status' => $failedCount > 0 ? 'completed_with_errors' : 'completed',
                'error_log' => json_encode($errors),
                'completed_at' => now()
            ]);
            
            // Send notification
            $this->sendCompletionNotification($import);
            
        } catch (Exception $e) {
            $import->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
                'completed_at' => now()
            ]);
            
            Log::error('Customer import failed', [
                'import_id' => $import->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function parseFile($filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        if ($extension === 'csv') {
            return $this->parseCSV($filePath);
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            return $this->parseExcel($filePath);
        }
        
        throw new Exception('Unsupported file format');
    }
    
    private function parseCSV($filePath)
    {
        $data = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new Exception('Failed to open CSV file');
        }
        
        // Get headers
        $headers = fgetcsv($handle);
        
        // Process rows
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = array_combine($headers, $row);
        }
        
        fclose($handle);
        
        return $data;
    }
    
    private function parseExcel($filePath)
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        $data = [];
        $headers = [];
        
        foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
            $rowData = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            foreach ($cellIterator as $cellIndex => $cell) {
                if ($rowIndex === 1) {
                    // Header row
                    $headers[] = $cell->getValue();
                } else {
                    // Data row
                    $rowData[$headers[$cellIndex]] = $cell->getValue();
                }
            }
            
            if ($rowIndex > 1) {
                $data[] = $rowData;
            }
        }
        
        return $data;
    }
    
    private function processRow($row, $companyId)
    {
        // Validate required fields
        $this->validateRow($row);
        
        // Check for duplicates
        if ($this->isDuplicate($row, $companyId)) {
            throw new Exception('Duplicate customer record');
        }
        
        // Create customer
        $customer = Customer::create([
            'company_id' => $companyId,
            'name' => $row['name'],
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'username' => $row['username'],
            'password' => bcrypt($row['password'] ?? Str::random(8)),
            'nid' => $row['nid'] ?? null,
            'ip_address' => $row['ip'] ?? null,
            'mac_address' => $row['mac'] ?? null,
            'package_id' => $this->getPackageId($row['package'], $companyId),
            'pop_id' => $this->getPopId($row['pop'], $companyId),
            'router_id' => $this->getRouterId($row['router'], $companyId),
            'reseller_id' => $this->getResellerId($row['reseller'], $companyId),
            'customer_type' => $row['type'] ?? 'home',
            'status' => $row['status'] ?? 'active',
            'activation_date' => $row['activation_date'] ?? now(),
            'expiry_date' => $row['expiry_date'] ?? now()->addMonth(),
            'notes' => $row['notes'] ?? null
        ]);
        
        // Create PPPoE user if needed
        if ($customer->router_id) {
            $this->createPPPoEUser($customer);
        }
        
        return $customer;
    }
    
    private function validateRow($row)
    {
        $requiredFields = ['name', 'username'];
        
        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Validate username uniqueness
        if (Customer::where('username', $row['username'])->exists()) {
            throw new Exception("Username already exists: {$row['username']}");
        }
        
        // Validate email format if provided
        if (!empty($row['email']) && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format: {$row['email']}");
        }
        
        // Validate IP address if provided
        if (!empty($row['ip']) && !filter_var($row['ip'], FILTER_VALIDATE_IP)) {
            throw new Exception("Invalid IP address: {$row['ip']}");
        }
        
        // Validate MAC address if provided
        if (!empty($row['mac']) && !filter_var($row['mac'], FILTER_VALIDATE_MAC)) {
            throw new Exception("Invalid MAC address: {$row['mac']}");
        }
    }
    
    private function isDuplicate($row, $companyId)
    {
        return Customer::where('company_id', $companyId)
            ->where('username', $row['username'])
            ->exists();
    }
    
    private function getPackageId($packageName, $companyId)
    {
        if (empty($packageName)) {
            return null;
        }
        
        $package = Package::where('company_id', $companyId)
            ->where('name', $packageName)
            ->first();
            
        return $package ? $package->id : null;
    }
    
    private function getPopId($popName, $companyId)
    {
        if (empty($popName)) {
            return null;
        }
        
        $pop = POP::where('company_id', $companyId)
            ->where('name', $popName)
            ->first();
            
        return $pop ? $pop->id : null;
    }
    
    private function getRouterId($routerName, $companyId)
    {
        if (empty($routerName)) {
            return null;
        }
        
        $router = MikrotikRouter::where('company_id', $companyId)
            ->where('name', $routerName)
            ->first();
            
        return $router ? $router->id : null;
    }
    
    private function getResellerId($resellerName, $companyId)
    {
        if (empty($resellerName)) {
            return null;
        }
        
        $reseller = User::where('company_id', $companyId)
            ->where('name', $resellerName)
            ->where('user_type', 'reseller')
            ->first();
            
        return $reseller ? $reseller->id : null;
    }
    
    private function createPPPoEUser($customer)
    {
        try {
            $userService = new PPPoEUserService();
            $userService->createUser($customer);
        } catch (Exception $e) {
            Log::warning('Failed to create PPPoE user during import', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function sendCompletionNotification($import)
    {
        // Send notification to user who initiated the import
        // This could be an email, in-app notification, or both
        Notification::send($import->user, new ImportCompletedNotification($import));
    }
}
```

## Queue-Based Processing

### Import Job
```php
class ProcessCustomerImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $import;
    
    public function __construct(BulkImport $import)
    {
        $this->import = $import;
    }
    
    public function handle()
    {
        $importService = new CustomerImportService();
        $importService->processImport($this->import);
    }
    
    public function failed(Exception $exception)
    {
        // Handle job failure
        $this->import->update([
            'status' => 'failed',
            'error_log' => $exception->getMessage(),
            'completed_at' => now()
        ]);
        
        Log::error('Customer import job failed', [
            'import_id' => $this->import->id,
            'error' => $exception->getMessage()
        ]);
    }
}
```

## Bulk Date Extension

### Date Extension Service
```php
class BulkDateExtensionService
{
    public function extendDates($customerIds, $months, $companyId)
    {
        $customers = Customer::whereIn('id', $customerIds)
            ->where('company_id', $companyId)
            ->get();
            
        $updatedCount = 0;
        $errors = [];
        
        foreach ($customers as $customer) {
            try {
                $this->extendCustomerDate($customer, $months);
                $updatedCount++;
            } catch (Exception $e) {
                $errors[] = "Customer {$customer->id}: " . $e->getMessage();
            }
        }
        
        return [
            'updated_count' => $updatedCount,
            'errors' => $errors
        ];
    }
    
    private function extendCustomerDate($customer, $months)
    {
        // Calculate new expiry date
        $newExpiryDate = $this->calculateNewExpiryDate($customer->expiry_date, $months);
        
        // Update customer
        $customer->expiry_date = $newExpiryDate;
        $customer->save();
        
        // Log the change
        CustomerDateExtensionLog::create([
            'customer_id' => $customer->id,
            'old_expiry_date' => $customer->getOriginal('expiry_date'),
            'new_expiry_date' => $newExpiryDate,
            'extended_months' => $months,
            'extended_by' => auth()->id()
        ]);
    }
    
    private function calculateNewExpiryDate($currentExpiryDate, $months)
    {
        $date = new DateTime($currentExpiryDate);
        $date->modify("+{$months} months");
        return $date->format('Y-m-d');
    }
}
```

## Bulk Package Change

### Package Change Service
```php
class BulkPackageChangeService
{
    public function changePackages($customerIds, $newPackageId, $companyId, $prorate = false)
    {
        $customers = Customer::whereIn('id', $customerIds)
            ->where('company_id', $companyId)
            ->get();
            
        $newPackage = Package::findOrFail($newPackageId);
        $updatedCount = 0;
        $errors = [];
        
        foreach ($customers as $customer) {
            try {
                $this->changeCustomerPackage($customer, $newPackage, $prorate);
                $updatedCount++;
            } catch (Exception $e) {
                $errors[] = "Customer {$customer->id}: " . $e->getMessage();
            }
        }
        
        return [
            'updated_count' => $updatedCount,
            'errors' => $errors
        ];
    }
    
    private function changeCustomerPackage($customer, $newPackage, $prorate)
    {
        $oldPackage = $customer->package;
        
        // Calculate proration if needed
        if ($prorate && $oldPackage) {
            $prorationService = new ProrationService();
            $proration = $prorationService->calculateProration($oldPackage, $newPackage, now());
            
            // Handle proration charges/credits
            if ($proration['net_amount'] > 0) {
                // Charge customer for difference
                $this->chargeCustomer($customer, $proration['net_amount']);
            } elseif ($proration['net_amount'] < 0) {
                // Credit customer for difference
                $this->creditCustomer($customer, abs($proration['net_amount']));
            }
        }
        
        // Update customer package
        $customer->package_id = $newPackage->id;
        $customer->save();
        
        // Update PPPoE user profile if needed
        if ($customer->router_id) {
            $this->updatePPPoEUserProfile($customer, $newPackage);
        }
        
        // Log the change
        CustomerPackageChangeLog::create([
            'customer_id' => $customer->id,
            'old_package_id' => $oldPackage ? $oldPackage->id : null,
            'new_package_id' => $newPackage->id,
            'changed_by' => auth()->id(),
            'prorated' => $prorate
        ]);
    }
    
    private function updatePPPoEUserProfile($customer, $newPackage)
    {
        try {
            $userService = new PPPoEUserService();
            $userService->changeUserProfile($customer, $newPackage);
        } catch (Exception $e) {
            Log::warning('Failed to update PPPoE user profile during bulk package change', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function chargeCustomer($customer, $amount)
    {
        // Create invoice for proration charge
        $invoice = Invoice::create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'invoice_number' => 'PR-' . time() . '-' . $customer->id,
            'billing_date' => now(),
            'due_date' => now(),
            'base_price' => $amount,
            'vat_amount' => 0,
            'total_amount' => $amount,
            'status' => 'unpaid',
            'notes' => 'Proration charge for package change'
        ]);
        
        return $invoice;
    }
    
    private function creditCustomer($customer, $amount)
    {
        // Create credit note for proration credit
        $creditNote = CreditNote::create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'credit_number' => 'CR-' . time() . '-' . $customer->id,
            'credit_date' => now(),
            'amount' => $amount,
            'notes' => 'Proration credit for package change'
        ]);
        
        return $creditNote;
    }
}
```

## Bulk Enable/Disable

### Enable/Disable Service
```php
class BulkEnableDisableService
{
    public function enableCustomers($customerIds, $companyId)
    {
        return $this->changeCustomerStatus($customerIds, $companyId, 'active');
    }
    
    public function disableCustomers($customerIds, $companyId)
    {
        return $this->changeCustomerStatus($customerIds, $companyId, 'suspended');
    }
    
    private function changeCustomerStatus($customerIds, $companyId, $newStatus)
    {
        $customers = Customer::whereIn('id', $customerIds)
            ->where('company_id', $companyId)
            ->get();
            
        $updatedCount = 0;
        $errors = [];
        
        foreach ($customers as $customer) {
            try {
                $this->updateCustomerStatus($customer, $newStatus);
                $updatedCount++;
            } catch (Exception $e) {
                $errors[] = "Customer {$customer->id}: " . $e->getMessage();
            }
        }
        
        return [
            'updated_count' => $updatedCount,
            'errors' => $errors
        ];
    }
    
    private function updateCustomerStatus($customer, $newStatus)
    {
        $oldStatus = $customer->status;
        
        // Update customer status
        $customer->status = $newStatus;
        $customer->save();
        
        // Update PPPoE user if needed
        if ($customer->router_id) {
            $userService = new PPPoEUserService();
            
            if ($newStatus === 'active') {
                $userService->enableUser($customer);
            } elseif ($newStatus === 'suspended') {
                $userService->disableUser($customer);
            }
        }
        
        // Log the change
        CustomerStatusChangeLog::create([
            'customer_id' => $customer->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id(),
            'reason' => 'Bulk operation'
        ]);
    }
}
```

## File Upload and Storage

### Import Controller
```php
class BulkImportController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx|max:10240' // 10MB max
        ]);
        
        $file = $request->file('file');
        
        // Store file
        $filePath = $file->store('bulk-imports', 'private');
        
        // Create import record
        $import = BulkImport::create([
            'company_id' => tenancy()->company()->id,
            'user_id' => auth()->id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'status' => 'pending'
        ]);
        
        // Dispatch job to process import
        ProcessCustomerImportJob::dispatch($import);
        
        return response()->json([
            'message' => 'Import started successfully',
            'import_id' => $import->id
        ]);
    }
    
    public function getStatus(BulkImport $import)
    {
        // Ensure import belongs to current company
        if ($import->company_id !== tenancy()->company()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        return response()->json($import);
    }
    
    public function getHistory()
    {
        $imports = BulkImport::where('company_id', tenancy()->company()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return response()->json($imports);
    }
}
```

## Progress Tracking and Notifications

### Import Progress Tracking
```php
class ImportProgressService
{
    public function updateProgress(BulkImport $import, $processed, $total)
    {
        $percentage = ($processed / $total) * 100;
        
        // Update import record with progress
        $import->update([
            'progress' => $percentage,
            'processed_records' => $processed
        ]);
        
        // Send real-time update if using broadcasting
        if (config('broadcasting.default') !== 'null') {
            broadcast(new ImportProgressUpdated($import, $percentage));
        }
    }
}
```

## Error Reporting and Correction

### Error Report Generation
```php
class ImportErrorReportService
{
    public function generateErrorReport(BulkImport $import)
    {
        $errors = json_decode($import->error_log, true);
        
        if (empty($errors)) {
            return null;
        }
        
        // Generate CSV error report
        $filename = 'import-errors-' . $import->id . '.csv';
        $filePath = storage_path('app/reports/' . $filename);
        
        $handle = fopen($filePath, 'w');
        
        // Write headers
        fputcsv($handle, ['Row Number', 'Error Message']);
        
        // Write errors
        foreach ($errors as $error) {
            // Parse error format: "Row X: Error message"
            preg_match('/Row (\d+): (.+)/', $error, $matches);
            
            if (count($matches) === 3) {
                fputcsv($handle, [$matches[1], $matches[2]]);
            } else {
                fputcsv($handle, ['Unknown', $error]);
            }
        }
        
        fclose($handle);
        
        return $filePath;
    }
}
```

## Testing Strategy

### Import Tests
```php
class BulkImportTest extends TestCase
{
    public function test_csv_import()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        
        // Initialize tenant context
        tenancy()->initialize($company);
        
        // Create test CSV file
        $csvContent = "name,username,email,package\nJohn Doe,johndoe,john@example.com,Basic Plan";
        $csvPath = tempnam(sys_get_temp_dir(), 'test') . '.csv';
        file_put_contents($csvPath, $csvContent);
        
        // Create import record
        $import = BulkImport::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'file_name' => 'test.csv',
            'file_path' => $csvPath,
            'status' => 'pending'
        ]);
        
        // Process import
        $importService = new CustomerImportService();
        $importService->processImport($import);
        
        // Verify customer was created
        $this->assertDatabaseHas('customers', [
            'company_id' => $company->id,
            'username' => 'johndoe',
            'email' => 'john@example.com'
        ]);
        
        // Verify import status
        $import->refresh();
        $this->assertEquals('completed', $import->status);
    }
    
    public function test_duplicate_detection()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        
        // Initialize tenant context
        tenancy()->initialize($company);
        
        // Create existing customer
        Customer::create([
            'company_id' => $company->id,
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com'
        ]);
        
        // Create test CSV with duplicate
        $csvContent = "name,username,email\nJohn Doe,johndoe,john@example.com";
        $csvPath = tempnam(sys_get_temp_dir(), 'test') . '.csv';
        file_put_contents($csvPath, $csvContent);
        
        // Create import record
        $import = BulkImport::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'file_name' => 'test.csv',
            'file_path' => $csvPath,
            'status' => 'pending'
        ]);
        
        // Process import
        $importService = new CustomerImportService();
        $importService->processImport($import);
        
        // Verify import completed with errors
        $import->refresh();
        $this->assertEquals('completed_with_errors', $import->status);
        $this->assertGreaterThan(0, $import->failed_records);
    }
}
```

This comprehensive bulk operations implementation plan provides a robust foundation for handling large-scale customer management operations in the ISP Billing & CRM system.