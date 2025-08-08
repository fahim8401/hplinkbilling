<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Http\Controllers\Api\BaseController;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyController extends BaseController
{
    /**
     * Display a listing of the companies.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $companies = Company::all();
        return $this->sendResponse($companies, 'Companies retrieved successfully.');
    }

    /**
     * Store a newly created company in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255|unique:companies',
            'subdomain' => 'nullable|string|max:255|unique:companies',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'currency' => 'nullable|string|max:3',
            'timezone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        $company = Company::create($request->all());
        return $this->sendResponse($company, 'Company created successfully.', 201);
    }

    /**
     * Display the specified company.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Company $company)
    {
        return $this->sendResponse($company, 'Company retrieved successfully.');
    }

    /**
     * Update the specified company in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Company $company)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255|unique:companies,domain,' . $company->id,
            'subdomain' => 'nullable|string|max:255|unique:companies,subdomain,' . $company->id,
            'billing_day' => 'nullable|integer|min:1|max:28',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'currency' => 'nullable|string|max:3',
            'timezone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        $company->update($request->all());
        return $this->sendResponse($company, 'Company updated successfully.');
    }

    /**
     * Remove the specified company from storage.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Company $company)
    {
        $company->delete();
        return $this->sendResponse(null, 'Company deleted successfully.');
    }

    /**
     * Enable the specified company.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\JsonResponse
     */
    public function enable(Company $company)
    {
        $company->status = 'active';
        $company->save();
        return $this->sendResponse($company, 'Company enabled successfully.');
    }

    /**
     * Disable the specified company.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\JsonResponse
     */
    public function disable(Company $company)
    {
        $company->status = 'inactive';
        $company->save();
        return $this->sendResponse($company, 'Company disabled successfully.');
    }
}