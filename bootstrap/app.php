<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CryptoWAFMiddleware; // Pastikan menggunakan nama class Middleware Anda yang terbaru

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Mendaftarkan WAF agar berjalan OTOMATIS pada semua route API
        $middleware->api(append: [
            \App\Http\Middleware\CryptoWAFMiddleware::class,
        ]);

        // Tetap menyediakan alias jika ingin digunakan secara spesifik di route definitions
        $middleware->alias([
            'waf.protect' => \App\Http\Middleware\CryptoWAFMiddleware::class,
            'verify.signature' => \App\Http\Middleware\VerifySignature::class,
            'crypto.waf' => \App\Http\Middleware\CryptoWAFMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();