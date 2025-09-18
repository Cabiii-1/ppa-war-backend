<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WeeklyReportController;
use Illuminate\Http\Request;
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

// Public authentication routes (no middleware required)
Route::group(['prefix' => 'auth'], function () {
    // Login endpoint - POST /api/auth/login
    Route::post('/login', [AuthController::class, 'login']);
});

// Frontend expected routes (matching the auth service endpoints)
Route::post('/login', [AuthController::class, 'login']);

// Protected authentication routes (requires authentication middleware)
Route::group(['prefix' => 'auth', 'middleware' => 'pgcsso.checkauth'], function () {
    // Get current user info - POST /api/auth/me
    Route::post('/me', [AuthController::class, 'me']);

    // Logout endpoint - POST /api/auth/logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Logout from all devices - POST /api/auth/logout-all
    Route::post('/logout-all', [AuthController::class, 'logoutAllDevices']);
});

// Frontend expected protected routes
Route::group(['middleware' => 'pgcsso.checkauth'], function () {
    // Get current user - GET /api/user (frontend expects this endpoint)
    Route::get('/user', [AuthController::class, 'me']);

    // Logout - POST /api/logout (frontend expects this endpoint)
    Route::post('/logout', [AuthController::class, 'logout']);

    // Test authentication endpoint - GET /api/test-auth
    Route::get('/test-auth', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Authentication test successful',
            'user' => $request->user()->emp_no ?? 'N/A',
        ]);
    });

    // Entries CRUD routes
    Route::apiResource('entries', EntryController::class);

    // Additional entry routes
    Route::post('/entries/bulk-delete', [EntryController::class, 'bulkDelete']);
    Route::get('/entries/date-range', [EntryController::class, 'getByDateRange']);

    // Weekly Reports CRUD routes
    Route::apiResource('weekly-reports', WeeklyReportController::class);

    // Additional weekly report routes
    Route::patch('/weekly-reports/{weeklyReport}/status', [WeeklyReportController::class, 'updateStatus']);

    // PDF generation routes
    Route::get('/weekly-reports/{weeklyReport}/pdf/download', [ReportController::class, 'downloadWeeklyReportPdf'])->name('weekly-reports.pdf.download');
    Route::get('/weekly-reports/{weeklyReport}/pdf/preview', [ReportController::class, 'previewWeeklyReportPdf'])->name('weekly-reports.pdf.preview');
    Route::post('/weekly-reports/{weeklyReport}/pdf/generate', [ReportController::class, 'generateWeeklyReportPdf'])->name('weekly-reports.pdf.generate');
});

// Health check endpoint (public)
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'DAR System API is running',
        'timestamp' => now()->toDateTimeString(),
    ]);
});
