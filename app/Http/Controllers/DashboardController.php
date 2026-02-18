<?php

namespace App\Http\Controllers;

use App\Models\WafLog; 
use App\Models\BlockedIP;
use App\Models\User;
use App\WAF\Logger\WAFLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
     * Registrasi Middleware untuk Controller
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
    public function index(Request $request)
    {
        $user = Auth::user();
        $stats = $this->getDashboardStats();
        
        $recentThreats = WafLog::whereNotNull('threat_type')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        $topBlockedIPs = BlockedIP::orderBy('attempts', 'desc')
            ->limit(10)
            ->get();
        
        if ($request->expectsJson()) {
            return response()->json([
                'user' => $user,
                'stats' => $stats,
                'recent_threats' => $recentThreats,
                'top_blocked_ips' => $topBlockedIPs,
            ]);
        }

        return view('crypto.dashboard', compact('user', 'stats', 'recentThreats', 'topBlockedIPs'));
    }

    /**
     * Menampilkan Halaman Audit Logs (Milestone: Observability)
     */
    public function logs(Request $request)
    {
        $query = WafLog::with('user');
        
        // Filter Pencarian
        if ($request->filled('type')) {
            $query->where('threat_type', $request->type);
        }
        
        if ($request->filled('ip')) {
            $query->where('ip_address', 'like', "%{$request->ip}%");
        }

        if ($request->filled('severity')) {
            $query->where('severity', '>=', $request->severity);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        
        /**
         * UPDATED: Dropdown Logic
         * We merge default security types with unique types found in the DB.
         * This ensures the dropdown isn't empty on a new install.
         */
        $defaultTypes = collect([
            'invalid_signature', 
            'replay_attack', 
            'sql_injection', 
            'manual_block', 
            'xss_attack'
        ]);

        $dbTypes = WafLog::select('threat_type')
                        ->distinct()
                        ->whereNotNull('threat_type')
                        ->pluck('threat_type');

        // Merge, unique, and sort for a clean dropdown list
        $threatTypes = $defaultTypes->merge($dbTypes)->unique()->sort();

        return view('crypto.logs', compact('logs', 'threatTypes'));
    }

    /**
     * Export ke CSV (Native PHP - Milestone: Observability)
     */
    public function export()
    {
        $fileName = 'WAF_Security_Report_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Timestamp', 'IP Address', 'User', 'Threat Type', 'Severity', 'Method', 'URL', 'Status', 'Description'];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            WafLog::with('user')->latest()->chunk(500, function($logs) use ($file) {
                foreach ($logs as $log) {
                    fputcsv($file, [
                        $log->id,
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->ip_address,
                        $log->user ? $log->user->email : 'Guest',
                        strtoupper(str_replace('_', ' ', $log->threat_type ?? 'NORMAL')),
                        'Level ' . ($log->severity ?? 1),
                        $log->method,
                        $log->url,
                        $log->blocked ? 'Blocked' : 'Allowed',
                        $log->description,
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * List IP yang sedang diblokir (untuk view/blade)
     */
    public function showBlockedIPsView(Request $request)
    {
        $query = BlockedIP::with('blockedBy');
        
        if ($request->has('active')) {
            $query->where(function ($q) {
                $q->whereNull('blocked_until')
                  ->orWhere('blocked_until', '>', now());
            });
        }
        
        $blockedIPs = $query->orderBy('created_at', 'desc')->paginate(20);
        
        $totalActive = BlockedIP::where(function ($q) {
            $q->whereNull('blocked_until')
              ->orWhere('blocked_until', '>', now());
        })->count();
        
        return view('crypto.blocked-ips', compact('blockedIPs', 'totalActive'));
    }

    /**
     * List IP yang sedang diblokir (JSON API)
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
     * Blokir IP secara Manual (Milestone: Automatic Protection)
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
                'attempts' => DB::raw('attempts + 1')
            ]
        );
        
        // Milestone: Observability (Logging admin action)
        $this->wafLogger->logThreat($request, 'manual_block', "IP manually blocked by admin: " . Auth::user()->name, 3, true);
        
        return response()->json(['message' => 'IP blocked successfully', 'blocked_ip' => $blockedIP]);
    }

    /**
     * Buka Blokir IP
     */
    public function unblockIP(Request $request, $id)
    {
        $blockedIP = BlockedIP::findOrFail($id);
        $blockedIP->delete(); 
        
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
        
        $stats = $this->wafLogger->getStats($filters);
        
        $stats['top_attacked_endpoints'] = WafLog::whereNotNull('threat_type')
            ->select('url', DB::raw('COUNT(*) as attacks'))
            ->groupBy('url')
            ->orderBy('attacks', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json($stats);
    }

    /**
     * Bersihkan Log Lama (Maintenance)
     */
    public function cleanupLogs(Request $request)
    {
        $days = $request->get('days', 30);
        $cutoff = now()->subDays($days);
        $deleted = WafLog::where('created_at', '<', $cutoff)->delete();
        
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
            'total_requests_today' => WafLog::where('created_at', '>=', $today)->count(),
            'threats_today' => WafLog::where('created_at', '>=', $today)
                ->whereNotNull('threat_type')
                ->count(),
            'blocked_today' => WafLog::where('created_at', '>=', $today)
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