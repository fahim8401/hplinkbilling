<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Services\BulkOperationService;
use Illuminate\Http\Request;

class BulkController extends BaseController
{
    protected $bulkOperationService;

    public function __construct(BulkOperationService $bulkOperationService)
    {
        $this->bulkOperationService = $bulkOperationService;
    }

    /**
     * Import customers from CSV.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importCustomers(Request $request)
    {
        $admin = $request->user();
        
        // Validate the request
        $validator = $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
            'mapping' => 'required|array',
        ]);

        try {
            // Process the customer import
            $result = $this->bulkOperationService->importCustomers($request->file('file'), $request->mapping, $admin->company);
            
            return $this->sendResponse('Customers imported successfully', $result);
        } catch (\Exception $e) {
            return $this->sendError('Failed to import customers: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Extend customer expiry dates in bulk.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function extendExpiryDates(Request $request)
    {
        $admin = $request->user();
        
        // Validate the request
        $validator = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id,company_id,' . $admin->company_id,
            'days' => 'required|integer|min:1',
        ]);

        try {
            // Process the bulk expiry date extension
            $result = $this->bulkOperationService->extendExpiryDates($request->customer_ids, $request->days);
            
            return $this->sendResponse('Customer expiry dates extended successfully', $result);
        } catch (\Exception $e) {
            return $this->sendError('Failed to extend customer expiry dates: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Change customer packages in bulk.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePackages(Request $request)
    {
        $admin = $request->user();
        
        // Validate the request
        $validator = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id,company_id,' . $admin->company_id,
            'package_id' => 'required|exists:packages,id,company_id,' . $admin->company_id,
        ]);

        try {
            // Process the bulk package change
            $result = $this->bulkOperationService->changePackages($request->customer_ids, $request->package_id);
            
            return $this->sendResponse('Customer packages changed successfully', $result);
        } catch (\Exception $e) {
            return $this->sendError('Failed to change customer packages: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Suspend customers in bulk.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function suspendCustomers(Request $request)
    {
        $admin = $request->user();
        
        // Validate the request
        $validator = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id,company_id,' . $admin->company_id,
        ]);

        try {
            // Process the bulk customer suspension
            $result = $this->bulkOperationService->suspendCustomers($request->customer_ids);
            
            return $this->sendResponse('Customers suspended successfully', $result);
        } catch (\Exception $e) {
            return $this->sendError('Failed to suspend customers: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Enable customers in bulk.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enableCustomers(Request $request)
    {
        $admin = $request->user();
        
        // Validate the request
        $validator = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id,company_id,' . $admin->company_id,
        ]);

        try {
            // Process the bulk customer enablement
            $result = $this->bulkOperationService->enableCustomers($request->customer_ids);
            
            return $this->sendResponse('Customers enabled successfully', $result);
        } catch (\Exception $e) {
            return $this->sendError('Failed to enable customers: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get bulk operation status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $operationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOperationStatus(Request $request, $operationId)
    {
        try {
            // Get the status of a bulk operation
            $status = $this->bulkOperationService->getOperationStatus($operationId);
            
            return $this->sendResponse('Operation status retrieved successfully', $status);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve operation status: ' . $e->getMessage(), [], 500);
        }
    }
}