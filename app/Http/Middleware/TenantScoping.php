<?php

namespace App\Http\Middleware;

use Closure;
use App\Facades\Tenancy;
use App\Models\TenantModel;
use Illuminate\Database\Eloquent\Builder;

class TenantScoping
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
        // Apply global scope to all models that belong to tenant
        $this->applyTenantScope();

        return $next($request);
    }

    /**
     * Apply tenant scope to all tenant models.
     *
     * @return void
     */
    private function applyTenantScope()
    {
        if (Tenancy::isInitialized() && !Tenancy::isSuperAdmin()) {
            $companyId = Tenancy::company()->id;

            // Get all tenant models
            $tenantModels = $this->getTenantModels();

            foreach ($tenantModels as $model) {
                // Add global scope to ensure company_id filtering
                $model::addGlobalScope('company', function (Builder $builder) use ($companyId) {
                    $builder->where('company_id', $companyId);
                });
            }
        }
    }

    /**
     * Get all tenant models that need scoping.
     *
     * @return array
     */
    private function getTenantModels()
    {
        // For now, we'll return an empty array
        // In a real implementation, this would dynamically discover tenant models
        // or be configured with a list of tenant models
        return [
            // Add tenant models here as they are created
            // 'App\Models\Customer',
            // 'App\Models\Package',
            // etc.
        ];
    }
}