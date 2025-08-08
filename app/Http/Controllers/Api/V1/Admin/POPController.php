<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\POP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class POPController extends BaseController
{
    /**
     * Display a listing of the POPs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $pops = POP::all();
        return $this->sendResponse($pops, 'POPs retrieved successfully.');
    }

    /**
     * Store a newly created POP in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        $pop = POP::create($request->all());
        return $this->sendResponse($pop, 'POP created successfully.', 201);
    }

    /**
     * Display the specified POP.
     *
     * @param  \App\Models\POP  $pop
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(POP $pop)
    {
        return $this->sendResponse($pop, 'POP retrieved successfully.');
    }

    /**
     * Update the specified POP in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\POP  $pop
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, POP $pop)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        $pop->update($request->all());
        return $this->sendResponse($pop, 'POP updated successfully.');
    }

    /**
     * Remove the specified POP from storage.
     *
     * @param  \App\Models\POP  $pop
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(POP $pop)
    {
        $pop->delete();
        return $this->sendResponse(null, 'POP deleted successfully.');
    }

    /**
     * Get POPs with customer counts.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWithCustomerCounts()
    {
        $pops = POP::withCount([
            'customers as online_customers' => function ($query) {
                $query->where('status', 'active');
            },
            'customers as offline_customers' => function ($query) {
                $query->where('status', 'suspended');
            },
            'customers as expired_customers' => function ($query) {
                $query->where('status', 'expired');
            },
            'customers as disabled_customers' => function ($query) {
                $query->where('status', 'disabled');
            }
        ])->get();

        return $this->sendResponse($pops, 'POPs with customer counts retrieved successfully.');
    }

    /**
     * Get active POPs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActive()
    {
        $pops = POP::where('status', 'active')->get();
        return $this->sendResponse($pops, 'Active POPs retrieved successfully.');
    }

    /**
     * Get inactive POPs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInactive()
    {
        $pops = POP::where('status', 'inactive')->get();
        return $this->sendResponse($pops, 'Inactive POPs retrieved successfully.');
    }
}