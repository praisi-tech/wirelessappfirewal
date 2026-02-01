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

    public function encryptData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|string',
            'key' => 'string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $key = $request->input('key') ? base64_decode($request->input('key')) : null;
            
            if ($key) {
                $originalKey = $this->crypto->key;
                $this->crypto->key = $key;
            }
            
            $encrypted = $this->crypto->encrypt($request->input('data'));
            
            if ($key) {
                $this->crypto->key = $originalKey;
            }

            Log::info('Data encrypted', [
                'data_length' => strlen($request->input('data')),
                'algorithm' => config('crypto.algorithm'),
            ]);

            return response()->json([
                'encrypted' => $encrypted,
                'algorithm' => config('crypto.algorithm'),
                'note' => 'Store IV and tag securely with ciphertext',
            ]);
        } catch (\Exception $e) {
            Log::error('Encryption failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'error' => 'Encryption failed',
                'message' => $e->getMessage(),
            ], 500);
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
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $key = $request->input('key') ? base64_decode($request->input('key')) : null;
            
            $encryptedData = [
                'ciphertext' => $request->input('ciphertext'),
                'iv' => $request->input('iv'),
            ];
            
            if ($request->has('tag')) {
                $encryptedData['tag'] = $request->input('tag');
            }
            
            if ($key) {
                $originalKey = $this->crypto->key;
                $this->crypto->key = $key;
            }
            
            $decrypted = $this->crypto->decrypt($encryptedData);
            
            if ($key) {
                $this->crypto->key = $originalKey;
            }

            Log::info('Data decrypted', [
                'algorithm' => config('crypto.algorithm'),
            ]);

            return response()->json([
                'decrypted' => $decrypted,
                'algorithm' => config('crypto.algorithm'),
            ]);
        } catch (\Exception $e) {
            Log::error('Decryption failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'error' => 'Decryption failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateHmac(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|string',
            'key' => 'string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $hmac = $this->crypto->generateHmac(
                $request->input('data'),
                $request->input('key')
            );

            return response()->json([
                'hmac' => $hmac,
                'algorithm' => 'sha256',
                'data' => $request->input('data'),
            ]);
        } catch (\Exception $e) {
            Log::error('HMAC generation failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'error' => 'HMAC generation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyHmac(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|string',
            'hmac' => 'required|string',
            'key' => 'string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $isValid = $this->crypto->verifyHmac(
                $request->input('data'),
                $request->input('hmac'),
                $request->input('key')
            );

            return response()->json([
                'verified' => $isValid,
                'data' => $request->input('data'),
                'provided_hmac' => $request->input('hmac'),
            ]);
        } catch (\Exception $e) {
            Log::error('HMAC verification failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'error' => 'HMAC verification failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function createSignedRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'secret' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $signed = $this->signatureService->createSignedRequest(
                $request->input('data'),
                $request->input('secret')
            );

            Log::info('Signed request created', [
                'data_keys' => array_keys($request->input('data')),
            ]);

            return response()->json([
                'request_data' => $signed['data'],
                'headers' => $signed['headers'],
                'note' => 'Include these headers in your request',
            ]);
        } catch (\Exception $e) {
            Log::error('Signed request creation failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'error' => 'Signed request creation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function encryptAndSign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'key' => 'required|string',
            'secret' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $payload = $this->signatureService->encryptAndSign(
                $request->input('data'),
                base64_decode($request->input('key')),
                $request->input('secret')
            );

            Log::info('Data encrypted and signed', [
                'data_keys' => array_keys($request->input('data')),
            ]);

            return response()->json([
                'payload' => $payload,
                'algorithm' => config('crypto.algorithm'),
                'signature_algorithm' => config('crypto.signature_algorithm'),
                'note' => 'Send this payload and verify signature on receipt',
            ]);
        } catch (\Exception $e) {
            Log::error('Encryption and signing failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'error' => 'Encryption and signing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function hashPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $hash = $this->crypto->hashPassword($request->input('password'));

            return response()->json([
                'hash' => $hash,
                'algorithm' => 'argon2id',
                'note' => 'Store this hash securely. Do not store plain passwords.',
            ]);
        } catch (\Exception $e) {
            Log::error('Password hashing failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'error' => 'Password hashing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}