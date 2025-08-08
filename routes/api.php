<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BulkController;
use App\Http\Controllers\Api\CustomerController as ApiCustomerController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MikrotikController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ResellerController;
use App\Http\Controllers\Api\SupportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::post('/auth/login/customer', [AuthController::class, 'customerLogin']);
Route::post('/auth/login/reseller', [AuthController::class, 'resellerLogin']);
Route::post('/auth/login/admin', [AuthController::class, 'adminLogin']);

// Protected routes for authenticated users
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    
    // Customer routes
    Route::prefix('customer')->group(function () {
        Route::get('/profile', [ApiCustomerController::class, 'profile']);
        Route::put('/profile', [ApiCustomerController::class, 'updateProfile']);
        Route::put('/password', [ApiCustomerController::class, 'changePassword']);
        Route::get('/usage', [ApiCustomerController::class, 'usage']);
        Route::get('/session', [ApiCustomerController::class, 'liveSession']);
        
        // Customer invoice routes
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::get('/invoices/unpaid', [InvoiceController::class, 'unpaid']);
        Route::get('/invoices/paid', [InvoiceController::class, 'paid']);
        
        // Customer payment routes
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{payment}', [PaymentController::class, 'show']);
        Route::post('/payments/initiate', [PaymentController::class, 'initiatePayment']);
        Route::post('/payments/callback', [PaymentController::class, 'handleCallback']);
        
        // Customer support routes
        Route::get('/tickets', [SupportController::class, 'index']);
        Route::post('/tickets', [SupportController::class, 'store']);
        Route::get('/tickets/{ticket}', [SupportController::class, 'show']);
        Route::post('/tickets/{ticket}/comment', [SupportController::class, 'addComment']);
        Route::post('/tickets/{ticket}/close', [SupportController::class, 'close']);
    });
    
    // Reseller routes
    Route::prefix('reseller')->group(function () {
        Route::get('/balance', [ResellerController::class, 'balance']);
        Route::get('/commission-stats', [ResellerController::class, 'commissionStats']);
        Route::get('/commission-report', [ResellerController::class, 'commissionReport']);
        Route::post('/transfer-to-employee', [ResellerController::class, 'transferToEmployee']);
        Route::get('/customers', [ResellerController::class, 'customers']);
        Route::post('/customers/{customer}/recharge', [ResellerController::class, 'rechargeCustomer']);
    });
    
    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard-stats', [AdminController::class, 'dashboardStats']);
        Route::get('/financial-report', [AdminController::class, 'financialReport']);
        Route::get('/customer-report', [AdminController::class, 'customerReport']);
        
        // Super admin only routes
        Route::middleware('role:super_admin')->group(function () {
            Route::get('/companies', [AdminController::class, 'companies']);
            Route::get('/companies/{company}', [AdminController::class, 'companyDetails']);
        });
    });
    
    // Mikrotik routes
    Route::prefix('mikrotik')->group(function () {
        Route::get('/live-status', [MikrotikController::class, 'liveStatus']);
        
        // Admin only routes
        Route::middleware('role:company_admin|super_admin')->group(function () {
            Route::get('/routers/{router}/interface-counters', [MikrotikController::class, 'interfaceCounters']);
            Route::get('/routers/{router}/active-sessions', [MikrotikController::class, 'activeSessions']);
            Route::get('/routers/{router}/status', [MikrotikController::class, 'routerStatus']);
            Route::post('/routers/{router}/test-connection', [MikrotikController::class, 'testConnection']);
        });
    });
    
    // Bulk operation routes (admin only)
    Route::prefix('bulk')->middleware('role:company_admin|super_admin')->group(function () {
        Route::post('/import-customers', [BulkController::class, 'importCustomers']);
        Route::post('/extend-expiry-dates', [BulkController::class, 'extendExpiryDates']);
        Route::post('/change-packages', [BulkController::class, 'changePackages']);
        Route::post('/suspend-customers', [BulkController::class, 'suspendCustomers']);
        Route::post('/enable-customers', [BulkController::class, 'enableCustomers']);
        Route::get('/operations/{operationId}', [BulkController::class, 'getOperationStatus']);
    });
});

// Payment gateway callback routes (no auth required)
Route::post('/payment-callbacks/bkash', [PaymentController::class, 'handleCallback']);
Route::post('/payment-callbacks/nagad', [PaymentController::class, 'handleCallback']);
Route::post('/payment-callbacks/rocket', [PaymentController::class, 'handleCallback']);