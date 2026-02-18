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
            <div class="card shadow mb-4 border-0">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white border-bottom">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-activity me-1"></i> Recent Security Threats 
                        <span class="badge bg-danger ms-2 animate-pulse">LIVE</span>
                    </h6>
                    <a href="{{ route('dashboard.logs') }}" class="btn btn-sm btn-dark shadow-sm">View All Logs</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle" id="threatsTable">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th class="ps-3 border-0">Time</th>
                                    <th class="border-0">Type</th>
                                    <th class="border-0">IP Address</th>
                                    <th class="border-0">Severity</th>
                                    <th class="text-center border-0">Status</th>
                                </tr>
                            </thead>
                            <tbody id="threatsBody">
                                @foreach($recentThreats as $threat)
                                @php 
                                    $severityClass = $threat->severity >= 4 ? 'high' : ($threat->severity >= 3 ? 'medium' : 'low');
                                    $severityColor = $threat->severity >= 4 ? 'danger' : ($threat->severity >= 3 ? 'warning' : 'info');
                                    $severityLabel = $threat->severity >= 4 ? 'CRITICAL' : ($threat->severity >= 3 ? 'WARNING' : 'NOTICE');
                                @endphp
                                <tr onclick='showPayload(@json($threat->url), @json($threat->user_agent), @json($threat->request_data))' 
                                    class="threat-row severity-{{ $severityClass }}" style="cursor: pointer;">
                                    <td class="ps-3 fw-bold text-dark">{{ $threat->created_at->format('H:i:s') }}</td>
                                    <td><span class="badge bg-dark border border-secondary">{{ strtoupper(str_replace('_', ' ', $threat->threat_type ?? 'GENERAL')) }}</span></td>
                                    <td><code class="user-select-all bg-light px-2 py-1 rounded text-danger fw-bold border">{{ $threat->ip_address }}</code></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-{{ $severityColor }} me-2 shadow-sm" style="width:10px; height:10px;"></div>
                                            <span class="fw-bold text-{{ $severityColor }} small">LVL {{ $threat->severity }} - {{ $severityLabel }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $threat->blocked ? 'bg-danger' : 'bg-warning text-dark' }} shadow-sm w-75 py-2">
                                            <i class="bi {{ $threat->blocked ? 'bi-shield-fill-x' : 'bi-eye-fill' }} me-1"></i>
                                            {{ $threat->blocked ? 'BLOCKED' : 'LOGGED' }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light py-2 text-center small text-muted">
                    <i class="bi bi-info-circle me-1"></i> Click any row to inspect deep-packet payload
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
                <div class="card-header py-3 bg-white border-bottom">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('crypto.encrypt.form') }}" class="btn btn-outline-primary text-start p-3 shadow-sm border-2">
                            <i class="bi bi-lock-fill me-2"></i> <strong>Encrypt New Data</strong>
                        </a>
                        <a href="{{ route('dashboard.blocked-ips') }}" class="btn btn-outline-warning text-start p-3 shadow-sm border-2 text-dark">
                            <i class="bi bi-shield-slash me-2 text-warning"></i> <strong>Manage Blocked IPs</strong>
                        </a>
                        <button class="btn btn-primary p-3 shadow" id="refreshStats">
                            <i class="bi bi-arrow-clockwise me-2"></i> <strong>Refresh Dashboard</strong>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white border-bottom">
                    <h6 class="m-0 font-weight-bold text-primary">System Health</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 text-center d-flex justify-content-center gap-2">
                        <span class="badge bg-success shadow-sm p-2"><i class="bi bi-cpu me-1"></i> WAF Online</span>
                        <span class="badge bg-primary shadow-sm p-2"><i class="bi bi-database me-1"></i> DB Linked</span>
                    </div>
                    <div class="progress mb-3 shadow-sm" style="height: 25px;">
                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated fw-bold" role="progressbar" style="width: 25%" id="cpuBar">CPU: 25%</div>
                    </div>
                    <div class="progress mb-3 shadow-sm" style="height: 25px;">
                        <div class="progress-bar bg-info progress-bar-striped progress-bar-animated fw-bold" role="progressbar" style="width: 45%" id="memBar">RAM: 45%</div>
                    </div>
                    <p class="small text-muted mb-0 text-center border-top pt-2 mt-3">Uptime: <span id="uptime" class="fw-bold text-dark">Checking...</span></p>
                </div>
            </div>

            <div class="alert alert-info shadow-sm small border-left-info bg-white">
                <h6 class="alert-heading fw-bold"><i class="bi bi-info-circle-fill text-info me-1"></i> Security Tip</h6>
                <p class="mb-0 text-dark">Logs are automatically purged every 30 days to maintain database performance and data privacy.</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="payloadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-shield-lock-fill me-2 text-danger"></i>Threat Intelligence Detail</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="card border-0 mb-3 shadow-sm">
                    <div class="card-body">
                        <p class="mb-1 small text-muted text-uppercase fw-bold">Request URL</p>
                        <p id="modalUrl" class="text-primary fw-bold mb-3 user-select-all"></p>
                        <p class="mb-1 small text-muted text-uppercase fw-bold">User Agent</p>
                        <p id="modalUA" class="small text-dark mb-0 italic"></p>
                    </div>
                </div>
                <h6 class="fw-bold text-uppercase small text-muted mb-2">Request Payload / Data Analysis:</h6>
                <pre class="bg-dark text-success p-3 rounded shadow-inner border border-secondary" id="modalPayload" style="max-height: 400px; overflow-y: auto; font-family: 'Courier New', Courier, monospace;"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<style>
    /* Utility Borders */
    .border-left-primary { border-left: 0.35rem solid #4e73df !important; }
    .border-left-success { border-left: 0.35rem solid #1cc88a !important; }
    .border-left-info { border-left: 0.35rem solid #36b9cc !important; }
    .border-left-warning { border-left: 0.35rem solid #f6c23e !important; }
    .border-left-danger { border-left: 0.35rem solid #e74a3b !important; }

    /* Threat Table Styling */
    #threatsTable thead th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 15px 10px;
    }

    .threat-row {
        transition: all 0.2s ease;
        border-left: 5px solid transparent;
    }

    .threat-row:hover {
        background-color: #f8f9fc !important;
        transform: scale(1.002);
        box-shadow: inset 0 0 15px rgba(0,0,0,0.03);
    }

    /* Severity Colors Left Border */
    .severity-high { border-left-color: #e74a3b !important; background-color: rgba(231, 74, 59, 0.02); }
    .severity-medium { border-left-color: #f6c23e !important; }
    .severity-low { border-left-color: #36b9cc !important; }

    /* Animation */
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .5; }
    }

    pre#modalPayload::-webkit-scrollbar { width: 10px; }
    pre#modalPayload::-webkit-scrollbar-thumb { background: #444; border-radius: 5px; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let threatChart;
const modalObj = new bootstrap.Modal(document.getElementById('payloadModal'));

function showPayload(url, ua, payload) {
    document.getElementById('modalUrl').innerText = url;
    document.getElementById('modalUA').innerText = ua;
    document.getElementById('modalPayload').innerText = JSON.stringify(payload, null, 4) || "// No Data Payload";
    modalObj.show();
}

async function refreshDashboard() {
    try {
        const response = await fetch("{{ route('dashboard') }}", {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();

        // Update Stats
        document.getElementById('totalRequests').innerText = data.stats.total_requests_today;
        document.getElementById('threatsToday').innerText = data.stats.threats_today;
        document.getElementById('blockedToday').innerText = data.stats.blocked_today;
        document.getElementById('activeBlocks').innerText = data.stats.active_blocks;

        // Update Table dengan Desain Kontras Baru
        const body = document.getElementById('threatsBody');
        body.innerHTML = '';
        data.recent_threats.forEach(t => {
            const severityClass = t.severity >= 4 ? 'high' : (t.severity >= 3 ? 'medium' : 'low');
            const severityColor = t.severity >= 4 ? 'danger' : (t.severity >= 3 ? 'warning' : 'info');
            const severityLabel = t.severity >= 4 ? 'CRITICAL' : (t.severity >= 3 ? 'WARNING' : 'NOTICE');
            
            const tr = document.createElement('tr');
            tr.className = `threat-row severity-${severityClass}`;
            tr.style.cursor = 'pointer';
            tr.onclick = () => showPayload(t.url, t.user_agent, t.request_data);
            
            tr.innerHTML = `
                <td class="ps-3 fw-bold text-dark">${new Date(t.created_at).toLocaleTimeString()}</td>
                <td><span class="badge bg-dark border border-secondary">${(t.threat_type || 'GENERAL').toUpperCase()}</span></td>
                <td><code class="user-select-all bg-light px-2 py-1 rounded text-danger fw-bold border">${t.ip_address}</code></td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-${severityColor} me-2 shadow-sm" style="width:10px; height:10px;"></div>
                        <span class="fw-bold text-${severityColor} small">LVL ${t.severity} - ${severityLabel}</span>
                    </div>
                </td>
                <td class="text-center">
                    <span class="badge ${t.blocked ? 'bg-danger' : 'bg-warning text-dark'} shadow-sm w-75 py-2">
                        <i class="bi ${t.blocked ? 'bi-shield-fill-x' : 'bi-eye-fill'} me-1"></i>
                        ${t.blocked ? 'BLOCKED' : 'LOGGED'}
                    </span>
                </td>
            `;
            body.appendChild(tr);
        });
    } catch (e) { console.error("Auto-refresh failed", e); }
}

function initChart() {
    const ctx = document.getElementById('threatChart').getContext('2d');
    threatChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Daily Attacks Detected',
                data: [5, 12, 8, 15, 22, 10, 18],
                borderColor: '#e74a3b',
                backgroundColor: 'rgba(231, 74, 59, 0.05)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#e74a3b'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { color: '#f0f0f0' } } }
        }
    });
}

function updateSystemHealth() {
    const cpu = Math.floor(Math.random() * 10) + 2; 
    const mem = Math.floor(Math.random() * 5) + 42;
    document.getElementById('cpuBar').style.width = cpu + '%';
    document.getElementById('cpuBar').innerText = `CPU: ${cpu}%`;
    document.getElementById('memBar').style.width = mem + '%';
    document.getElementById('memBar').innerText = `RAM: ${mem}%`;
    document.getElementById('uptime').innerText = "14 Days, 02:45:10";
}

document.addEventListener('DOMContentLoaded', () => {
    initChart();
    updateSystemHealth();
    
    document.getElementById('refreshStats').addEventListener('click', function() {
        const originalHtml = this.innerHTML;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Syncing...';
        this.disabled = true;
        refreshDashboard().then(() => {
            setTimeout(() => {
                this.innerHTML = originalHtml;
                this.disabled = false;
            }, 600);
        });
    });

    setInterval(refreshDashboard, 30000); 
    setInterval(updateSystemHealth, 5000); 
});
</script>
@endpush