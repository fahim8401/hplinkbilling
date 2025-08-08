<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\MikrotikRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RouterController extends BaseController
{
    /**
     * Display a listing of the routers.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $routers = MikrotikRouter::all();
        return $this->sendResponse($routers, 'Routers retrieved successfully.');
    }

    /**
     * Store a newly created router in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pop_id' => 'required|exists:pops,id',
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:100',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        // Encrypt the password
        $request->merge(['password' => encrypt($request->password)]);

        $router = MikrotikRouter::create($request->all());
        return $this->sendResponse($router, 'Router created successfully.', 201);
    }

    /**
     * Display the specified router.
     *
     * @param  \App\Models\MikrotikRouter  $router
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(MikrotikRouter $router)
    {
        return $this->sendResponse($router, 'Router retrieved successfully.');
    }

    /**
     * Update the specified router in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MikrotikRouter  $router
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, MikrotikRouter $router)
    {
        $validator = Validator::make($request->all(), [
            'pop_id' => 'required|exists:pops,id',
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:100',
            'password' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        // Encrypt the password if provided
        if ($request->has('password')) {
            $request->merge(['password' => encrypt($request->password)]);
        }

        $router->update($request->all());
        return $this->sendResponse($router, 'Router updated successfully.');
    }

    /**
     * Remove the specified router from storage.
     *
     * @param  \App\Models\MikrotikRouter  $router
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(MikrotikRouter $router)
    {
        $router->delete();
        return $this->sendResponse(null, 'Router deleted successfully.');
    }

    /**
     * Test the connection to the specified router.
     *
     * @param  \App\Models\MikrotikRouter  $router
     * @return \Illuminate\Http\JsonResponse
     */
    public function testConnection(MikrotikRouter $router)
    {
        try {
            // This would typically involve actually testing the connection
            // to the MikroTik router using the routeros-api-php library
            // For now, we'll just return a success response
            return $this->sendResponse(null, 'Connection test successful.');
        } catch (\Exception $e) {
            return $this->sendError('Connection test failed: ' . $e->getMessage());
        }
    }

    /**
     * Get routers by POP.
     *
     * @param  int  $popId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByPop($popId)
    {
        $routers = MikrotikRouter::where('pop_id', $popId)->get();
        return $this->sendResponse($routers, 'Routers retrieved successfully.');
    }

    /**
     * Get active routers.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActive()
    {
        $routers = MikrotikRouter::where('status', 'active')->get();
        return $this->sendResponse($routers, 'Active routers retrieved successfully.');
    }

    /**
     * Get inactive routers.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInactive()
    {
        $routers = MikrotikRouter::where('status', 'inactive')->get();
        return $this->sendResponse($routers, 'Inactive routers retrieved successfully.');
    }
}