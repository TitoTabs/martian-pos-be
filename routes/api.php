<?php

use App\Http\Controllers\AddonController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\ManualSalesAdjustmentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'time' => now()->toISOString(),
    ]);
});

Route::post('/auth/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Public routes (POS and Barista Queue run without authentication)
|--------------------------------------------------------------------------
*/
Route::get('/products', [ProductController::class, 'index']);
Route::get('/addons', [AddonController::class, 'index']);
Route::post('/sales', [SaleController::class, 'store']);
Route::get('/orders', [OrderController::class, 'index']);
Route::patch('/orders/{sale}/status', [OrderController::class, 'updateStatus']);

/*
|--------------------------------------------------------------------------
| Admin routes (require authentication)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('products', ProductController::class)->except(['index']);
    Route::apiResource('addons', AddonController::class)->except(['index']);
    Route::apiResource('inventory-items', InventoryItemController::class);
    Route::apiResource('expenses', ExpenseController::class);
    Route::apiResource('manual-sales-adjustments', ManualSalesAdjustmentController::class);
    Route::apiResource('sales', SaleController::class)->only(['index', 'show']);

    Route::get('/dashboard', DashboardController::class);

    Route::prefix('reports')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales']);
        Route::get('/expenses', [ReportController::class, 'expenses']);
        Route::get('/inventory', [ReportController::class, 'inventory']);
        Route::get('/savings', [ReportController::class, 'savings']);
    });
});
