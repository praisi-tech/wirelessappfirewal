<?php

return [
    'algorithm' => env('CRYPTO_ALGORITHM', 'aes-256-gcm'),
    'key' => env('CRYPTO_KEY'),
    'signature_algorithm' => env('SIGNATURE_ALGORITHM', 'sha256'),
    'token_expiry' => env('TOKEN_EXPIRY', 3600),
    
    'hmac_key' => env('HMAC_KEY'),
    
    'headers' => [
        'signature' => 'X-Signature',
        'timestamp' => 'X-Timestamp',
        'nonce' => 'X-Nonce',
        'token' => 'X-Auth-Token',
    ],
];