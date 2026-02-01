<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CryptoRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public API routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Crypto API routes
Route::prefix('crypto')->middleware(['throttle:60,1'])->group(function () {
    Route::post('/encrypt', [CryptoRequestController::class, 'encryptData']);
    Route::post('/decrypt', [CryptoRequestController::class, 'decryptData']);
    Route::post('/hmac', [CryptoRequestController::class, 'generateHmac']);
    Route::post('/verify-hmac', [CryptoRequestController::class, 'verifyHmac']);
    Route::post('/sign', [CryptoRequestController::class, 'createSignedRequest']);
    Route::post('/encrypt-sign', [CryptoRequestController::class, 'encryptAndSign']);
});

// Protected API routes (require API key and signature)
Route::middleware(['verify.signature', 'throttle:100,1'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'getProfile']);
    
    // Secure data endpoints
    Route::post('/secure/data', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'message' => 'Secure data received',
            'data' => $request->all(),
            'user' => $request->user()->only(['id', 'email', 'name']),
            'timestamp' => time(),
        ]);
    });
    
    Route::get('/secure/info', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'message' => 'Secure information',
            'server_time' => now()->toIso8601String(),
            'request_info' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
            ],
        ]);
    });
});

// WAF monitoring API (admin only)
Route::middleware(['verify.signature', 'auth.admin'])->prefix('admin')->group(function () {
    Route::get('/waf/stats', [DashboardController::class, 'getStats']);
    Route::get('/waf/logs', [DashboardController::class, 'getLogs']);
    Route::get('/waf/blocked-ips', [DashboardController::class, 'getBlockedIPs']);
    Route::post('/waf/block-ip', [DashboardController::class, 'blockIP']);
    Route::delete('/waf/unblock-ip/{id}', [DashboardController::class, 'unblockIP']);
});