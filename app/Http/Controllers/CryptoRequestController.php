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
            // Handle custom key if provided via Base64, otherwise service uses app master key
            $key = $request->filled('key') ? base64_decode($request->input('key')) : null;
            
            $encrypted = $this->crypto->encrypt($request->input('data'), $key);

            Log::info('Data encrypted successfully');

            return $this->respond([
                'ciphertext' => $encrypted['ciphertext'] ?? $encrypted,
                'iv' => $encrypted['iv'] ?? null,
                'tag' => $encrypted['tag'] ?? null,
                'algorithm' => config('crypto.algorithm', 'AES-256-GCM'),
            ], 'Data encrypted successfully');
            
        } catch (\Exception $e) {
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
            $key = $request->filled('key') ? base64_decode($request->input('key')) : null;
            
            $payload = [
                'ciphertext' => $request->input('ciphertext'),
                'iv' => $request->input('iv'),
                'tag' => $request->input('tag'),
            ];
            
            $decrypted = $this->crypto->decrypt($payload, $key);

            Log::info('Data decrypted successfully');

            return $this->respond([
                'decrypted' => $decrypted,
                'algorithm' => config('crypto.algorithm', 'AES-256-GCM'),
            ], 'Data decrypted successfully');

        } catch (\Exception $e) {
            // Generic message for security (prevents padding oracle info leaks)
            return $this->error('Decryption failed', ['message' => 'The provided key or IV is invalid for this ciphertext.'], 500);
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
            $signature = '';
            $success = openssl_sign(
                $request->input('data'), 
                $signature, 
                $request->input('private_key'), 
                OPENSSL_ALGO_SHA256
            );

            if (!$success) {
                throw new \Exception("OpenSSL was unable to sign the data. Ensure the private key is valid PEM format.");
            }

            return $this->respond([
                'signature' => base64_encode($signature),
                'algorithm' => 'RSA-SHA256',
            ], 'Digital signature created successfully');
        } catch (\Exception $e) {
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