<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    /**
     * Success response method.
     *
     * @param string $message
     * @param array $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResponse($message, $data = [], $code = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($response, $code);
    }

    /**
     * Error response method.
     *
     * @param string $error
     * @param array $errorMessages
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendError($error, $errorMessages = [], $code = 400)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    /**
     * Unauthorized response method.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendUnauthorized($message = 'Unauthorized')
    {
        return $this->sendError($message, [], 401);
    }

    /**
     * Forbidden response method.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendForbidden($message = 'Forbidden')
    {
        return $this->sendError($message, [], 403);
    }

    /**
     * Not found response method.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendNotFound($message = 'Not Found')
    {
        return $this->sendError($message, [], 404);
    }

    /**
     * Validation error response method.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendValidationError($validator)
    {
        return $this->sendError('Validation Error', $validator->errors()->all(), 422);
    }
}