<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PackageController extends BaseController
{
    /**
     * Display a listing of the packages.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $packages = Package::all();
        return $this->sendResponse($packages, 'Packages retrieved successfully.');
    }

    /**
     * Store a newly created package in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'speed' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'fup_limit' => 'nullable|integer|min:0',
            'duration' => 'nullable|integer|min:1',
            'is_expired_package' => 'nullable|boolean',
            'mikrotik_profile_id' => 'nullable|exists:mikrotik_profiles,id',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        $package = Package::create($request->all());
        return $this->sendResponse($package, 'Package created successfully.', 201);
    }

    /**
     * Display the specified package.
     *
     * @param  \App\Models\Package  $package
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Package $package)
    {
        return $this->sendResponse($package, 'Package retrieved successfully.');
    }

    /**
     * Update the specified package in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Package  $package
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Package $package)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'speed' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'fup_limit' => 'nullable|integer|min:0',
            'duration' => 'nullable|integer|min:1',
            'is_expired_package' => 'nullable|boolean',
            'mikrotik_profile_id' => 'nullable|exists:mikrotik_profiles,id',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        $package->update($request->all());
        return $this->sendResponse($package, 'Package updated successfully.');
    }

    /**
     * Remove the specified package from storage.
     *
     * @param  \App\Models\Package  $package
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Package $package)
    {
        $package->delete();
        return $this->sendResponse(null, 'Package deleted successfully.');
    }
}