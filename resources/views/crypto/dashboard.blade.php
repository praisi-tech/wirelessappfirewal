@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    <!-- Stats Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Requests Today</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalRequests">0</div>
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
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Threats Today</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="threatsToday">0</div>
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
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Blocked Today</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="blockedToday">0</div>
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
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Active IP Blocks</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeBlocks">0</div>
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
    <!-- Recent Threats -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Security Threats</h6>
                <a href="/logs" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="threatsTable">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Type</th>
                                <th>IP Address</th>
                                <th>Severity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="threatsBody">
                            <!-- Dynamic content will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Threat Chart -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Threat Distribution (Last 7 Days)</h6>
            </div>
            <div class="card-body">
                <canvas id="threatChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick Actions & System Info -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="/crypto/encrypt" class="btn btn-outline-primary">
                        <i class="bi bi-lock-fill"></i> Encrypt Data
                    </a>
                    <a href="/api-keys" class="btn btn-outline-secondary">
                        <i class="bi bi-key-fill"></i> Manage API Keys
                    </a>
                    <a href="/blocked-ips" class="btn btn-outline-warning">
                        <i class="bi bi-shield-slash"></i> View Blocked IPs
                    </a>
                    <button class="btn btn-outline-info" id="refreshStats">
                        <i class="bi bi-arrow-clockwise"></i> Refresh Stats
                    </button>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">System Status</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge bg-success">WAF Active</span>
                    <span class="badge bg-success">Crypto Engine Ready</span>
                    <span class="badge bg-success">Database Connected</span>
                </div>
                <div class="small">
                    <div class="d-flex justify-content-between mb-1">
                        <span>CPU Usage:</span>
                        <span id="cpuUsage">0%</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Memory Usage:</span>
                        <span id="memoryUsage">0%</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Disk Space:</span>
                        <span id="diskSpace">0%</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Uptime:</span>
                        <span id="uptime">0 days</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Tips -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Security Tips</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info small">
                    <h6><i class="bi bi-lightbulb"></i> Best Practices:</h6>
                    <ul class="mb-0">
                        <li>Rotate API keys every 90 days</li>
                        <li>Use strong passwords with Argon2id hashing</li>
                        <li>Monitor failed login attempts regularly</li>
                        <li>Keep your secret keys secure and encrypted</li>
                        <li>Review WAF logs weekly for anomalies</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let threatChart;

// Load dashboard data
async function loadDashboardData() {
    try {
        const response = await fetch('/api/dashboard');
        const data = await response.json();
        
        // Update stats
        document.getElementById('totalRequests').textContent = data.stats.total_requests_today;
        document.getElementById('threatsToday').textContent = data.stats.threats_today;
        document.getElementById('blockedToday').textContent = data.stats.blocked_today;
        document.getElementById('activeBlocks').textContent = data.stats.active_blocks;
        
        // Update threats table
        const threatsBody = document.getElementById('threatsBody');
        threatsBody.innerHTML = '';
        
        data.recent_threats.forEach(threat => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${new Date(threat.created_at).toLocaleTimeString()}</td>
                <td><span class="badge bg-${getThreatBadgeColor(threat.threat_type)}">${threat.threat_type}</span></td>
                <td><code>${threat.ip_address}</code></td>
                <td><span class="badge bg-${getSeverityBadgeColor(threat.severity)}">${threat.severity_label}</span></td>
                <td>${threat.blocked ? '<span class="badge bg-danger">Blocked</span>' : '<span class="badge bg-warning">Logged</span>'}</td>
            `;
            threatsBody.appendChild(row);
        });
        
        // Update system status
        updateSystemStatus();
        
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

function getThreatBadgeColor(type) {
    const colors = {
        'sql_injection': 'danger',
        'xss': 'warning',
        'brute_force': 'info',
        'blocked_ip': 'dark',
        'manual_block': 'secondary',
    };
    return colors[type] || 'primary';
}

function getSeverityBadgeColor(severity) {
    switch(severity) {
        case 1: return 'info';
        case 2: return 'primary';
        case 3: return 'warning';
        case 4: return 'danger';
        default: return 'secondary';
    }
}

async function updateSystemStatus() {
    try {
        const response = await fetch('/api/system-status');
        const data = await response.json();
        
        document.getElementById('cpuUsage').textContent = `${data.cpu_usage}%`;
        document.getElementById('memoryUsage').textContent = `${data.memory_usage}%`;
        document.getElementById('diskSpace').textContent = `${data.disk_usage}%`;
        document.getElementById('uptime').textContent = `${data.uptime_days} days`;
        
    } catch (error) {
        console.error('Error loading system status:', error);
    }
}

async function loadThreatChart() {
    try {
        const response = await fetch('/api/threat-stats?period=7d');
        const data = await response.json();
        
        if (threatChart) {
            threatChart.destroy();
        }
        
        const ctx = document.getElementById('threatChart').getContext('2d');
        threatChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'SQL Injection',
                    data: data.sql_injection,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }, {
                    label: 'XSS Attacks',
                    data: data.xss,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Brute Force',
                    data: data.brute_force,
                    backgroundColor: 'rgba(255, 206, 86, 0.6)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Attacks'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error loading threat chart:', error);
    }
}

// Refresh stats button
document.getElementById('refreshStats').addEventListener('click', () => {
    loadDashboardData();
    loadThreatChart();
});

// Auto-refresh every 30 seconds
setInterval(() => {
    loadDashboardData();
}, 30000);

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    loadDashboardData();
    loadThreatChart();
    updateSystemStatus();
});
</script>
@endpush