<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CompanyService
{
    /**
     * Create a new company.
     *
     * @param array $data
     * @return Company
     * @throws ValidationException
     */
    public function createCompany(array $data)
    {
        // Validate domain/subdomain uniqueness
        $this->validateDomainUniqueness($data);

        return DB::transaction(function () use ($data) {
            // Create company record
            $company = Company::create($data);

            // Create default settings
            $this->createDefaultSettings($company);

            return $company;
        });
    }

    /**
     * Update a company.
     *
     * @param Company $company
     * @param array $data
     * @return Company
     * @throws ValidationException
     */
    public function updateCompany(Company $company, array $data)
    {
        // Validate domain/subdomain if changed
        if (isset($data['domain']) || isset($data['subdomain'])) {
            $this->validateDomainUniqueness($data, $company->id);
        }

        $company->update($data);

        return $company;
    }

    /**
     * Enable a company.
     *
     * @param Company $company
     * @return Company
     */
    public function enableCompany(Company $company)
    {
        $company->status = 'active';
        $company->save();

        // Restore any disabled services
        $this->restoreCompanyServices($company);

        return $company;
    }

    /**
     * Disable a company.
     *
     * @param Company $company
     * @return Company
     */
    public function disableCompany(Company $company)
    {
        $company->status = 'inactive';
        $company->save();

        // Disable services for this company
        $this->disableCompanyServices($company);

        return $company;
    }

    /**
     * Validate domain/subdomain uniqueness.
     *
     * @param array $data
     * @param int|null $excludeId
     * @return void
     * @throws ValidationException
     */
    protected function validateDomainUniqueness(array $data, $excludeId = null)
    {
        $query = Company::where(function ($query) use ($data) {
            if (!empty($data['domain'])) {
                $query->where('domain', $data['domain']);
            }

            if (!empty($data['subdomain'])) {
                $query->orWhere('subdomain', $data['subdomain']);
            }
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'domain' => ['Domain or subdomain already exists'],
                'subdomain' => ['Domain or subdomain already exists'],
            ]);
        }
    }

    /**
     * Create default settings for a company.
     *
     * @param Company $company
     * @return void
     */
    protected function createDefaultSettings(Company $company)
    {
        $defaultSettings = [
            'billing_day' => config('tenancy.default_billing_day'),
            'vat_percent' => config('tenancy.default_vat_percent'),
            'currency' => config('tenancy.default_currency'),
            'timezone' => config('tenancy.default_timezone'),
        ];

        foreach ($defaultSettings as $key => $value) {
            CompanySetting::create([
                'company_id' => $company->id,
                'key' => $key,
                'value' => $value,
            ]);
        }
    }

    /**
     * Restore company services when enabling.
     *
     * @param Company $company
     * @return void
     */
    protected function restoreCompanyServices(Company $company)
    {
        // In a real implementation, this would restore any disabled services
        // For now, we'll leave it as a placeholder
    }

    /**
     * Disable company services when disabling.
     *
     * @param Company $company
     * @return void
     */
    protected function disableCompanyServices(Company $company)
    {
        // In a real implementation, this would disable services for the company
        // For now, we'll leave it as a placeholder
    }
}