<?php

namespace App\Services;

use App\Models\Token;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TokenService
{
    private CryptoService $crypto;

    public function __construct(CryptoService $crypto)
    {
        $this->crypto = $crypto;
    }

    public function createToken(User $user, array $metadata = []): Token
    {
        // Generate a secure token
        $plainToken = Str::random(64);
        $hashedToken = hash('sha256', $plainToken);
        
        // Store metadata encrypted
        $encryptedMetadata = $this->crypto->encrypt(json_encode($metadata));
        
        $token = Token::create([
            'token' => $hashedToken,
            'user_id' => $user->id,
            'device_info' => $metadata['device_info'] ?? null,
            'ip_address' => $metadata['ip_address'] ?? request()->ip(),
            'expires_at' => now()->addSeconds(config('crypto.token_expiry', 3600)),
            'is_active' => true,
        ]);
        
        // Store plain token in cache for quick validation (short-lived)
        Cache::put('token:' . $hashedToken, [
            'plain' => $plainToken,
            'user_id' => $user->id,
            'metadata' => $encryptedMetadata,
        ], now()->addMinutes(5));
        
        return $token;
    }

    public function validateToken(string $token): ?User
    {
        $hashedToken = hash('sha256', $token);
        
        // Check cache first
        $cached = Cache::get('token:' . $hashedToken);
        if ($cached && $cached['plain'] === $token) {
            $tokenModel = Token::where('token', $hashedToken)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();
            
            if ($tokenModel) {
                $tokenModel->markAsUsed();
                return $tokenModel->user;
            }
        }
        
        return null;
    }

    public function revokeToken(string $token): bool
    {
        $hashedToken = hash('sha256', $token);
        
        Cache::forget('token:' . $hashedToken);
        
        $tokenModel = Token::where('token', $hashedToken)->first();
        if ($tokenModel) {
            $tokenModel->revoke();
            return true;
        }
        
        return false;
    }

    public function revokeAllUserTokens(User $user): void
    {
        $tokens = Token::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();
        
        foreach ($tokens as $token) {
            Cache::forget('token:' . $token->token);
            $token->revoke();
        }
    }

    public function generateApiToken(User $user): array
    {
        $apiKey = $user->generateApiKey();
        $timestamp = time();
        $nonce = $this->crypto->generateNonce();
        
        $signature = $this->crypto->generateSignature([
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
        ], $user->secret_key);
        
        return [
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'signature' => $signature,
        ];
    }
}