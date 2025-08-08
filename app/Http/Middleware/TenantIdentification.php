<?php

namespace App\Http\Middleware;

use Closure;
use App\Facades\Tenancy;
use App\Models\Company;

class TenantIdentification
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();

        // Check if this is the super admin domain
        if ($host === config('tenancy.super_admin_domain')) {
            // Set context to super admin mode
            Tenancy::initializeSuperAdmin();
            return $next($request);
        }

        // Try to identify tenant by domain
        $company = Tenancy::identifyTenant($host);

        if (!$company) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        // Initialize tenant context
        Tenancy::initialize($company);

        return $next($request);
    }
}