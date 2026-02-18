<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CryptoService;
use App\Services\TokenService;
use App\WAF\Detectors\BruteForceDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private CryptoService $crypto;
    private TokenService $tokenService;
    private BruteForceDetector $bruteForceDetector;

    public function __construct(
        CryptoService $crypto,
        TokenService $tokenService,
        BruteForceDetector $bruteForceDetector
    ) {
        $this->crypto = $crypto;
        $this->tokenService = $tokenService;
        $this->bruteForceDetector = $bruteForceDetector;
    }

    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        
        return view('crypto.login');
    }

    public function showRegistrationForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        
        return view('crypto.register');
    }
    
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // MODIFIKASI: Buat instance baru tanpa save dulu untuk menghindari 
        // error "Field 'secret_key' doesn't have a default value"
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        
        /** * PENTING: Karena di Model User sudah ada cast 'password' => 'hashed',
         * cukup masukkan password teks polos. Laravel akan otomatis meng-hash-nya.
         */
        $user->password = $request->password;

        // Generate API keys secara internal ke properti model sebelum save
        $user->generateKeysForNewUser();

        // Simpan ke database (Sekarang api_key dan secret_key sudah ada isinya)
        $user->save();

        Log::info('User registered with CryptoWAF keys', ['user_id' => $user->id, 'email' => $user->email]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Akun berhasil dibuat!');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($this->bruteForceDetector->isBlocked($request->ip())) {
            throw ValidationException::withMessages([
                'email' => ['Terlalu banyak percobaan. IP Anda diblokir sementara.'],
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $this->bruteForceDetector->recordFailedAttempt($request, $request->email);
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak cocok.'],
            ]);
        }

        if ($user->isLocked()) {
            throw ValidationException::withMessages([
                'email' => ['Akun terkunci sementara. Silakan coba lagi nanti.'],
            ]);
        }

        // Gunakan CryptoService untuk verifikasi jika password di-hash dengan metode custom
        if (!$this->crypto->verifyPassword($request->password, $user->password)) {
            $user->incrementLoginAttempts();
            $this->bruteForceDetector->recordFailedAttempt($request, $request->email);
            
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak cocok.'],
            ]);
        }

        $user->resetLoginAttempts();
        $user->last_login_at = now();
        $user->save();

        $this->bruteForceDetector->recordSuccessfulAttempt($request, $request->email);

        $this->tokenService->createToken($user, [
            'device_info' => $request->header('User-Agent'),
            'ip_address' => $request->ip(),
        ]);

        Log::info('User logged in', ['user_id' => $user->id, 'email' => $user->email]);

        Auth::login($user, $request->has('remember'));

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        
        if ($token) {
            $this->tokenService->revokeToken($token);
        }

        Log::info('User logged out', ['user_id' => Auth::id()]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function generateApiKey(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $apiData = $this->tokenService->generateApiToken($user);

        Log::info('API key regenerated', ['user_id' => $user->id]);

        return response()->json([
            'message' => 'API key generated successfully',
            'api_key' => $apiData['api_key'],
            'secret_key' => $user->secret_key,
            'signature_data' => [
                'timestamp' => $apiData['timestamp'],
                'nonce' => $apiData['nonce'],
                'signature' => $apiData['signature'],
            ],
            'warning' => 'Simpan secret key Anda dengan aman. Tidak akan ditampilkan lagi.',
        ]);
    }

    public function revokeApiKey(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user->api_key = null;
        $user->secret_key = null;
        $user->save();

        $this->tokenService->revokeAllUserTokens($user);

        Log::info('API key revoked', ['user_id' => $user->id]);

        return response()->json(['message' => 'API key revoked successfully']);
    }

    public function showProfile(Request $request)
    {
        $user = $request->user();
        if (!$user) return redirect()->route('login');

        return view('crypto.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('new_password')) {
            if (!$this->crypto->verifyPassword($request->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Password saat ini tidak sesuai.'],
                ]);
            }
            // MODIFIKASI: Langsung set password, Model cast yang akan meng-hash
            $user->password = $request->new_password;
        }

        $user->save();

        Log::info('User profile updated', ['user_id' => $user->id]);

        return redirect()->route('profile')->with('success', 'Profil berhasil diperbarui!');
    }

    public function showApiKeys(Request $request)
    {
        $user = $request->user();
        if (!$user) return redirect()->route('login');

        return view('crypto.api-keys', compact('user'));
    }

    public function getProfile(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'last_login_at' => $user->last_login_at,
                'has_api_key' => !empty($user->api_key),
                'created_at' => $user->created_at,
            ],
        ]);
    }
}