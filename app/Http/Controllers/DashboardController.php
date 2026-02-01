<?php

namespace App\Http\Controllers;

use App\Models\WAFLog;
use App\Models\BlockedIP;
use App\Models\User;
use App\WAF\Logger\WAFLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private WAFLogger $wafLogger;

    public function __construct(WAFLogger $wafLogger)
    {
        $this->wafLogger = $wafLogger;
        $this->middleware('auth');
    }

    public function dashboard()
    {
        $user = Auth::user();
        
        // Get statistics
        $stats = $this->getDashboardStats();
        
        // Get recent threats
        $recentThreats = WAFLog::whereNotNull('threat_type')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get top blocked IPs
        $topBlockedIPs = BlockedIP::orderBy('attempts', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
            ],
            'stats' => $stats,
            'recent_threats' => $recentThreats,
            'top_blocked_ips' => $topBlockedIPs,
        ]);
    }

    public function getLogs(Request $request)
    {
        $this->authorize('viewAny', WAFLog::class);
        
        $query = WAFLog::with('user');
        
        // Apply filters
        if ($request->has('type')) {
            $query->where('threat_type', $request->type);
        }
        
        if ($request->has('severity')) {
            $query->where('severity', '>=', $request->severity);
        }
        
        if ($request->has('blocked')) {
            $query->where('blocked', $request->boolean('blocked'));
        }
        
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
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

    public function getBlockedIPs(Request $request)
    {
        $this->authorize('viewAny', BlockedIP::class);
        
        $query = BlockedIP::with('user');
        
        if ($request->has('active')) {
            $query->where(function ($q) {
                $q->whereNull('blocked_until')
                  ->orWhere('blocked_until', '>', now());
            });
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('ip_address', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
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

    public function blockIP(Request $request)
    {
        $this->authorize('create', BlockedIP::class);
        
        $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string|max:255',
            'block_duration' => 'integer|min:60',
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
        
        // Log the action
        $this->wafLogger->logThreat(
            $request,
            'manual_block',
            "IP manually blocked: {$request->reason}",
            3,
            true,
            ['ip' => $request->ip_address, 'duration' => $request->get('block_duration')]
        );
        
        return response()->json([
            'message' => 'IP blocked successfully',
            'blocked_ip' => $blockedIP,
        ]);
    }

    public function unblockIP(Request $request, $id)
    {
        $blockedIP = BlockedIP::findOrFail($id);
        $this->authorize('delete', $blockedIP);
        
        $blockedIP->blocked_until = now()->subDay(); // Set to past to effectively unblock
        $blockedIP->save();
        
        // Log the action
        $this->wafLogger->logThreat(
            $request,
            'manual_unblock',
            "IP manually unblocked",
            1,
            false,
            ['ip' => $blockedIP->ip_address]
        );
        
        return response()->json([
            'message' => 'IP unblocked successfully',
        ]);
    }

    public function getStats(Request $request)
    {
        $this->authorize('viewStats', WAFLog::class);
        
        $filters = $request->validate([
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'group_by' => 'in:hour,day,week,month',
        ]);
        
        $stats = $this->wafLogger->getStats($filters);
        
        // Add additional statistics
        $stats['top_attacked_endpoints'] = WAFLog::whereNotNull('threat_type')
            ->select('url', DB::raw('COUNT(*) as attacks'))
            ->groupBy('url')
            ->orderBy('attacks', 'desc')
            ->limit(10)
            ->get();
            
        $stats['top_attacker_ips'] = WAFLog::whereNotNull('threat_type')
            ->select('ip_address', DB::raw('COUNT(*) as attacks'))
            ->groupBy('ip_address')
            ->orderBy('attacks', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json($stats);
    }

    public function cleanupLogs(Request $request)
    {
        $this->authorize('cleanup', WAFLog::class);
        
        $days = $request->get('days', config('waf.logging.storage_days', 30));
        $cutoff = now()->subDays($days);
        
        $deleted = WAFLog::where('created_at', '<', $cutoff)->delete();
        
        return response()->json([
            'message' => 'Logs cleaned up successfully',
            'deleted_count' => $deleted,
            'cutoff_date' => $cutoff,
        ]);
    }

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
            'recent_logins' => User::where('last_login_at', '>=', now()->subDay())
                ->count(),
        ];
    }
}