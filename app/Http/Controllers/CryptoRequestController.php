<?php

namespace App\Http\Controllers;

use App\Services\CryptoService;
use App\Services\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CryptoRequestController extends Controller
{
    private CryptoService $crypto;
    private SignatureService $signatureService;

    public function __construct(CryptoService $crypto, SignatureService $signatureService)
    {
        $this->crypto = $crypto;
        $this->signatureService = $signatureService;
    }

    /**
     * Unified response handler: Detects if request expects JSON (API)
     * or a redirect (Web Form).
     */
    private function respond($data, $message = 'Success')
    {
        if (request()->expectsJson()) {
            return response()->json(array_merge(['message' => $message], $data));
        }

        // withInput() ensures the user doesn't lose their data on the form after submission
        return back()->with('result', $data)->with('success', $message)->withInput();
    }

    /**
     * Unified error handler for validation and exceptions.
     */
    private function error($message, $details = [], $code = 422)
    {
        if (request()->expectsJson()) {
            return response()->json(['error' => $message, 'details' => $details], $code);
        }

        return back()->withErrors($details ?: $message)->with('error', $message)->withInput();
    }

    public function encryptData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|string',
            'key' => 'string|nullable',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors());
        }

        try {
            // Trim and handle custom key
            $keyInput = trim($request->input('key') ?? '');
            $key = null;
            
            if ($keyInput) {
                // If user provides a custom key, it should be base64 encoded
                try {
                    $key = base64_decode($keyInput, strict: true);
                    if (!$key) {
                        return $this->error('Invalid Key Format', ['message' => 'Custom key must be valid base64 encoded.'], 400);
                    }
                } catch (\Exception $e) {
                    return $this->error('Invalid Key Format', ['message' => 'Custom key must be valid base64 encoded.'], 400);
                }
            }
            // If no custom key, null is passed → service uses system master key
            
            $encrypted = $this->crypto->encrypt($request->input('data'), $key);

            Log::info('Data encrypted successfully', ['key_type' => $key ? 'custom' : 'system']);

            return $this->respond([
                'ciphertext' => $encrypted['ciphertext'],
                'iv' => $encrypted['iv'],
                'tag' => $encrypted['tag'],
                'algorithm' => config('crypto.algorithm', 'AES-256-GCM'),
                'key_type' => $key ? 'custom' : 'system',
            ], 'Data encrypted successfully');
            
        } catch (\Exception $e) {
            Log::error('Encryption error', ['message' => $e->getMessage()]);
            return $this->error('Encryption failed', ['message' => $e->getMessage()], 500);
        }
    }

    public function decryptData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ciphertext' => 'required|string',
            'iv' => 'required|string',
            'tag' => 'string|nullable',
            'key' => 'string|nullable',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors());
        }

        try {
            // Trim whitespace from all inputs to prevent copy-paste errors
            $ciphertext = trim($request->input('ciphertext'));
            $iv = trim($request->input('iv'));
            $tag = trim($request->input('tag') ?? '');
            $keyInput = trim($request->input('key') ?? '');
            
            $key = $keyInput ? base64_decode($keyInput, strict: true) : null;
            
            // Validate base64 format
            if (!base64_decode($ciphertext, strict: true)) {
                return $this->error('Invalid Format', ['message' => 'Ciphertext must be valid base64 encoded data.'], 400);
            }
            if (!base64_decode($iv, strict: true)) {
                return $this->error('Invalid Format', ['message' => 'IV must be valid base64 encoded data.'], 400);
            }
            if ($tag && !base64_decode($tag, strict: true)) {
                return $this->error('Invalid Format', ['message' => 'Tag must be valid base64 encoded data.'], 400);
            }
            
            $payload = [
                'ciphertext' => $ciphertext,
                'iv' => $iv,
                'tag' => $tag,
            ];
            
            $decrypted = $this->crypto->decrypt($payload, $key);

            Log::info('Data decrypted successfully');

            return $this->respond([
                'decrypted' => $decrypted,
                'algorithm' => config('crypto.algorithm', 'AES-256-GCM'),
            ], 'Data decrypted successfully');

        } catch (\Exception $e) {
            // Log the actual error for debugging
            Log::error('Decryption error', [
                'exception' => class_basename($e),
                'message' => $e->getMessage(),
            ]);
            
            $message = 'Decryption failed. ';
            if (str_contains($e->getMessage(), 'Authentication tag')) {
                $message .= 'The Authentication Tag field is missing or invalid. Make sure to copy the Tag from the encryption result.';
            } elseif (str_contains($e->getMessage(), 'Check key')) {
                $message .= 'Your key, IV, or tag are incorrect. Verify: 1) Are you using the same key as encryption? 2) Did you copy Ciphertext, IV, and Tag exactly (no extra spaces)? 3) Did the app key change since encryption?';
            } else {
                $message .= 'Verify all values match the encryption result exactly.';
            }
            
            return $this->error('Decryption Failed', ['message' => $message], 500);
        }
    }

    public function generateHmac(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|string',
            'key' => 'required|string',
            'algo' => 'string|in:sha256,sha512,md5'
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors());
        }

        try {
            $algo = $request->input('algo', 'sha256');
            // We use the crypto service for consistency if it exists, otherwise standard hash_hmac
            $hmac = hash_hmac($algo, $request->input('data'), $request->input('key'));

            return $this->respond([
                'hmac' => $hmac,
                'algorithm' => strtoupper($algo),
                'data' => $request->input('data'),
            ], 'HMAC generated successfully');
        } catch (\Exception $e) {
            return $this->error('HMAC generation failed', ['message' => $e->getMessage()], 500);
        }
    }

    public function verifyHmac(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|string',
            'hmac' => 'required|string',
            'key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors());
        }

        try {
            $calculated = hash_hmac('sha256', $request->input('data'), $request->input('key'));
            $isValid = hash_equals($calculated, $request->input('hmac'));

            return $this->respond([
                'verified' => $isValid,
                'status' => $isValid ? 'Valid' : 'Invalid',
            ], $isValid ? 'Signature is valid' : 'Signature is invalid');
        } catch (\Exception $e) {
            return $this->error('HMAC verification failed', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Digital Signature Creation (Asymmetric)
     */
    public function createSignedRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|string',
            'private_key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors());
        }

        try {
            $privateKeyInput = trim($request->input('private_key'));
            
            // Validate PEM format
            if (!str_contains($privateKeyInput, 'BEGIN') || !str_contains($privateKeyInput, 'END')) {
                return $this->error('Invalid Key Format', [
                    'message' => 'Private key must be in PEM format (starting with "-----BEGIN PRIVATE KEY-----")'
                ], 400);
            }
            
            $signature = '';
            $success = openssl_sign(
                $request->input('data'), 
                $signature, 
                $privateKeyInput, 
                OPENSSL_ALGO_SHA256
            );

            if (!$success) {
                throw new \Exception("OpenSSL rejected the private key. Ensure it's a valid RSA private key in PEM format. Common issues: wrong key type (use RSA), corrupted key, or missing BEGIN/END markers.");
            }

            return $this->respond([
                'signature' => base64_encode($signature),
                'algorithm' => 'RSA-SHA256',
            ], 'Digital signature created successfully');
        } catch (\Exception $e) {
            Log::error('Signature creation error', ['message' => $e->getMessage()]);
            return $this->error('Signature creation failed', ['message' => $e->getMessage()], 500);
        }
    }

    public function encryptAndSign(Request $request)
    {
        // This is a complex operation typically used for API payloads
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'key' => 'required|string',
            'secret' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors());
        }

        try {
            $payload = $this->signatureService->encryptAndSign(
                $request->input('data'),
                base64_decode($request->input('key')),
                $request->input('secret')
            );

            return $this->respond([
                'payload' => $payload,
                'algorithm' => config('crypto.algorithm'),
                'signature_algorithm' => 'sha256',
            ], 'Data encrypted and signed successfully');
        } catch (\Exception $e) {
            return $this->error('Encryption and signing failed', ['message' => $e->getMessage()], 500);
        }
    }

    public function hashPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors());
        }

        try {
            $hash = $this->crypto->hashPassword($request->input('password'));

            return $this->respond([
                'hash' => $hash,
                'algorithm' => 'Argon2id',
            ], 'Password hashed successfully');
        } catch (\Exception $e) {
            return $this->error('Hashing failed', ['message' => $e->getMessage()], 500);
        }
    }
}