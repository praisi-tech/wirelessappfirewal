<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateCryptoKeys extends Command
{
    protected $signature = 'crypto:generate-keys';
    protected $description = 'Generate cryptographic keys for the application';

    public function handle(): void
    {
        $this->info('Generating cryptographic keys...');
        
        // Generate encryption key
        $encryptionKey = base64_encode(random_bytes(32));
        
        // Generate HMAC key
        $hmacKey = Str::random(64);
        
        // Update .env file
        $envPath = base_path('.env');
        $envContent = File::get($envPath);
        
        // Update or add keys
        $envContent = preg_replace(
            '/CRYPTO_KEY=.*/',
            "CRYPTO_KEY={$encryptionKey}",
            $envContent
        );
        
        if (!str_contains($envContent, 'HMAC_KEY=')) {
            $envContent .= "\nHMAC_KEY={$hmacKey}\n";
        } else {
            $envContent = preg_replace(
                '/HMAC_KEY=.*/',
                "HMAC_KEY={$hmacKey}",
                $envContent
            );
        }
        
        File::put($envPath, $envContent);
        
        $this->info('Cryptographic keys generated successfully!');
        $this->line('Encryption Key: ' . substr($encryptionKey, 0, 20) . '...');
        $this->line('HMAC Key: ' . substr($hmacKey, 0, 20) . '...');
        $this->warn('Please store these keys securely. They have been saved to your .env file.');
    }
}