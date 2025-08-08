<?php

namespace App\Http\Controllers\Web\CompanyAdmin;

use App\Http\Controllers\Web\BaseController;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{
    /**
     * Display a listing of payments.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $payments = Payment::where('company_id', tenancy()->company()->id)->get();
        return $this->view('companyadmin.payments.index', compact('payments'));
    }

    /**
     * Show the form for creating a new payment.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        $customers = Customer::where('company_id', tenancy()->company()->id)->get();
        $invoices = Invoice::where('company_id', tenancy()->company()->id)->get();
        return $this->view('companyadmin.payments.create', compact('customers', 'invoices'));
    }

    /**
     * Store a newly created payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:receive,due,online',
            'payment_gateway' => 'nullable|string|max:50',
            'transaction_id' => 'nullable|string|max:100',
        ]);

        // Add company ID to the request
        $request->merge(['company_id' => tenancy()->company()->id]);
        $request->merge(['operator_id' => auth()->id()]);

        // Create the payment
        $payment = Payment::create($request->all());

        return $this->redirectSuccess('companyadmin.payments.index', 'Payment recorded successfully.');
    }

    /**
     * Display the specified payment.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Payment $payment)
    {
        // Ensure the payment belongs to the current company
        if ($payment->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        return $this->view('companyadmin.payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified payment.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Payment $payment)
    {
        // Ensure the payment belongs to the current company
        if ($payment->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        $customers = Customer::where('company_id', tenancy()->company()->id)->get();
        $invoices = Invoice::where('company_id', tenancy()->company()->id)->get();
        return $this->view('companyadmin.payments.edit', compact('payment', 'customers', 'invoices'));
    }

    /**
     * Update the specified payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Payment $payment)
    {
        // Ensure the payment belongs to the current company
        if ($payment->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        $validator = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:receive,due,online',
            'payment_gateway' => 'nullable|string|max:50',
            'transaction_id' => 'nullable|string|max:100',
        ]);

        // Update the payment
        $payment->update($request->all());

        return $this->redirectSuccess('companyadmin.payments.index', 'Payment updated successfully.');
    }

    /**
     * Remove the specified payment.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Payment $payment)
    {
        // Ensure the payment belongs to the current company
        if ($payment->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        // Delete the payment
        $payment->delete();

        return $this->redirectSuccess('companyadmin.payments.index', 'Payment deleted successfully.');
    }

    /**
     * Get payments by customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Contracts\View\View
     */
    public function getByCustomer(Customer $customer)
    {
        // Ensure the customer belongs to the current company
        if ($customer->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        $payments = $customer->payments;
        return $this->view('companyadmin.payments.customer', compact('customer', 'payments'));
    }

    /**
     * Get payments by invoice.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Contracts\View\View
     */
    public function getByInvoice(Invoice $invoice)
    {
        // Ensure the invoice belongs to the current company
        if ($invoice->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        $payments = $invoice->payments;
        return $this->view('companyadmin.payments.invoice', compact('invoice', 'payments'));
    }
}