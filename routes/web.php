<?php

use App\Http\Controllers\Web\CompanyAdmin\CustomerController as CompanyAdminCustomerController;
use App\Http\Controllers\Web\CompanyAdmin\DashboardController as CompanyAdminDashboardController;
use App\Http\Controllers\Web\CompanyAdmin\InvoiceController as CompanyAdminInvoiceController;
use App\Http\Controllers\Web\CompanyAdmin\PackageController as CompanyAdminPackageController;
use App\Http\Controllers\Web\CompanyAdmin\PaymentController as CompanyAdminPaymentController;
use App\Http\Controllers\Web\CompanyAdmin\POPController as CompanyAdminPOPController;
use App\Http\Controllers\Web\CompanyAdmin\ResellerController as CompanyAdminResellerController;
use App\Http\Controllers\Web\CompanyAdmin\RouterController as CompanyAdminRouterController;
use App\Http\Controllers\Web\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Web\Customer\InvoiceController as CustomerInvoiceController;
use App\Http\Controllers\Web\Customer\PaymentController as CustomerPaymentController;
use App\Http\Controllers\Web\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Web\Customer\SupportController as CustomerSupportController;
use App\Http\Controllers\Web\Reseller\BalanceController as ResellerBalanceController;
use App\Http\Controllers\Web\Reseller\CommissionController as ResellerCommissionController;
use App\Http\Controllers\Web\Reseller\CustomerController as ResellerCustomerController;
use App\Http\Controllers\Web\Reseller\DashboardController as ResellerDashboardController;
use App\Http\Controllers\Web\SuperAdmin\CompanyController;
use App\Http\Controllers\Web\SuperAdmin\DashboardController as SuperAdminDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Auth::routes();

// Super Admin routes
Route::prefix('super-admin')->middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('superadmin.dashboard');
    
    // Company management
    Route::resource('companies', CompanyController::class);
    Route::post('/companies/{company}/enable', [CompanyController::class, 'enable'])->name('superadmin.companies.enable');
    Route::post('/companies/{company}/disable', [CompanyController::class, 'disable'])->name('superadmin.companies.disable');
});

// Company Admin routes
Route::prefix('company-admin')->middleware(['auth', 'role:company_admin'])->group(function () {
    Route::get('/dashboard', [CompanyAdminDashboardController::class, 'index'])->name('companyadmin.dashboard');
    
    // Customer management
    Route::resource('customers', CompanyAdminCustomerController::class);
    Route::post('/customers/{customer}/suspend', [CompanyAdminCustomerController::class, 'suspend'])->name('companyadmin.customers.suspend');
    Route::post('/customers/{customer}/enable', [CompanyAdminCustomerController::class, 'enable'])->name('companyadmin.customers.enable');
    Route::post('/customers/{customer}/extend-expiry/{paymentType}', [CompanyAdminCustomerController::class, 'extendExpiry'])->name('companyadmin.customers.extend-expiry');
    
    // Package management
    Route::resource('packages', CompanyAdminPackageController::class);
    
    // POP management
    Route::resource('pops', CompanyAdminPOPController::class);
    
    // Router management
    Route::resource('routers', CompanyAdminRouterController::class);
    Route::post('/routers/{router}/test-connection', [CompanyAdminRouterController::class, 'testConnection'])->name('companyadmin.routers.test-connection');
    
    // Invoice management
    Route::resource('invoices', CompanyAdminInvoiceController::class);
    Route::post('/invoices/{invoice}/mark-as-paid', [CompanyAdminInvoiceController::class, 'markAsPaid'])->name('companyadmin.invoices.mark-as-paid');
    Route::post('/invoices/{invoice}/mark-as-unpaid', [CompanyAdminInvoiceController::class, 'markAsUnpaid'])->name('companyadmin.invoices.mark-as-unpaid');
    Route::get('/invoices/{invoice}/pdf', [CompanyAdminInvoiceController::class, 'generatePDF'])->name('companyadmin.invoices.pdf');
    Route::post('/invoices/{invoice}/send-email', [CompanyAdminInvoiceController::class, 'sendEmail'])->name('companyadmin.invoices.send-email');
    
    // Payment management
    Route::resource('payments', CompanyAdminPaymentController::class);
    Route::get('/payments/customer/{customer}', [CompanyAdminPaymentController::class, 'getByCustomer'])->name('companyadmin.payments.customer');
    Route::get('/payments/invoice/{invoice}', [CompanyAdminPaymentController::class, 'getByInvoice'])->name('companyadmin.payments.invoice');
    
    // Reseller management
    Route::resource('resellers', CompanyAdminResellerController::class);
    Route::post('/resellers/{reseller}/add-balance', [CompanyAdminResellerController::class, 'addBalance'])->name('companyadmin.resellers.add-balance');
    Route::get('/resellers/{reseller}/balance', [CompanyAdminResellerController::class, 'balance'])->name('companyadmin.resellers.balance');
    Route::get('/resellers/{reseller}/commission-report', [CompanyAdminResellerController::class, 'commissionReport'])->name('companyadmin.resellers.commission-report');
});

