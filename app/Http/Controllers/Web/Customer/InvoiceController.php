<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Web\BaseController;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends BaseController
{
    /**
     * Display a listing of invoices.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $customer = auth()->user();
        $invoices = Invoice::where('customer_id', $customer->id)->get();
        
        return $this->view('customer.invoices.index', compact('invoices'));
    }

    /**
     * Display the specified invoice.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Invoice $invoice)
    {
        $customer = auth()->user();
        
        // Ensure the invoice belongs to the current customer
        if ($invoice->customer_id !== $customer->id) {
            abort(404);
        }

        return $this->view('customer.invoices.show', compact('invoice'));
    }

    /**
     * Generate a PDF invoice.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function generatePDF(Invoice $invoice)
    {
        $customer = auth()->user();
        
        // Ensure the invoice belongs to the current customer
        if ($invoice->customer_id !== $customer->id) {
            abort(404);
        }

        // Generate the PDF
        $pdf = \PDF::loadView('customer.invoices.pdf', compact('invoice'));
        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }
}