<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Apply middleware for authentication and role-based access
        $this->middleware('auth');
        $this->middleware('role:super_admin|company_admin|reseller|customer');
    }

    /**
     * Render a view with common data.
     *
     * @param string $view
     * @param array $data
     * @param array $mergeData
     * @return \Illuminate\Contracts\View\View
     */
    protected function view($view, $data = [], $mergeData = [])
    {
        // Add common data to all views
        $commonData = [
            'company' => tenancy()->company(),
            'user' => auth()->user(),
        ];

        return view($view, array_merge($commonData, $data), $mergeData);
    }

    /**
     * Redirect to a route with a success message.
     *
     * @param string $route
     * @param string $message
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectSuccess($route, $message)
    {
        return redirect()->route($route)->with('success', $message);
    }

    /**
     * Redirect to a route with an error message.
     *
     * @param string $route
     * @param string $message
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectError($route, $message)
    {
        return redirect()->route($route)->with('error', $message);
    }

    /**
     * Redirect back with a success message.
     *
     * @param string $message
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function backSuccess($message)
    {
        return back()->with('success', $message);
    }

    /**
     * Redirect back with an error message.
     *
     * @param string $message
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function backError($message)
    {
        return back()->with('error', $message);
    }

    /**
     * Return a JSON response for AJAX requests.
     *
     * @param bool $success
     * @param string $message
     * @param array $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function jsonResponse($success, $message, $data = [], $code = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return a success JSON response.
     *
     * @param string $message
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function jsonSuccess($message, $data = [])
    {
        return $this->jsonResponse(true, $message, $data);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param array $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function jsonError($message, $data = [], $code = 400)
    {
        return $this->jsonResponse(false, $message, $data, $code);
    }
}