// Reseller routes
Route::prefix('reseller')->middleware(['auth', 'role:reseller'])->group(function () {
    Route::get('/dashboard', [ResellerDashboardController::class, 'index'])->name('reseller.dashboard');
    
    // Customer management
    Route::resource('customers', ResellerCustomerController::class);
    Route::post('/customers/{customer}/recharge', [ResellerCustomerController::class, 'recharge'])->name('reseller.customers.recharge');
    
    // Balance management
    Route::get('/balance', [ResellerBalanceController::class, 'index'])->name('reseller.balance.index');
    Route::post('/balance/transfer-to-employee', [ResellerBalanceController::class, 'transferToEmployee'])->name('reseller.balance.transfer-to-employee');
    Route::get('/balance/get-balance', [ResellerBalanceController::class, 'getBalance'])->name('reseller.balance.get-balance');
    
    // Commission management
    Route::get('/commissions', [ResellerCommissionController::class, 'index'])->name('reseller.commissions.index');
    Route::get('/commissions/{commission}', [ResellerCommissionController::class, 'show'])->name('reseller.commissions.show');
    Route::get('/commissions/report', [ResellerCommissionController::class, 'report'])->name('reseller.commissions.report');
    Route::post('/commissions/request-payout', [ResellerCommissionController::class, 'requestPayout'])->name('reseller.commissions.request-payout');
    Route::get('/commissions/statistics', [ResellerCommissionController::class, 'statistics'])->name('reseller.commissions.statistics');
});

// Customer routes
Route::prefix('customer')->middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('customer.dashboard');
    
    // Profile management
    Route::get('/profile', [CustomerProfileController::class, 'index'])->name('customer.profile.index');
    Route::get('/profile/edit', [CustomerProfileController::class, 'edit'])->name('customer.profile.edit');
    Route::put('/profile', [CustomerProfileController::class, 'update'])->name('customer.profile.update');
    Route::get('/profile/change-password', [CustomerProfileController::class, 'changePassword'])->name('customer.profile.change-password');
    Route::put('/profile/password', [CustomerProfileController::class, 'updatePassword'])->name('customer.profile.update-password');
    
    // Invoice management
    Route::get('/invoices', [CustomerInvoiceController::class, 'index'])->name('customer.invoices.index');
    Route::get('/invoices/{invoice}', [CustomerInvoiceController::class, 'show'])->name('customer.invoices.show');
    Route::get('/invoices/{invoice}/pdf', [CustomerInvoiceController::class, 'generatePDF'])->name('customer.invoices.pdf');
    
    // Payment management
    Route::get('/payments', [CustomerPaymentController::class, 'index'])->name('customer.payments.index');
    Route::get('/payments/create', [CustomerPaymentController::class, 'create'])->name('customer.payments.create');
    Route::post('/payments/online', [CustomerPaymentController::class, 'processOnlinePayment'])->name('customer.payments.online');
    Route::post('/payments/callback', [CustomerPaymentController::class, 'handleCallback'])->name('customer.payments.callback');
    
    // Support management
    Route::get('/support', [CustomerSupportController::class, 'index'])->name('customer.support.index');
    Route::get('/support/create', [CustomerSupportController::class, 'create'])->name('customer.support.create');
    Route::post('/support', [CustomerSupportController::class, 'store'])->name('customer.support.store');
    Route::get('/support/{ticket}', [CustomerSupportController::class, 'show'])->name('customer.support.show');
    Route::post('/support/{ticket}/comment', [CustomerSupportController::class, 'addComment'])->name('customer.support.add-comment');
});

// Home route for authenticated users
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');