<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Encryption Settings
    |--------------------------------------------------------------------------
    | The algorithm used for data encryption. AES-256-GCM is recommended 
    | as it provides both confidentiality and integrity (AEAD).
    */
    'algorithm' => env('CRYPTO_ALGORITHM', 'aes-256-gcm'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    | This key must be a base64 encoded string. If null, the service will 
    | attempt to fallback to the Laravel APP_KEY.
    */
    'key' => env('CRYPTO_KEY', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Signing & HMAC Settings
    |--------------------------------------------------------------------------
    | hmac_key: Used for internal data integrity checks.
    | signature_algorithm: The hashing algorithm used for request signatures.
    | token_expiry: Duration (in seconds) that an auth token remains valid.
    */
    'hmac_key' => env('HMAC_KEY', env('APP_KEY')),
    'signature_algorithm' => env('SIGNATURE_ALGORITHM', 'sha256'),
    'token_expiry' => (int) env('TOKEN_EXPIRY', 3600),

    /*
    |--------------------------------------------------------------------------
    | Custom Header Definitions
    |--------------------------------------------------------------------------
    | These headers are used by the SignatureService to extract security 
    | metadata from incoming requests.
    */
    'headers' => [
        'signature' => env('CRYPTO_HEADER_SIGNATURE', 'X-Signature'),
        'timestamp' => env('CRYPTO_HEADER_TIMESTAMP', 'X-Timestamp'),
        'nonce'     => env('CRYPTO_HEADER_NONCE', 'X-Nonce'),
        'token'     => env('CRYPTO_HEADER_TOKEN', 'X-Auth-Token'),
    ],

];