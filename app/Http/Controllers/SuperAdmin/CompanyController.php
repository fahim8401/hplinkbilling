<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    /**
     * The company service instance.
     *
     * @var CompanyService
     */
    protected $companyService;

    /**
     * Create a new controller instance.
     *
     * @param CompanyService $companyService
     * @return void
     */
    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * Display a listing of companies.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $companies = Company::orderBy('created_at', 'desc')->paginate(20);

        return response()->json($companies);
    }

    /**
     * Store a newly created company.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255|unique:companies',
            'subdomain' => 'nullable|string|max:255|unique:companies',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $company = $this->companyService->createCompany($request->all());

            return response()->json($company, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified company.
     *
     * @param Company $company
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        return response()->json($company);
    }

    /**
     * Update the specified company.
     *
     * @param Request $request
     * @param Company $company
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Company $company)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'domain' => 'nullable|string|max:255|unique:companies,domain,' . $company->id,
            'subdomain' => 'nullable|string|max:255|unique:companies,subdomain,' . $company->id,
            'status' => 'nullable|in:active,inactive,suspended',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $company = $this->companyService->updateCompany($company, $request->all());

            return response()->json($company);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Remove the specified company.
     *
     * @param Company $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        // In a real implementation, you might want to soft delete or archive
        // For now, we'll do a hard delete
        $company->delete();

        return response()->json(['message' => 'Company deleted successfully']);
    }

    /**
     * Enable the specified company.
     *
     * @param Company $company
     * @return \Illuminate\Http\Response
     */
    public function enable(Company $company)
    {
        $company = $this->companyService->enableCompany($company);

        return response()->json($company);
    }

    /**
     * Disable the specified company.
     *
     * @param Company $company
     * @return \Illuminate\Http\Response
     */
    public function disable(Company $company)
    {
        $company = $this->companyService->disableCompany($company);

        return response()->json($company);
    }
}