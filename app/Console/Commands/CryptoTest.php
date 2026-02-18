<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\User;

class CryptoTest extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'crypto:test';

    /**
     * The console command description.
     */
    protected $description = 'Trigger WAF logs by simulating SQL Injection and Invalid Signatures';

    /**
     * The Base URL of your application API.
     */
    private $baseUrl = 'http://127.0.0.1:8000/api'; 

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🚀 [WAF SIMULATOR] Starting Attack Sequence...");

        // Ensure we have a user to provide valid API credentials
        $user = User::first();
        if (!$user) {
            $this->error("❌ ERROR: No users found. Please run 'php artisan db:seed'.");
            return;
        }

        $this->info("Targeting: " . $this->baseUrl . "/test-endpoint");

        // TEST 1: SQL INJECTION
        // This payload is designed to match your regex /(or\s*1\s*=\s*1)/i or /(union\s+select)/i
        $this->sendRequest('ATTACK: SQL INJECTION', [
            'id' => "1' OR 1=1 --",
            'query' => "UNION SELECT username, password FROM users--",
            'comment' => "admin' #"
        ], $user);

        // TEST 2: INVALID SIGNATURE
        // This will trigger the 'invalid_signature' log type
        $this->sendRequest('ATTACK: INVALID SIGNATURE', [
            'data' => 'unauthorized_access_attempt'
        ], $user, true);

        $this->info("\n✨ Simulation Sequence Finished.");
        $this->info("Action: Go to the Security Audit Logs and filter by 'SQL INJECTION'.");
    }

    /**
     * Helper to send signed HTTP requests.
     */
    private function sendRequest($label, $data, $user, $isBadSignature = false)
    {
        $this->line("\nTriggering: $label...");
        
        $payload = json_encode($data);
        $nonce = Str::random(16);
        $timestamp = time();
        $secret = $user->secret_key; 

        // Generate correct HMAC signature
        $signature = hash_hmac('sha256', $payload . $nonce . $timestamp, $secret);
        
        // Corrupt the signature if we want to test signature failure
        if ($isBadSignature) { 
            $signature = "forged_signature_at_" . time(); 
        }

        try {
            $response = Http::withHeaders([
                'X-API-Key'   => $user->api_key,
                'X-Signature' => $signature,
                'X-Nonce'     => $nonce,
                'X-Timestamp' => $timestamp,
                'Accept'      => 'application/json'
            ])->post($this->baseUrl . '/test-endpoint', $data);

            $status = $response->status();
            
            if ($status === 403 || $status === 401) {
                $this->info("✔ BLOCKED (Status $status): WAF detected the threat and logged it.");
            } else {
                $this->error("✘ PASSED (Status $status): WAF failed to block. Check your Detector regex.");
                $this->line("Server Response: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("🚨 Connection Error: Ensure 'php artisan serve' is running!");
        }
    }
}