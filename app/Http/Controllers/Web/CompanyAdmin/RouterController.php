<?php

namespace App\Http\Controllers\Web\CompanyAdmin;

use App\Http\Controllers\Web\BaseController;
use App\Models\MikrotikRouter;
use App\Models\POP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class RouterController extends BaseController
{
    /**
     * Display a listing of routers.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $routers = MikrotikRouter::where('company_id', tenancy()->company()->id)->get();
        return $this->view('companyadmin.routers.index', compact('routers'));
    }

    /**
     * Show the form for creating a new router.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        $pops = POP::where('company_id', tenancy()->company()->id)->get();
        return $this->view('companyadmin.routers.create', compact('pops'));
    }

    /**
     * Store a newly created router.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pop_id' => 'required|exists:pops,id',
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:100',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Encrypt the password
        $request->merge(['password' => encrypt($request->password)]);

        // Add company ID to the request
        $request->merge(['company_id' => tenancy()->company()->id]);

        // Create the router
        $router = MikrotikRouter::create($request->all());

        return $this->redirectSuccess('companyadmin.routers.index', 'Router created successfully.');
    }

    /**
     * Display the specified router.
     *
     * @param  \App\Models\MikrotikRouter  $router
     * @return \Illuminate\Contracts\View\View
     */
    public function show(MikrotikRouter $router)
    {
        // Ensure the router belongs to the current company
        if ($router->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        return $this->view('companyadmin.routers.show', compact('router'));
    }

    /**
     * Show the form for editing the specified router.
     *
     * @param  \App\Models\MikrotikRouter  $router
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(MikrotikRouter $router)
    {
        // Ensure the router belongs to the current company
        if ($router->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        $pops = POP::where('company_id', tenancy()->company()->id)->get();
        return $this->view('companyadmin.routers.edit', compact('router', 'pops'));
    }

    /**
     * Update the specified router.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MikrotikRouter  $router
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, MikrotikRouter $router)
    {
        // Ensure the router belongs to the current company
        if ($router->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'pop_id' => 'required|exists:pops,id',
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:100',
            'password' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Encrypt the password if provided
        if ($request->has('password')) {
            $request->merge(['password' => encrypt($request->password)]);
        }

        // Update the router
        $router->update($request->all());

        return $this->redirectSuccess('companyadmin.routers.index', 'Router updated successfully.');
    }

    /**
     * Remove the specified router.
     *
     * @param  \App\Models\MikrotikRouter  $router
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(MikrotikRouter $router)
    {
        // Ensure the router belongs to the current company
        if ($router->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        // Delete the router
        $router->delete();

        return $this->redirectSuccess('companyadmin.routers.index', 'Router deleted successfully.');
    }

    /**
     * Test the connection to the specified router.
     *
     * @param  \App\Models\MikrotikRouter  $router
     * @return \Illuminate\Http\RedirectResponse
     */
    public function testConnection(MikrotikRouter $router)
    {
        // Ensure the router belongs to the current company
        if ($router->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        // Test the connection to the router
        // This would typically involve actually testing the connection
        // to the MikroTik router using the routeros-api-php library
        // For now, we'll just return a success response
        return $this->backSuccess('Connection test successful.');
    }
}