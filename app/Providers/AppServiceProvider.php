<?php

namespace App\Providers;

use App\Services\CryptoService;
use App\Services\TokenService;
use App\Services\SignatureService;
use App\WAF\Detectors\SQLInjectionDetector;
use App\WAF\Detectors\XSSDetector;
use App\WAF\Detectors\BruteForceDetector;
use App\WAF\Logger\WAFLogger;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register singleton services
        $this->app->singleton(CryptoService::class, function ($app) {
            return new CryptoService();
        });
        
        $this->app->singleton(TokenService::class, function ($app) {
            return new TokenService($app->make(CryptoService::class));
        });
        
        $this->app->singleton(SignatureService::class, function ($app) {
            return new SignatureService($app->make(CryptoService::class));
        });
        
        $this->app->singleton(SQLInjectionDetector::class, function ($app) {
            return new SQLInjectionDetector();
        });
        
        $this->app->singleton(XSSDetector::class, function ($app) {
            return new XSSDetector();
        });
        
        $this->app->singleton(BruteForceDetector::class, function ($app) {
            return new BruteForceDetector();
        });
        
        $this->app->singleton(WAFLogger::class, function ($app) {
            return new WAFLogger();
        });
    }

    public function boot(): void
    {
        // Global middleware registration
        if (config('waf.enabled')) {
            $kernel = $this->app['Illuminate\Contracts\Http\Kernel'];
            $kernel->pushMiddleware(\App\Http\Middleware\CryptoWAFMiddleware::class);
        }
    }
}