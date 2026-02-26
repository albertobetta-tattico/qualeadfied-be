<?php

use App\Http\Controllers\Api\AdminActivityLogController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartItemController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CategoryPriceController;
use App\Http\Controllers\Api\ClientProfileController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\LeadSaleController;
use App\Http\Controllers\Api\LeadSourceController;
use App\Http\Controllers\Api\NotificationSettingController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\ProvinceController;
use App\Http\Controllers\Api\PublicCatalogController;
use App\Http\Controllers\Api\PublicLeadSubmissionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\SystemSettingController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserLeadController;
use App\Http\Controllers\Api\UserPackageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded within the "api" middleware group and are prefixed
| with /api automatically.
|
*/

// ──────────────────────────────────────────────────────────────────────────
// Public routes (no authentication required)
// ──────────────────────────────────────────────────────────────────────────

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Admin authentication (public - no auth required)
Route::prefix('admin/auth')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);
});

Route::prefix('public')->group(function () {
    Route::get('/leads', [PublicCatalogController::class, 'leads']);
    Route::get('/categories', [PublicCatalogController::class, 'categories']);
    Route::get('/provinces', [PublicCatalogController::class, 'provinces']);
    Route::post('/lead-submissions', [PublicLeadSubmissionController::class, 'store']);
});

// ──────────────────────────────────────────────────────────────────────────
// Authenticated routes (auth:sanctum)
// ──────────────────────────────────────────────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // User leads (portfolio)
    Route::get('/user-leads', [UserLeadController::class, 'index']);
    Route::get('/user-leads/{userLead}', [UserLeadController::class, 'show']);
    Route::put('/user-leads/{userLead}', [UserLeadController::class, 'update']);

    // User packages
    Route::get('/user-packages', [UserPackageController::class, 'index']);
    Route::get('/user-packages/{userPackage}', [UserPackageController::class, 'show']);

    // Cart
    Route::get('/cart', [CartItemController::class, 'index']);
    Route::post('/cart', [CartItemController::class, 'store']);
    Route::delete('/cart/{cartItem}', [CartItemController::class, 'destroy']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    // Notification settings
    Route::get('/notification-settings', [NotificationSettingController::class, 'index']);
    Route::put('/notification-settings', [NotificationSettingController::class, 'update']);

    // Client profile (own profile)
    Route::get('/client-profile', [ClientProfileController::class, 'show']);
    Route::put('/client-profile', [ClientProfileController::class, 'update']);
});

