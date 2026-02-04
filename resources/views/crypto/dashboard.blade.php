@extends('layouts.app')

@section('title', 'Security Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Requests Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalRequests">{{ $stats['total_requests_today'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-bar-chart-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Threats Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="threatsToday">{{ $stats['threats_today'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-shield-exclamation fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Blocked Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="blockedToday">{{ $stats['blocked_today'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-ban fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active IP Blocks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeBlocks">{{ $stats['active_blocks'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-shield-lock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Security Threats</h6>
                    <a href="{{ route('dashboard.logs') }}" class="btn btn-sm btn-outline-primary">View All Logs</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="threatsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Type</th>
                                    <th>IP Address</th>
                                    <th>Severity</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="threatsBody">
                                @foreach($recentThreats as $threat)
                                <tr>
                                    <td>{{ $threat->created_at->format('H:i:s') }}</td>
                                    <td><span class="badge bg-secondary">{{ strtoupper(str_replace('_', ' ', $threat->threat_type)) }}</span></td>
                                    <td><code>{{ $threat->ip_address }}</code></td>
                                    <td>
                                        @php $color = $threat->severity >= 4 ? 'danger' : ($threat->severity >= 3 ? 'warning' : 'info'); @endphp
                                        <span class="badge bg-{{ $color }}">Level {{ $threat->severity }}</span>
                                    </td>
                                    <td>
                                        @if($threat->blocked)
                                            <span class="badge bg-danger">Blocked</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Logged</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Threat Distribution (Visual Analysis)</h6>
                </div>
                <div class="card-body">
                    <canvas id="threatChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('crypto.encrypt.form') }}" class="btn btn-outline-primary text-start">
                            <i class="bi bi-lock-fill"></i> Encrypt New Data
                        </a>
                        <a href="{{ route('dashboard.blocked-ips') }}" class="btn btn-outline-warning text-start">
                            <i class="bi bi-shield-slash"></i> Manage Blocked IPs
                        </a>
                        <button class="btn btn-primary" id="refreshStats">
                            <i class="bi bi-arrow-clockwise"></i> Refresh Dashboard
                        </button>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">System Health</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge bg-success">WAF Engine Online</span>
                        <span class="badge bg-primary">DB Linked</span>
                    </div>
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 25%" id="cpuBar">CPU: 25%</div>
                    </div>
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: 45%" id="memBar">RAM: 45%</div>
                    </div>
                    <p class="small text-muted">Uptime: <span id="uptime">Checking...</span></p>
                </div>
            </div>

            <div class="alert alert-info shadow-sm small">
                <h6 class="alert-heading fw-bold"><i class="bi bi-info-circle"></i> Security Tip</h6>
                <p class="mb-0">Ensure your <code>APP_KEY</code> is rotated if you suspect a compromise. WAF logs are purged every 30 days automatically.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<style>
    .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
    .border-left-success { border-left: 0.25rem solid #1cc88a !important; }
    .border-left-info { border-left: 0.25rem solid #36b9cc !important; }
    .border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
    .border-left-danger { border-left: 0.25rem solid #e74a3b !important; }
    .card { transition: all 0.2s ease-in-out; }
    .card:hover { transform: scale(1.01); }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let threatChart;

async function refreshDashboard() {
    try {
        // Karena route 'dashboard' di web.php bisa menerima JSON (lihat controller yang kita edit tadi)
        const response = await fetch("{{ route('dashboard') }}", {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();

        // 1. Update Stats Card
        document.getElementById('totalRequests').innerText = data.stats.total_requests_today;
        document.getElementById('threatsToday').innerText = data.stats.threats_today;
        document.getElementById('blockedToday').innerText = data.stats.blocked_today;
        document.getElementById('activeBlocks').innerText = data.stats.active_blocks;

        // 2. Update Table
        const body = document.getElementById('threatsBody');
        body.innerHTML = '';
        data.recent_threats.forEach(t => {
            body.innerHTML += `
                <tr>
                    <td>${new Date(t.created_at).toLocaleTimeString()}</td>
                    <td><span class="badge bg-secondary">${t.threat_type}</span></td>
                    <td><code>${t.ip_address}</code></td>
                    <td><span class="badge bg-info">Level ${t.severity}</span></td>
                    <td><span class="badge bg-${t.blocked ? 'danger' : 'warning'}">${t.blocked ? 'Blocked' : 'Logged'}</span></td>
                </tr>
            `;
        });

        console.log("Dashboard Auto-Refreshed");
    } catch (e) {
        console.error("Dashboard refresh failed", e);
    }
}

function initChart() {
    const ctx = document.getElementById('threatChart').getContext('2d');
    
    // Mock data untuk chart (Bisa diganti dengan fetch dari endpoint stats Anda)
    threatChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Security Threats',
                data: [12, 19, 3, 5, 2, 3, 9],
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });
}

// System Status Mock Update
function updateSystemHealth() {
    const cpu = Math.floor(Math.random() * 20) + 5;
    const mem = Math.floor(Math.random() * 30) + 20;
    document.getElementById('cpuBar').style.width = cpu + '%';
    document.getElementById('cpuBar').innerText = `CPU: ${cpu}%`;
    document.getElementById('memBar').style.width = mem + '%';
    document.getElementById('memBar').innerText = `RAM: ${mem}%`;
    document.getElementById('uptime').innerText = "14 Days, 2 Hours";
}

document.addEventListener('DOMContentLoaded', () => {
    initChart();
    updateSystemHealth();
    
    // Tombol refresh manual
    document.getElementById('refreshStats').addEventListener('click', function() {
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Refreshing...';
        refreshDashboard().then(() => {
            this.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh Dashboard';
        });
    });

    // Auto refresh setiap 30 detik
    setInterval(refreshDashboard, 30000);
});
</script>
@endpush    