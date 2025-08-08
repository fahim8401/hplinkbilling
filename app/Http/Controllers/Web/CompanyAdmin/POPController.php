<?php

namespace App\Http\Controllers\Web\CompanyAdmin;

use App\Http\Controllers\Web\BaseController;
use App\Models\POP;
use App\Models\Customer;
use App\Models\MikrotikRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class POPController extends BaseController
{
    /**
     * Display a listing of POPs.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $pops = POP::where('company_id', tenancy()->company()->id)
            ->withCount([
                'customers as online_customers' => function ($query) {
                    $query->where('status', 'active');
                },
                'customers as offline_customers' => function ($query) {
                    $query->where('status', 'suspended');
                },
                'customers as expired_customers' => function ($query) {
                    $query->where('status', 'expired');
                },
                'customers as disabled_customers' => function ($query) {
                    $query->where('status', 'disabled');
                }
            ])
            ->get();

        return $this->view('companyadmin.pops.index', compact('pops'));
    }

    /**
     * Show the form for creating a new POP.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return $this->view('companyadmin.pops.create');
    }

    /**
     * Store a newly created POP.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Add company ID to the request
        $request->merge(['company_id' => tenancy()->company()->id]);

        // Create the POP
        $pop = POP::create($request->all());

        return $this->redirectSuccess('companyadmin.pops.index', 'POP created successfully.');
    }

    /**
     * Display the specified POP.
     *
     * @param  \App\Models\POP  $pop
     * @return \Illuminate\Contracts\View\View
     */
    public function show(POP $pop)
    {
        // Ensure the POP belongs to the current company
        if ($pop->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        // Get customers for this POP
        $customers = Customer::where('pop_id', $pop->id)->get();

        // Get routers for this POP
        $routers = MikrotikRouter::where('pop_id', $pop->id)->get();

        return $this->view('companyadmin.pops.show', compact('pop', 'customers', 'routers'));
    }

    /**
     * Show the form for editing the specified POP.
     *
     * @param  \App\Models\POP  $pop
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(POP $pop)
    {
        // Ensure the POP belongs to the current company
        if ($pop->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        return $this->view('companyadmin.pops.edit', compact('pop'));
    }

    /**
     * Update the specified POP.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\POP  $pop
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, POP $pop)
    {
        // Ensure the POP belongs to the current company
        if ($pop->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Update the POP
        $pop->update($request->all());

        return $this->redirectSuccess('companyadmin.pops.index', 'POP updated successfully.');
    }

    /**
     * Remove the specified POP.
     *
     * @param  \App\Models\POP  $pop
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(POP $pop)
    {
        // Ensure the POP belongs to the current company
        if ($pop->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        // Delete the POP
        $pop->delete();

        return $this->redirectSuccess('companyadmin.pops.index', 'POP deleted successfully.');
    }
}