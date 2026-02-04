<?php

namespace App\Http\Controllers;

use App\Models\WAFLog;
use App\Models\BlockedIP;
use App\Models\User;
use App\WAF\Logger\WAFLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
// Tambahkan import untuk Laravel 11/12 middleware
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DashboardController extends Controller implements HasMiddleware
{
    private WAFLogger $wafLogger;

    public function __construct(WAFLogger $wafLogger)
    {
        $this->wafLogger = $wafLogger;
    }

    /**
     * Registrasi Middleware untuk Controller (Standar Laravel 11/12)
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    /**
     * Menampilkan Halaman Dashboard Utama
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        
        // Ambil data statistik
        $stats = $this->getDashboardStats();
        
        // Ambil 10 ancaman terbaru
        $recentThreats = WAFLog::whereNotNull('threat_type')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Ambil 10 IP yang paling sering diblokir
        $topBlockedIPs = BlockedIP::orderBy('attempts', 'desc')
            ->limit(10)
            ->get();
        
        // JIKA request mengharapkan JSON (AJAX Refresh)
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'user' => $user,
                'stats' => $stats,
                'recent_threats' => $recentThreats,
                'top_blocked_ips' => $topBlockedIPs,
            ]);
        }

        // TAMPILKAN HALAMAN VISUAL (Blade View)
        return view('crypto.dashboard', compact('user', 'stats', 'recentThreats', 'topBlockedIPs'));
    }

    /**
     * Ambil Logs untuk Data Table
     */
    public function getLogs(Request $request)
    {
        // Pastikan User punya akses (Policy)
        // $this->authorize('viewAny', WAFLog::class); // Opsional jika Policy sudah siap
        
        $query = WAFLog::with('user');
        
        if ($request->has('type')) {
            $query->where('threat_type', $request->type);
        }
        
        if ($request->has('severity')) {
            $query->where('severity', '>=', $request->severity);
        }
        
        if ($request->has('blocked')) {
            $query->where('blocked', $request->boolean('blocked'));
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%");
            });
        }
        
        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));
        
        return response()->json([
            'logs' => $logs,
            'filters' => $request->all(),
        ]);
    }

    /**
     * List IP yang sedang diblokir
     */
    public function getBlockedIPs(Request $request)
    {
        $query = BlockedIP::with('user');
        
        if ($request->has('active')) {
            $query->where(function ($q) {
                $q->whereNull('blocked_until')
                  ->orWhere('blocked_until', '>', now());
            });
        }
        
        $blockedIPs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));
        
        return response()->json([
            'blocked_ips' => $blockedIPs,
            'total_active' => BlockedIP::where(function ($q) {
                $q->whereNull('blocked_until')
                  ->orWhere('blocked_until', '>', now());
            })->count(),
        ]);
    }

    /**
     * Blokir IP secara Manual
     */
    public function blockIP(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string|max:255',
            'block_duration' => 'nullable|integer|min:60',
        ]);
        
        $blockedIP = BlockedIP::updateOrCreate(
            ['ip_address' => $request->ip_address],
            [
                'reason' => $request->reason,
                'blocked_until' => $request->has('permanent') ? null : 
                    now()->addSeconds($request->get('block_duration', 3600)),
                'blocked_by' => Auth::id(),
            ]
        );
        
        // Catat ke log bahwa ada blokir manual
        $this->wafLogger->logThreat($request, 'manual_block', "IP manually blocked by admin", 3, true);
        
        return response()->json(['message' => 'IP blocked successfully', 'blocked_ip' => $blockedIP]);
    }

    /**
     * Buka Blokir IP
     */
    public function unblockIP(Request $request, $id)
    {
        $blockedIP = BlockedIP::findOrFail($id);
        
        // Kita set expired saja daripada hapus total (untuk history)
        $blockedIP->blocked_until = now()->subDay(); 
        $blockedIP->save();
        
        return response()->json(['message' => 'IP unblocked successfully']);
    }

    /**
     * Ambil Statistik untuk Chart.js
     */
    public function getStats(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        
        // Menggunakan library WAFLogger Anda untuk ambil data grafik
        $stats = $this->wafLogger->getStats($filters);
        
        $stats['top_attacked_endpoints'] = WAFLog::whereNotNull('threat_type')
            ->select('url', DB::raw('COUNT(*) as attacks'))
            ->groupBy('url')
            ->orderBy('attacks', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json($stats);
    }

    /**
     * Bersihkan Log Lama
     */
    public function cleanupLogs(Request $request)
    {
        $days = $request->get('days', 30);
        $cutoff = now()->subDays($days);
        $deleted = WAFLog::where('created_at', '<', $cutoff)->delete();
        
        return response()->json([
            'message' => 'Logs cleaned up successfully',
            'deleted_count' => $deleted
        ]);
    }

    /**
     * Helper: Hitung Statistik Dashboard
     */
    private function getDashboardStats(): array
    {
        $today = now()->startOfDay();
        
        return [
            'total_requests_today' => WAFLog::where('created_at', '>=', $today)->count(),
            'threats_today' => WAFLog::where('created_at', '>=', $today)
                ->whereNotNull('threat_type')
                ->count(),
            'blocked_today' => WAFLog::where('created_at', '>=', $today)
                ->where('blocked', true)
                ->count(),
            'active_blocks' => BlockedIP::where(function ($q) {
                $q->whereNull('blocked_until')
                  ->orWhere('blocked_until', '>', now());
            })->count(),
            'total_users' => User::count(),
        ];
    }
}