<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class TenancyService
{
    /**
     * The current company/tenant.
     *
     * @var Company|null
     */
    protected $company;

    /**
     * Whether tenancy is initialized.
     *
     * @var bool
     */
    protected $initialized = false;

    /**
     * Whether in super admin mode.
     *
     * @var bool
     */
    protected $superAdmin = false;

    /**
     * Initialize tenancy with a company.
     *
     * @param Company $company
     * @return void
     */
    public function initialize(Company $company)
    {
        $this->company = $company;
        $this->initialized = true;
        $this->superAdmin = false;
    }

    /**
     * Initialize super admin mode.
     *
     * @return void
     */
    public function initializeSuperAdmin()
    {
        $this->company = null;
        $this->initialized = true;
        $this->superAdmin = true;
    }

    /**
     * Get the current company.
     *
     * @return Company|null
     */
    public function company()
    {
        return $this->company;
    }

    /**
     * Check if tenancy is initialized.
     *
     * @return bool
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * Check if in super admin mode.
     *
     * @return bool
     */
    public function isSuperAdmin()
    {
        return $this->superAdmin;
    }

    /**
     * End tenancy session.
     *
     * @return void
     */
    public function end()
    {
        $this->company = null;
        $this->initialized = false;
        $this->superAdmin = false;
    }

    /**
     * Identify tenant from domain.
     *
     * @param string $host
     * @return Company|null
     */
    public function identifyTenant($host)
    {
        // Check if this is the super admin domain
        if ($host === config('tenancy.super_admin_domain')) {
            return null;
        }

        // Try to identify tenant by domain
        $company = Company::where('domain', $host)
            ->orWhere('subdomain', $this->extractSubdomain($host))
            ->first();

        return $company;
    }

    /**
     * Extract subdomain from host.
     *
     * @param string $host
     * @return string|null
     */
    protected function extractSubdomain($host)
    {
        $baseDomain = config('tenancy.base_domain');
        if (str_ends_with($host, $baseDomain)) {
            return str_before($host, '.' . $baseDomain);
        }
        return null;
    }
}