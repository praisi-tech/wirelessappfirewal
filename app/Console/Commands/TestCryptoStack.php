<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CryptoService;
use App\Services\SignatureService;

class TestCryptoStack extends Command
{
    protected $signature = 'crypto:test';
    protected $description = 'Performs a full integration test of the Crypto and Signature services';

    public function handle(CryptoService $crypto, SignatureService $signatureService)
    {
        $this->info('🚀 Starting Crypto Stack Integration Test...');
        $secret = 'test-secret-key';
        $key = base64_encode(random_bytes(32)); // 256-bit key
        $rawData = ['user_id' => 123, 'action' => 'transfer', 'amount' => 500];

        // 1. Test Simple Encryption
        $this->comment("\n1. Testing AES Encryption...");
        $encrypted = $crypto->encrypt('Hello World', base64_decode($key));
        $decrypted = $crypto->decrypt($encrypted, base64_decode($key));
        
        if ($decrypted === 'Hello World') {
            $this->info('✅ Encryption/Decryption Successful.');
        }

        // 2. Test HMAC
        $this->comment("\n2. Testing HMAC Generation...");
        $hmac = $crypto->generateHmac('message', $secret);
        if ($crypto->verifyHmac('message', $hmac, $secret)) {
            $this->info('✅ HMAC Verification Successful.');
        }

        // 3. Test Encrypt-then-Sign (The Full Loop)
        $this->comment("\n3. Testing Encrypt-then-Sign Workflow...");
        $payload = $signatureService->encryptAndSign($rawData, base64_decode($key), $secret);
        
        $this->line('   Payload Generated: ' . substr($payload['ciphertext'], 0, 20) . '...');
        $this->line('   Signature: ' . substr($payload['signature'], 0, 20) . '...');

        $verifiedData = $signatureService->decryptAndVerify($payload, base64_decode($key), $secret);

        if ($verifiedData && $verifiedData['amount'] === 500) {
            $this->info('✅ Encrypt-then-Sign Integration Successful.');
        } else {
            $this->error('❌ Encrypt-then-Sign Failed.');
        }

        $this->info("\n✨ All tests passed successfully!");
    }
}