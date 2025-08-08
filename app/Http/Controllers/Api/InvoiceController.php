<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends BaseController
{
    /**
     * Get the authenticated customer's invoices.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $customer = $request->user();
        $invoices = Invoice::where('customer_id', $customer->id)->get();
        
        return $this->sendResponse('Invoices retrieved successfully', $invoices);
    }

    /**
     * Get the specified invoice.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Invoice $invoice)
    {
        $customer = $request->user();
        
        // Ensure the invoice belongs to the current customer
        if ($invoice->customer_id !== $customer->id) {
            return $this->sendForbidden('You do not have permission to view this invoice');
        }

        return $this->sendResponse('Invoice retrieved successfully', $invoice);
    }

    /**
     * Get the authenticated customer's unpaid invoices.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unpaid(Request $request)
    {
        $customer = $request->user();
        $invoices = Invoice::where('customer_id', $customer->id)
            ->where('status', 'unpaid')
            ->get();
        
        return $this->sendResponse('Unpaid invoices retrieved successfully', $invoices);
    }

    /**
     * Get the authenticated customer's paid invoices.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paid(Request $request)
    {
        $customer = $request->user();
        $invoices = Invoice::where('customer_id', $customer->id)
            ->where('status', 'paid')
            ->get();
        
        return $this->sendResponse('Paid invoices retrieved successfully', $invoices);
    }
}