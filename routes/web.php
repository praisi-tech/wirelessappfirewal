<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CryptoRequestController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Welcome Page
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
 */
Route::middleware(['auth'])->group(function () {
    
    // Crypto Main Menu
    Route::get('/crypto', function () {
        return view('crypto.tools'); 
    })->name('crypto.dashboard');

    // Throttled Crypto Operations
    Route::middleware(['throttle:60,1'])->prefix('crypto')->name('crypto.')->group(function () {
        
        /**
         * Form views (GET)
         */
        Route::get('/encrypt-form', fn() => view('crypto.forms.encrypt'))->name('encrypt.form');
        Route::get('/decrypt-form', fn() => view('crypto.forms.decrypt'))->name('decrypt.form');
        Route::get('/hmac-form',     fn() => view('crypto.forms.hmac'))->name('hmac.form');
        Route::get('/sign-form',     fn() => view('crypto.forms.sign'))->name('sign.form');
        Route::get('/hash-form',     fn() => view('crypto.forms.hash'))->name('hash.form');

        /**
         * Fallback Redirects
         */
        Route::get('/encrypt', fn() => redirect()->route('crypto.encrypt.form'));
        Route::get('/decrypt', fn() => redirect()->route('crypto.decrypt.form'));
        Route::get('/hmac',     fn() => redirect()->route('crypto.hmac.form'));
        Route::get('/sign',     fn() => redirect()->route('crypto.sign.form'));
        Route::get('/hash-password', fn() => redirect()->route('crypto.hash.form'));

        /**
         * Processing operations (POST)
         */
        Route::post('/encrypt', [CryptoRequestController::class, 'encryptData'])->name('encrypt');
        Route::post('/decrypt', [CryptoRequestController::class, 'decryptData'])->name('decrypt');
        Route::post('/hmac', [CryptoRequestController::class, 'generateHmac'])->name('hmac');
        Route::post('/verify-hmac', [CryptoRequestController::class, 'verifyHmac'])->name('verify-hmac');
        
        // Advanced Signature & Multi-step operations
        Route::post('/sign', [CryptoRequestController::class, 'createSignedRequest'])->name('sign');
        Route::post('/encrypt-sign', [CryptoRequestController::class, 'encryptAndSign'])->name('encrypt-sign');
        
        // Password Security
        Route::post('/hash-password', [CryptoRequestController::class, 'hashPassword'])->name('hash-password');
    });

    /**
     * PROTECTED ROUTES (Administrative & WAF)
     */
    
    // User Session & Profile
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
    Route::post('/profile/update', [AuthController::class, 'updateProfile'])->name('profile.update');
    
    // API Key Management
    Route::get('/api-keys', [AuthController::class, 'showApiKeys'])->name('api-keys');
    Route::prefix('api-key')->name('api-key.')->group(function () {
        Route::post('/generate', [AuthController::class, 'generateApiKey'])->name('generate');
        Route::post('/revoke', [AuthController::class, 'revokeApiKey'])->name('revoke');
    });

    /**
     * WAF Administrative Dashboard Logic
     * Updated to match DashboardController methods
     */
    Route::prefix('dashboard')->name('dashboard')->group(function () {
        // Main View & JSON Stats (for Auto-refresh)
        Route::get('/', [DashboardController::class, 'index']); 
        
        // Logs Management
        Route::get('/logs', [DashboardController::class, 'logs'])->name('.logs');
        Route::get('/logs/export', [DashboardController::class, 'export'])->name('.logs.export');
        
        // IP Management
        Route::get('/blocked-ips', [DashboardController::class, 'showBlockedIPsView'])->name('.blocked-ips');
        Route::post('/block-ip', [DashboardController::class, 'blockIP'])->name('.block-ip');
        Route::delete('/unblock-ip/{id}', [DashboardController::class, 'unblockIP'])->name('.unblock-ip');
        
        // System & Cleanup
        Route::get('/stats', [DashboardController::class, 'getStats'])->name('.stats');
        Route::delete('/cleanup-logs', [DashboardController::class, 'cleanupLogs'])->name('.cleanup');
    });
});

/**
 * SECURE API ROUTES (Signature Based)
 */
Route::middleware(['verify.signature', 'throttle:60,1'])->prefix('api')->name('api.')->group(function () {
    Route::post('/secure-data', function (Request $request) {
        return response()->json([
            'message' => 'Secure endpoint accessed successfully',
            'data' => $request->all(),
            'user' => $request->user() ? $request->user()->only(['id', 'email']) : 'API Key Auth Only',
        ]);
    })->name('secure-data');
    
    Route::get('/secure-info', function (Request $request) {
        return response()->json([
            'message' => 'Secure info endpoint',
            'timestamp' => time(),
            'nonce' => $request->header('X-Nonce'),
        ]);
    })->name('secure-info');
});