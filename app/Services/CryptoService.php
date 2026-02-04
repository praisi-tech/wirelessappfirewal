<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CryptoService
{
    private string $key;
    private string $cipher;
    private string $hmacKey;

    public function __construct()
    {
        $this->key = base64_decode(config('crypto.key'));
        $this->cipher = config('crypto.algorithm', 'aes-256-gcm');
        $this->hmacKey = config('CRYPTO_HMAC_KEY', env('APP_KEY'));
    }

    public function encrypt(string $data): array
    {
        try {
            $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
            
            if (str_contains($this->cipher, 'gcm')) {
                $tag = '';
                $encrypted = openssl_encrypt(
                    $data,
                    $this->cipher,
                    $this->key,
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
                    $this->key,
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

    public function decrypt(array $encryptedData): string
    {
        try {
            $ciphertext = base64_decode($encryptedData['ciphertext']);
            $iv = base64_decode($encryptedData['iv']);
            
            if (str_contains($this->cipher, 'gcm')) {
                $tag = base64_decode($encryptedData['tag']);
                $decrypted = openssl_decrypt(
                    $ciphertext,
                    $this->cipher,
                    $this->key,
                    OPENSSL_RAW_DATA,
                    $iv,
                    $tag
                );
            } else {
                $decrypted = openssl_decrypt(
                    $ciphertext,
                    $this->cipher,
                    $this->key,
                    OPENSSL_RAW_DATA,
                    $iv
                );
            }
            
            if ($decrypted === false) {
                throw new \RuntimeException('Decryption failed');
            }
            
            return $decrypted;
        } catch (\Exception $e) {
            Log::error('Decryption failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Decryption failed: ' . $e->getMessage());
        }
    }

    public function generateHmac(string $data, string $key = null): string
    {
        $key = $key ?: $this->hmacKey;
        return hash_hmac('sha256', $data, $key);
    }

    public function verifyHmac(string $data, string $hmac, string $key = null): bool
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