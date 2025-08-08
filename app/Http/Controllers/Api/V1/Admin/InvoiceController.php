<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends BaseController
{
    /**
     * Display a listing of the invoices.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $invoices = Invoice::all();
        return $this->sendResponse($invoices, 'Invoices retrieved successfully.');
    }

    /**
     * Display the specified invoice.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Invoice $invoice)
    {
        return $this->sendResponse($invoice, 'Invoice retrieved successfully.');
    }

    /**
     * Mark the specified invoice as paid.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsPaid(Invoice $invoice)
    {
        if ($invoice->isPaid()) {
            return $this->sendError('Invoice is already paid.');
        }

        $invoice->status = 'paid';
        $invoice->payment_date = now();
        $invoice->save();

        return $this->sendResponse($invoice, 'Invoice marked as paid successfully.');
    }

    /**
     * Mark the specified invoice as unpaid.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsUnpaid(Invoice $invoice)
    {
        if ($invoice->isUnpaid()) {
            return $this->sendError('Invoice is already unpaid.');
        }

        $invoice->status = 'unpaid';
        $invoice->payment_date = null;
        $invoice->save();

        return $this->sendResponse($invoice, 'Invoice marked as unpaid successfully.');
    }

    /**
     * Get invoices by status.
     *
     * @param  string  $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByStatus($status)
    {
        $invoices = Invoice::where('status', $status)->get();
        return $this->sendResponse($invoices, 'Invoices retrieved successfully.');
    }

    /**
     * Get overdue invoices.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOverdue()
    {
        $invoices = Invoice::where('due_date', '<', now())
                          ->where('status', '!=', 'paid')
                          ->get();

        return $this->sendResponse($invoices, 'Overdue invoices retrieved successfully.');
    }
}