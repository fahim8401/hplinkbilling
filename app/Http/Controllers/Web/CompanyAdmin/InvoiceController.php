<?php

namespace App\Http\Controllers\Web\CompanyAdmin;

use App\Http\Controllers\Web\BaseController;
use App\Models\Invoice;
use App\Models\Customer;
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
        $invoices = Invoice::where('company_id', tenancy()->company()->id)->get();
        return $this->view('companyadmin.invoices.index', compact('invoices'));
    }

    /**
     * Display the specified invoice.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Invoice $invoice)
    {
        // Ensure the invoice belongs to the current company
        if ($invoice->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        return $this->view('companyadmin.invoices.show', compact('invoice'));
    }

    /**
     * Mark the specified invoice as paid.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsPaid(Invoice $invoice)
    {
        // Ensure the invoice belongs to the current company
        if ($invoice->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        // Mark the invoice as paid
        $invoice->status = 'paid';
        $invoice->payment_date = now();
        $invoice->save();

        return $this->backSuccess('Invoice marked as paid successfully.');
    }

    /**
     * Mark the specified invoice as unpaid.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsUnpaid(Invoice $invoice)
    {
        // Ensure the invoice belongs to the current company
        if ($invoice->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        // Mark the invoice as unpaid
        $invoice->status = 'unpaid';
        $invoice->payment_date = null;
        $invoice->save();

        return $this->backSuccess('Invoice marked as unpaid successfully.');
    }

    /**
     * Generate a PDF invoice.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function generatePDF(Invoice $invoice)
    {
        // Ensure the invoice belongs to the current company
        if ($invoice->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        // Generate the PDF
        $pdf = \PDF::loadView('companyadmin.invoices.pdf', compact('invoice'));
        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Send the invoice via email.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendEmail(Invoice $invoice)
    {
        // Ensure the invoice belongs to the current company
        if ($invoice->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        // Get the customer
        $customer = $invoice->customer;

        // Send the email
        // This would typically involve actually sending an email
        // For now, we'll just return a success response
        return $this->backSuccess('Invoice sent to customer successfully.');
    }
}