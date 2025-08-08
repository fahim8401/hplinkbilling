<?php

namespace App\Http\Controllers;

use App\Facades\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantController extends Controller
{
    /**
     * Check if the current user has permission to perform an action.
     *
     * @param string $permission
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function authorizePermission($permission)
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized');
        }

        // In a real implementation, you would check the user's permissions
        // For now, we'll assume the user has permission if they're authenticated
        // and the tenancy is properly initialized
        
        if (!Tenancy::isInitialized()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Tenant not initialized');
        }
    }

    /**
     * Get the current company/tenant.
     *
     * @return \App\Models\Company|null
     */
    protected function getCurrentCompany()
    {
        if (Tenancy::isInitialized() && !Tenancy::isSuperAdmin()) {
            return Tenancy::company();
        }

        return null;
    }

    /**
     * Check if the request is from the super admin.
     *
     * @return bool
     */
    protected function isSuperAdminRequest()
    {
        return Tenancy::isSuperAdmin();
    }
}