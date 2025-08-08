<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\CompanyController;

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Super Admin routes for your application.
| These routes are loaded by the RouteServiceProvider within a group
| which is assigned the "superadmin" middleware group.
|
*/

Route::prefix('superadmin')->group(function () {
    Route::apiResource('companies', CompanyController::class);
    
    Route::post('companies/{company}/enable', [CompanyController::class, 'enable']);
    Route::post('companies/{company}/disable', [CompanyController::class, 'disable']);
});