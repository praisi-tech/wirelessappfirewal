<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CryptoRequestController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Halaman utama (Welcome)
Route::get('/', function () {
    return view('welcome');
});

/**
 * GUEST ROUTES
 */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

/**
 * CRYPTO TOOLKIT ROUTES
 * Memisahkan rute /crypto agar tidak memanggil dashboard statistik secara langsung.
 */
Route::middleware(['auth'])->group(function () {
    
    // Halaman Menu Utama Alat Kriptografi
    // Pastikan Anda membuat file: resources/views/crypto/tools.blade.php
    Route::get('/crypto', function () {
        return view('crypto.tools'); 
    })->name('crypto.dashboard');

    Route::middleware(['throttle:60,1'])->prefix('crypto')->name('crypto.')->group(function () {
        // Form views
        Route::get('/encrypt-form', fn() => view('crypto.forms.encrypt'))->name('encrypt.form');
        Route::get('/decrypt-form', fn() => view('crypto.forms.decrypt'))->name('decrypt.form');
        Route::get('/hmac-form', fn() => view('crypto.forms.hmac'))->name('hmac.form');
        Route::get('/sign-form', fn() => view('crypto.forms.sign'))->name('sign.form');
        Route::get('/hash-form', fn() => view('crypto.forms.hash'))->name('hash.form');

        // Processing operations
        Route::post('/encrypt', [CryptoRequestController::class, 'encryptData'])->name('encrypt');
        Route::post('/decrypt', [CryptoRequestController::class, 'decryptData'])->name('decrypt');
        Route::post('/hmac', [CryptoRequestController::class, 'generateHmac'])->name('hmac');
        Route::post('/verify-hmac', [CryptoRequestController::class, 'verifyHmac'])->name('verify-hmac');
        Route::post('/sign', [CryptoRequestController::class, 'createSignedRequest'])->name('sign');
        Route::post('/encrypt-sign', [CryptoRequestController::class, 'encryptAndSign'])->name('encrypt-sign');
        Route::post('/hash-password', [CryptoRequestController::class, 'hashPassword'])->name('hash-password');
    });

    /**
     * PROTECTED ROUTES (Administrative & WAF)
     * Menggunakan DashboardController yang menyediakan variabel $stats.
     */
    
    // User Session & Profile
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'getProfile'])->name('profile');
    
    // API Key Management
    Route::prefix('api-key')->name('api-key.')->group(function () {
        Route::post('/generate', [AuthController::class, 'generateApiKey'])->name('generate');
        Route::post('/revoke', [AuthController::class, 'revokeApiKey'])->name('revoke');
    });

    // WAF Administrative Dashboard (Inilah yang memanggil view crypto.dashboard)
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    
    // Log & IP Management
    Route::get('/logs', [DashboardController::class, 'getLogs'])->name('dashboard.logs');
    Route::get('/blocked-ips', [DashboardController::class, 'getBlockedIPs'])->name('dashboard.blocked-ips');
    Route::post('/block-ip', [DashboardController::class, 'blockIP'])->name('dashboard.block-ip');
    Route::delete('/unblock-ip/{id}', [DashboardController::class, 'unblockIP'])->name('dashboard.unblock-ip');
    Route::get('/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');
    Route::delete('/cleanup-logs', [DashboardController::class, 'cleanupLogs'])->name('dashboard.cleanup');
});

/**
 * SECURE API ROUTES (Signature Based)
 */
Route::middleware(['verify.signature', 'throttle:60,1'])->prefix('api')->name('api.')->group(function () {
    Route::post('/secure-data', function (Illuminate\Http\Request $request) {
        return response()->json([
            'message' => 'Secure endpoint accessed successfully',
            'data' => $request->all(),
            'user' => $request->user() ? $request->user()->only(['id', 'email']) : 'API Authentication Only',
        ]);
    })->name('secure-data');
    
    Route::get('/secure-info', function (Illuminate\Http\Request $request) {
        return response()->json([
            'message' => 'Secure info endpoint',
            'timestamp' => time(),
            'nonce' => $request->header('X-Nonce'),
        ]);
    })->name('secure-info');
});