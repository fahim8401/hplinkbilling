<?php

namespace App\Services;

use App\Models\BulkImport;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BulkOperationService
{
    /**
     * Import customers from a CSV file.
     *
     * @param User $user
     * @param string $filePath
     * @param array $mapping
     * @return BulkImport
     */
    public function importCustomers(User $user, $filePath, $mapping)
    {
        // Create a bulk import record
        $bulkImport = BulkImport::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'file_name' => basename($filePath),
            'file_path' => $filePath,
            'total_records' => 0,
            'success_records' => 0,
            'failed_records' => 0,
            'status' => 'processing',
        ]);

        try {
            // Read the CSV file
            $csvData = array_map('str_getcsv', file(storage_path('app/' . $filePath)));
            
            // Remove the header row if it exists
            if (!empty($csvData)) {
                $header = array_shift($csvData);
            }

            $totalRecords = count($csvData);
            $successRecords = 0;
            $failedRecords = 0;
            $errors = [];

            // Update the bulk import record with total records
            $bulkImport->update(['total_records' => $totalRecords]);

            // Process each row
            foreach ($csvData as $row) {
                try {
                    // Map the CSV columns to customer fields
                    $customerData = $this->mapCsvRowToCustomerData($row, $header, $mapping);
                    
                    // Validate the customer data
                    $validator = Validator::make($customerData, [
                        'name' => 'required|string|max:255',
                        'phone' => 'required|string|max:20',
                        'email' => 'nullable|email|max:255|unique:customers',
                        'username' => 'required|string|max:100|unique:customers',
                        'password' => 'required|string|min:6',
                        'package_id' => 'required|exists:packages,id',
                        'pop_id' => 'required|exists:pops,id',
                        'router_id' => 'required|exists:mikrotik_routers,id',
                    ]);

                    if ($validator->fails()) {
                        throw new \Exception('Validation failed: ' . implode(', ', $validator->errors()->all()));
                    }

                    // Hash the password
                    $customerData['password'] = bcrypt($customerData['password']);

                    // Create the customer
                    Customer::create($customerData);
                    $successRecords++;
                } catch (\Exception $e) {
                    $failedRecords++;
                    $errors[] = 'Row ' . ($successRecords + $failedRecords) . ': ' . $e->getMessage();
                    Log::error('Customer import error: ' . $e->getMessage());
                }
            }

            // Update the bulk import record with results
            $bulkImport->update([
                'success_records' => $successRecords,
                'failed_records' => $failedRecords,
                'status' => 'completed',
                'error_log' => implode("\n", $errors),
                'completed_at' => now(),
            ]);

            return $bulkImport;
        } catch (\Exception $e) {
            // Update the bulk import record with error
            $bulkImport->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('Bulk customer import failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Map CSV row data to customer data based on column mapping.
     *
     * @param array $row
     * @param array $header
     * @param array $mapping
     * @return array
     */
    protected function mapCsvRowToCustomerData($row, $header, $mapping)
    {
        $customerData = [
            'company_id' => tenancy()->company()->id,
        ];

        foreach ($mapping as $customerField => $csvColumn) {
            $columnIndex = array_search($csvColumn, $header);
            
            if ($columnIndex !== false && isset($row[$columnIndex])) {
                $customerData[$customerField] = $row[$columnIndex];
            }
        }

        return $customerData;
    }

    /**
     * Extend customer expiry dates in bulk.
     *
     * @param array $customerIds
     * @param int $days
     * @return int
     */
    public function bulkExtendExpiry($customerIds, $days)
    {
        $affectedRows = 0;

        // Process in chunks to avoid memory issues
        $chunks = array_chunk($customerIds, 100);

        foreach ($chunks as $chunk) {
            $affectedRows += Customer::whereIn('id', $chunk)
                ->update([
                    'expiry_date' => DB::raw("expiry_date + INTERVAL '$days days'"),
                ]);
        }

        return $affectedRows;
    }

    /**
     * Change customer packages in bulk.
     *
     * @param array $customerIds
     * @param int $packageId
     * @return int
     */
    public function bulkChangePackage($customerIds, $packageId)
    {
        $affectedRows = 0;

        // Process in chunks to avoid memory issues
        $chunks = array_chunk($customerIds, 100);

        foreach ($chunks as $chunk) {
            $affectedRows += Customer::whereIn('id', $chunk)
                ->update([
                    'package_id' => $packageId,
                ]);
        }

        return $affectedRows;
    }

    /**
     * Enable customers in bulk.
     *
     * @param array $customerIds
     * @return int
     */
    public function bulkEnable($customerIds)
    {
        $affectedRows = 0;

        // Process in chunks to avoid memory issues
        $chunks = array_chunk($customerIds, 100);

        foreach ($chunks as $chunk) {
            $affectedRows += Customer::whereIn('id', $chunk)
                ->update([
                    'status' => 'active',
                ]);
        }

        return $affectedRows;
    }

    /**
     * Disable customers in bulk.
     *
     * @param array $customerIds
     * @return int
     */
    public function bulkDisable($customerIds)
    {
        $affectedRows = 0;

        // Process in chunks to avoid memory issues
        $chunks = array_chunk($customerIds, 100);

        foreach ($chunks as $chunk) {
            $affectedRows += Customer::whereIn('id', $chunk)
                ->update([
                    'status' => 'suspended',
                ]);
        }

        return $affectedRows;
    }

    /**
     * Delete customers in bulk.
     *
     * @param array $customerIds
     * @return int
     */
    public function bulkDelete($customerIds)
    {
        $affectedRows = 0;

        // Process in chunks to avoid memory issues
        $chunks = array_chunk($customerIds, 100);

        foreach ($chunks as $chunk) {
            $affectedRows += Customer::whereIn('id', $chunk)
                ->delete();
        }

        return $affectedRows;
    }
}