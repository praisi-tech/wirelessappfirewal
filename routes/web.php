<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CryptoRequestController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Crypto operations (public but rate limited)
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/crypto/encrypt', [CryptoRequestController::class, 'encryptData']);
    Route::post('/crypto/decrypt', [CryptoRequestController::class, 'decryptData']);
    Route::post('/crypto/hmac', [CryptoRequestController::class, 'generateHmac']);
    Route::post('/crypto/verify-hmac', [CryptoRequestController::class, 'verifyHmac']);
    Route::post('/crypto/sign', [CryptoRequestController::class, 'createSignedRequest']);
    Route::post('/crypto/encrypt-sign', [CryptoRequestController::class, 'encryptAndSign']);
    Route::post('/crypto/hash-password', [CryptoRequestController::class, 'hashPassword']);
});

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'getProfile']);
    Route::post('/api-key/generate', [AuthController::class, 'generateApiKey']);
    Route::post('/api-key/revoke', [AuthController::class, 'revokeApiKey']);
    
    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'dashboard']);
    Route::get('/logs', [DashboardController::class, 'getLogs']);
    Route::get('/blocked-ips', [DashboardController::class, 'getBlockedIPs']);
    Route::post('/block-ip', [DashboardController::class, 'blockIP']);
    Route::delete('/unblock-ip/{id}', [DashboardController::class, 'unblockIP']);
    Route::get('/stats', [DashboardController::class, 'getStats']);
    Route::delete('/cleanup-logs', [DashboardController::class, 'cleanupLogs']);
});

// API routes with signature verification
Route::middleware(['verify.signature', 'throttle:60,1'])->group(function () {
    Route::post('/api/secure-data', function (Request $request) {
        return response()->json([
            'message' => 'Secure endpoint accessed successfully',
            'data' => $request->all(),
            'user' => $request->user()->only(['id', 'email']),
        ]);
    });
    
    Route::get('/api/secure-info', function (Request $request) {
        return response()->json([
            'message' => 'Secure info endpoint',
            'timestamp' => time(),
            'nonce' => $request->header('X-Nonce'),
        ]);
    });
});