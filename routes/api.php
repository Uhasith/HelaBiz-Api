<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderInvoiceController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
// Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // User Profile
    Route::get('/user', [UserController::class, 'show']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::post('/profile/picture', [UserController::class, 'uploadProfilePicture']);
    Route::delete('/profile/picture', [UserController::class, 'deleteProfilePicture']);

    // Tenant Management
    Route::put('/tenant', [TenantController::class, 'update']);
    Route::post('/tenant/logo', [TenantController::class, 'uploadLogo']);
    Route::delete('/tenant/logo', [TenantController::class, 'deleteLogo']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/revenue', [DashboardController::class, 'revenue']);
    Route::get('/dashboard/activities', [DashboardController::class, 'activities']);

    // Dashboard: low stock products & unpaid invoices
    Route::get('/dashboard/low-stock-products', [DashboardController::class, 'lowStockProducts']);
    Route::get('/dashboard/unpaid-invoices', [DashboardController::class, 'unpaidInvoices']);

    // Products
    Route::post('/products/{product}/adjust-stock', [ProductController::class, 'adjustStock']);
    Route::apiResource('products', ProductController::class);

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Orders
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::apiResource('orders', OrderController::class);

    // Order Invoices
    Route::post('/orders/invoices', [OrderInvoiceController::class, 'store']);
    Route::get('/orders/invoices/{invoice}', [OrderInvoiceController::class, 'show']);
    Route::get('/orders/invoices/{invoice}/download', [OrderInvoiceController::class, 'download']);
    Route::get('/orders/invoices/{invoice}/stream', [OrderInvoiceController::class, 'stream']);
    Route::delete('/orders/invoices/{invoice}/pdf', [OrderInvoiceController::class, 'deletePdf']);
    Route::post('/orders/invoices/{invoice}/regenerate', [OrderInvoiceController::class, 'regenerate']);

    // Invoices
    Route::patch('/invoices/{invoice}/mark-as-paid', [InvoiceController::class, 'markAsPaid']);
    Route::apiResource('invoices', InvoiceController::class);

    // Quotations
    Route::apiResource('quotations', QuotationController::class);
});
