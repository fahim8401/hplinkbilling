<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Web\BaseController;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyController extends BaseController
{
    protected $companyService;

    public function __construct(CompanyService $companyService)
    {
        parent::__construct();
        $this->companyService = $companyService;
    }

    /**
     * Display a listing of companies.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $companies = Company::all();
        return $this->view('superadmin.companies.index', compact('companies'));
    }

    /**
     * Show the form for creating a new company.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return $this->view('superadmin.companies.create');
    }

    /**
     * Store a newly created company.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255|unique:companies',
            'subdomain' => 'nullable|string|max:255|unique:companies',
            'billing_day' => 'required|integer|min:1|max:28',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'currency' => 'nullable|string|max:3',
            'timezone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Create the company
        $company = $this->companyService->createCompany($request->all());

        return $this->redirectSuccess('superadmin.companies.index', 'Company created successfully.');
    }

    /**
     * Display the specified company.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Company $company)
    {
        $summary = $this->companyService->generateCompanySummary($company);
        return $this->view('superadmin.companies.show', compact('company', 'summary'));
    }

    /**
     * Show the form for editing the specified company.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Company $company)
    {
        return $this->view('superadmin.companies.edit', compact('company'));
    }

    /**
     * Update the specified company.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Company $company)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255|unique:companies,domain,' . $company->id,
            'subdomain' => 'nullable|string|max:255|unique:companies,subdomain,' . $company->id,
            'billing_day' => 'required|integer|min:1|max:28',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'currency' => 'nullable|string|max:3',
            'timezone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->backError('Please correct the errors below.')->withErrors($validator);
        }

        // Update the company
        $this->companyService->updateCompany($company, $request->all());

        return $this->redirectSuccess('superadmin.companies.index', 'Company updated successfully.');
    }

    /**
     * Remove the specified company.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Company $company)
    {
        // Delete the company
        $this->companyService->deleteCompany($company);

        return $this->redirectSuccess('superadmin.companies.index', 'Company deleted successfully.');
    }

    /**
     * Enable the specified company.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enable(Company $company)
    {
        $this->companyService->enableCompany($company);
        return $this->backSuccess('Company enabled successfully.');
    }

    /**
     * Disable the specified company.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disable(Company $company)
    {
        $this->companyService->disableCompany($company);
        return $this->backSuccess('Company disabled successfully.');
    }
}