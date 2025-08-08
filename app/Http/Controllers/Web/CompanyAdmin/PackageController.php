<?php

namespace App\Http\Controllers\Web\CompanyAdmin;

use App\Http\Controllers\Web\BaseController;
use App\Models\Package;
use App\Models\MikrotikProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PackageController extends BaseController
{
    /**
     * Display a listing of packages.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $packages = Package::where('company_id', tenancy()->company()->id)->get();
        return $this->view('companyadmin.packages.index', compact('packages'));
    }

    /**
     * Show the form for creating a new package.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        $profiles = MikrotikProfile::whereHas('router', function ($query) {
            $query->where('company_id', tenancy()->company()->id);
        })->get();
        
        return $this->view('companyadmin.packages.create', compact('profiles'));
    }

    /**
     * Store a newly created package.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'speed' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'fup_limit' => 'nullable|integer|min:0',
            'duration' => 'required|integer|min:1',
            'is_expired_package' => 'nullable|boolean',
            'mikrotik_profile_id' => 'nullable|exists:mikrotik_profiles,id',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Add company ID to the request
        $request->merge(['company_id' => tenancy()->company()->id]);

        // Create the package
        $package = Package::create($request->all());

        return $this->redirectSuccess('companyadmin.packages.index', 'Package created successfully.');
    }

    /**
     * Display the specified package.
     *
     * @param  \App\Models\Package  $package
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Package $package)
    {
        // Ensure the package belongs to the current company
        if ($package->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        return $this->view('companyadmin.packages.show', compact('package'));
    }

    /**
     * Show the form for editing the specified package.
     *
     * @param  \App\Models\Package  $package
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Package $package)
    {
        // Ensure the package belongs to the current company
        if ($package->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        $profiles = MikrotikProfile::whereHas('router', function ($query) {
            $query->where('company_id', tenancy()->company()->id);
        })->get();
        
        return $this->view('companyadmin.packages.edit', compact('package', 'profiles'));
    }

    /**
     * Update the specified package.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Package  $package
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Package $package)
    {
        // Ensure the package belongs to the current company
        if ($package->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'speed' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'fup_limit' => 'nullable|integer|min:0',
            'duration' => 'required|integer|min:1',
            'is_expired_package' => 'nullable|boolean',
            'mikrotik_profile_id' => 'nullable|exists:mikrotik_profiles,id',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Update the package
        $package->update($request->all());

        return $this->redirectSuccess('companyadmin.packages.index', 'Package updated successfully.');
    }

    /**
     * Remove the specified package.
     *
     * @param  \App\Models\Package  $package
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Package $package)
    {
        // Ensure the package belongs to the current company
        if ($package->company_id !== tenancy()->company()->id) {
            abort(404);
        }

        // Delete the package
        $package->delete();

        return $this->redirectSuccess('companyadmin.packages.index', 'Package deleted successfully.');
    }
}