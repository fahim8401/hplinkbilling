<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Services\RouterOSService;
use Illuminate\Http\Request;

class MikrotikController extends BaseController
{
    protected $routerOSService;

    public function __construct(RouterOSService $routerOSService)
    {
        $this->routerOSService = $routerOSService;
    }

    /**
     * Get live status for the authenticated customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function liveStatus(Request $request)
    {
        $customer = $request->user();
        
        // Get customer's router
        $router = $customer->router;
        
        if (!$router) {
            return $this->sendError('No router assigned to this customer', [], 404);
        }
        
        try {
            // Get live session data for the customer
            $sessionData = $this->routerOSService->getCustomerSession($router, $customer);
            
            return $this->sendResponse('Live status retrieved successfully', $sessionData);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve live status: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get interface counters for admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MikrotikRouter  $router
     * @return \Illuminate\Http\JsonResponse
     */
    public function interfaceCounters(Request $request, MikrotikRouter $router)
    {
        $admin = $request->user();
        
        // Ensure the router belongs to the admin's company
        if ($router->company_id !== $admin->company_id) {
            return $this->sendForbidden('You do not have permission to access this router');
        }
        
        try {
            // Get interface counters from the router
            $counters = $this->routerOSService->getInterfaceCounters($router);
            
            return $this->sendResponse('Interface counters retrieved successfully', $counters);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve interface counters: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get active sessions for admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MikrotikRouter  $router
     * @return \Illuminate\Http\JsonResponse
     */
    public function activeSessions(Request $request, MikrotikRouter $router)
    {
        $admin = $request->user();
        
        // Ensure the router belongs to the admin's company
        if ($router->company_id !== $admin->company_id) {
            return $this->sendForbidden('You do not have permission to access this router');
        }
        
        try {
            // Get active sessions from the router
            $sessions = $this->routerOSService->getActiveSessions($router);
            
            return $this->sendResponse('Active sessions retrieved successfully', $sessions);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve active sessions: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get router status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MikrotikRouter  $router
     * @return \Illuminate\Http\JsonResponse
     */
    public function routerStatus(Request $request, MikrotikRouter $router)
    {
        $admin = $request->user();
        
        // Ensure the router belongs to the admin's company
        if ($router->company_id !== $admin->company_id) {
            return $this->sendForbidden('You do not have permission to access this router');
        }
        
        try {
            // Get router status
            $status = $this->routerOSService->getRouterStatus($router);
            
            return $this->sendResponse('Router status retrieved successfully', $status);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve router status: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Test router connection.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MikrotikRouter  $router
     * @return \Illuminate\Http\JsonResponse
     */
    public function testConnection(Request $request, MikrotikRouter $router)
    {
        $admin = $request->user();
        
        // Ensure the router belongs to the admin's company
        if ($router->company_id !== $admin->company_id) {
            return $this->sendForbidden('You do not have permission to access this router');
        }
        
        try {
            // Test router connection
            $result = $this->routerOSService->testConnection($router);
            
            if ($result) {
                return $this->sendResponse('Router connection successful');
            } else {
                return $this->sendError('Router connection failed', [], 500);
            }
        } catch (\Exception $e) {
            return $this->sendError('Failed to test router connection: ' . $e->getMessage(), [], 500);
        }
    }
}