// ──────────────────────────────────────────────────────────────────────────
// Admin routes (auth:sanctum + admin prefix)
// ──────────────────────────────────────────────────────────────────────────

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {

    // Admin auth (authenticated)
    Route::post('/auth/logout', [AdminAuthController::class, 'logout']);
    Route::get('/auth/me', [AdminAuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Users - stats BEFORE apiResource
    Route::get('/users/stats', [UserController::class, 'stats']);
    Route::post('/users/{user}/suspend', [UserController::class, 'suspend']);
    Route::post('/users/{user}/activate', [UserController::class, 'activate']);
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
    Route::put('/users/{user}/free-trial', [UserController::class, 'updateFreeTrial']);
    Route::apiResource('users', UserController::class);

    // Client profiles
    Route::apiResource('client-profiles', ClientProfileController::class);

    // Admins
    Route::apiResource('admins', AdminController::class);

    // Categories - stats and toggle BEFORE apiResource
    Route::get('/categories/stats', [CategoryController::class, 'stats']);
    Route::post('/categories/{category}/toggle-active', [CategoryController::class, 'toggleActive']);
    Route::apiResource('categories', CategoryController::class);
    Route::get('/categories/{category}/prices', [CategoryPriceController::class, 'index']);
    Route::post('/categories/{category}/prices', [CategoryPriceController::class, 'store']);

    // Provinces - stats and toggle BEFORE apiResource
    Route::get('/provinces/stats', [ProvinceController::class, 'stats']);
    Route::post('/provinces/{province}/toggle-active', [ProvinceController::class, 'toggleActive']);
    Route::apiResource('provinces', ProvinceController::class)->only(['index', 'show']);

    // Lead sources - regenerate-key BEFORE apiResource
    Route::post('/lead-sources/{leadSource}/regenerate-key', [LeadSourceController::class, 'regenerateKey']);
    Route::apiResource('lead-sources', LeadSourceController::class);

    // Leads - stats BEFORE apiResource
    Route::get('/leads/stats', [LeadController::class, 'stats']);
    Route::apiResource('leads', LeadController::class);

    // Packages - stats and toggle BEFORE apiResource
    Route::get('/packages/stats', [PackageController::class, 'stats']);
    Route::post('/packages/{package}/toggle-active', [PackageController::class, 'toggleActive']);
    Route::apiResource('packages', PackageController::class);

    // Orders - stats BEFORE other order routes
    Route::get('/orders/stats', [OrderController::class, 'stats']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::get('/orders/{order}/items', [OrderItemController::class, 'index']);

    // Lead sales
    Route::apiResource('lead-sales', LeadSaleController::class)->only(['index', 'store']);

    // Transactions - stats BEFORE apiResource
    Route::get('/transactions/stats', [TransactionController::class, 'stats']);
    Route::apiResource('transactions', TransactionController::class)->only(['index', 'show']);

    // Invoices - stats and actions BEFORE apiResource
    Route::get('/invoices/stats', [InvoiceController::class, 'stats']);
    Route::post('/invoices/{invoice}/resend-sdi', [InvoiceController::class, 'resendSdi']);
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf']);
    Route::post('/invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail']);
    Route::post('/invoices/{invoice}/credit-note', [InvoiceController::class, 'createCreditNote']);
    Route::apiResource('invoices', InvoiceController::class)->only(['index', 'show']);

    // Pricing
    Route::get('/pricing', [PricingController::class, 'index']);
    Route::get('/pricing/stats', [PricingController::class, 'stats']);
    Route::get('/pricing/history', [PricingController::class, 'history']);
    Route::get('/pricing/{categoryId}', [PricingController::class, 'show']);
    Route::put('/pricing/{categoryId}', [PricingController::class, 'update']);

    // Reports
    Route::get('/reports/sales', [ReportController::class, 'sales']);
    Route::get('/reports/categories', [ReportController::class, 'categories']);
    Route::get('/reports/geographic', [ReportController::class, 'geographic']);

    // Activity logs (read-only)
    Route::get('/activity-logs', [AdminActivityLogController::class, 'index']);

    // System settings - bulk update (BEFORE the key-specific routes)
    Route::get('/system-settings', [SystemSettingController::class, 'index']);
    Route::put('/system-settings', [SystemSettingController::class, 'bulkUpdate']);
    Route::get('/system-settings/{key}', [SystemSettingController::class, 'show']);
    Route::put('/system-settings/{key}', [SystemSettingController::class, 'update']);

    // Settings stubs (notifications, SMTP, Fatture in Cloud)
    Route::get('/settings/notifications', [SettingsController::class, 'notifications']);
    Route::put('/settings/notifications/{categoryId}', [SettingsController::class, 'updateNotification']);
    Route::put('/settings/smtp', [SettingsController::class, 'updateSmtp']);
    Route::post('/settings/test-email', [SettingsController::class, 'testEmail']);
    Route::get('/settings/fatture-cloud', [SettingsController::class, 'fattureCloudConfig']);
    Route::put('/settings/fatture-cloud', [SettingsController::class, 'updateFattureCloudConfig']);
    Route::post('/settings/fatture-cloud/test', [SettingsController::class, 'testFattureCloudConnection']);

    // Admin reset password
    Route::post('/admins/{admin}/reset-password', [AdminController::class, 'resetPassword']);
});
