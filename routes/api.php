<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CryptoRequestController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Group with WAF, Signature Verification and Throttling
// MILESTONE: Automatic Protection - All crypto routes require valid signature via middleware
Route::prefix('crypto')->middleware(['crypto.waf', 'verify.signature', 'throttle:60,1'])->group(function () {
    Route::post('/encrypt', [CryptoRequestController::class, 'encryptData']);
    Route::post('/decrypt', [CryptoRequestController::class, 'decryptData']);
    Route::post('/hmac', [CryptoRequestController::class, 'generateHmac']);
    Route::post('/verify-hmac', [CryptoRequestController::class, 'verifyHmac']);
    Route::post('/sign', [CryptoRequestController::class, 'createSignedRequest']);
    Route::post('/encrypt-sign', [CryptoRequestController::class, 'encryptAndSign']);
});

// Admin Monitoring Routes
Route::middleware(['auth', 'crypto.waf'])->prefix('admin')->group(function () {
    Route::get('/waf/stats', [DashboardController::class, 'getStats']);
    Route::get('/waf/logs', [DashboardController::class, 'logs']); 
    Route::get('/waf/export', [DashboardController::class, 'export']); 
    Route::get('/waf/blocked-ips', [DashboardController::class, 'getBlockedIPs']);
    Route::post('/waf/block-ip', [DashboardController::class, 'blockIP']);
    Route::delete('/waf/unblock-ip/{id}', [DashboardController::class, 'unblockIP']);
    Route::post('/waf/cleanup', [DashboardController::class, 'cleanupLogs']);
});

/**
 * FIXED TEST ENDPOINT
 * This is the ONLY endpoint the 'crypto:test' command should hit.
 * We remove prefix to match the command's $baseUrl.
 */
Route::post('/test-endpoint', function (Request $request) {
    return response()->json([
        'status' => 'success',
        'message' => 'Security Integrity Check Passed',
        'received_data' => $request->all()
    ]);
})->middleware('crypto.waf');