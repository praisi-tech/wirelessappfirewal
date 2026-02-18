<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CryptoService
{
    private string $defaultKey;
    private string $cipher;
    private string $hmacKey;

    public function __construct()
    {
        // 1. Resolve Encryption Key
        // Fallback hierarchy: Config -> Env APP_KEY -> Random string to prevent null
        $configKey = config('crypto.key') ?? config('app.key') ?? Str::random(32);
        
        // Clean the key (remove 'base64:' prefix if it exists)
        if (str_starts_with($configKey, 'base64:')) {
            $configKey = substr($configKey, 7);
        }

        // Cast to string to ensure the 'private string' property is satisfied
        $this->defaultKey = (string) base64_decode($configKey);
        
        // 2. Resolve Cipher
        $this->cipher = (string) config('crypto.algorithm', 'aes-256-gcm');
        
        // 3. Resolve HMAC Key
        // This was the source of the crash. Casting to (string) prevents the TypeError.
        $this->hmacKey = (string) (config('crypto.hmac_key') ?? env('APP_KEY') ?? 'fallback-hmac-secret-32-characters');
    }

    /**
     * Get the default encryption key
     */
    public function getKey(): string
    {
        return $this->defaultKey;
    }

    /**
     * Encrypt data using the specified cipher.
     */
    public function encrypt(string $data, ?string $customKey = null): array
    {
        try {
            $key = $customKey ?: $this->defaultKey;
            $ivLength = openssl_cipher_iv_length($this->cipher);
            $iv = random_bytes($ivLength);
            
            if (str_contains(strtolower($this->cipher), 'gcm')) {
                $tag = '';
                $encrypted = openssl_encrypt(
                    $data,
                    $this->cipher,
                    $key,
                    OPENSSL_RAW_DATA,
                    $iv,
                    $tag,
                    '', 
                    16
                );
                
                return [
                    'ciphertext' => base64_encode($encrypted),
                    'iv' => base64_encode($iv),
                    'tag' => base64_encode($tag),
                ];
            } else {
                $encrypted = openssl_encrypt(
                    $data,
                    $this->cipher,
                    $key,
                    OPENSSL_RAW_DATA,
                    $iv
                );
                
                return [
                    'ciphertext' => base64_encode($encrypted),
                    'iv' => base64_encode($iv),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Encryption failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Encryption failed: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt data.
     */
    public function decrypt(array $encryptedData, ?string $customKey = null): string
    {
        try {
            $key = $customKey ?: $this->defaultKey;
            $ciphertext = base64_decode($encryptedData['ciphertext']);
            $iv = base64_decode($encryptedData['iv']);
            
            if (str_contains(strtolower($this->cipher), 'gcm')) {
                if (empty($encryptedData['tag'])) {
                    throw new \InvalidArgumentException('Authentication tag is required for GCM ciphers.');
                }
                $tag = base64_decode($encryptedData['tag']);
                
                $decrypted = openssl_decrypt(
                    $ciphertext,
                    $this->cipher,
                    $key,
                    OPENSSL_RAW_DATA,
                    $iv,
                    $tag
                );
            } else {
                $decrypted = openssl_decrypt(
                    $ciphertext,
                    $this->cipher,
                    $key,
                    OPENSSL_RAW_DATA,
                    $iv
                );
            }
            
            if ($decrypted === false) {
                throw new \RuntimeException('Decryption failed: Check key, IV, or tag integrity.');
            }
            
            return $decrypted;
        } catch (\Exception $e) {
            Log::error('Decryption failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Decryption failed: ' . $e->getMessage());
        }
    }

    public function generateHmac(string $data, ?string $key = null): string
    {
        $key = $key ?: $this->hmacKey;
        return hash_hmac('sha256', $data, $key);
    }

    public function verifyHmac(string $data, string $hmac, ?string $key = null): bool
    {
        $expected = $this->generateHmac($data, $key);
        return hash_equals($expected, $hmac);
    }

    public function generateSignature(array $data, string $secret): string
    {
        ksort($data);
        $stringToSign = http_build_query($data);
        return hash_hmac('sha256', $stringToSign, $secret);
    }

    public function generateNonce(): string
    {
        return Str::random(32) . time();
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ]);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}