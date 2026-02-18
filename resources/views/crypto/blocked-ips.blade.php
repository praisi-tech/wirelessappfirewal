@extends('layouts.app')

@section('title', 'Blocked IP Management')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Blocked IP Addresses</h1>
            <p class="text-muted small mb-0">Manage and monitor blocked IP addresses.</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-danger">{{ $totalActive }} Active Blocks</span>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    {{-- Success Alert --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Info Alert --}}
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-2"></i>
        <strong>MILESTONE: Automatic Protection</strong> — Blocked IPs are automatically prevented from accessing the API. 
        Manual blocks can be temporary or permanent.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="row mb-4">
        {{-- Block New IP Form --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-shield-x"></i> Block IP Address</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('dashboard.block-ip') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="ip_address" class="form-label fw-bold">IP Address</label>
                            <input type="text" class="form-control @error('ip_address') is-invalid @enderror" 
                                   id="ip_address" name="ip_address" placeholder="e.g. 192.168.1.100" required>
                            @error('ip_address')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label fw-bold">Reason for Block</label>
                            <div class="dropdown @error('reason') is-invalid @enderror">
                                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" 
                                        id="reasonDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span id="reasonDisplay">Select a reason...</span>
                                </button>
                                <ul class="dropdown-menu w-100" aria-labelledby="reasonDropdown">
                                    <li><a class="dropdown-item" href="#" data-value="">-- Select a reason --</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" data-value="sql_injection"><i class="bi bi-bug"></i> SQL Injection Attempt</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="xss_attack"><i class="bi bi-exclamation-circle"></i> XSS Attack Attempt</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="brute_force"><i class="bi bi-lightning"></i> Brute Force Attack</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="replay_attack"><i class="bi bi-arrow-repeat"></i> Replay Attack</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="suspicious_activity"><i class="bi bi-question-circle"></i> Suspicious Activity</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="manual_review"><i class="bi bi-eye"></i> Manual Review</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="other"><i class="bi bi-three-dots"></i> Other</a></li>
                                </ul>
                            </div>
                            <input type="hidden" name="reason" id="reason" value="" required>
                            @error('reason')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="block_duration" class="form-label fw-bold">Block Duration</label>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" 
                                        id="durationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-hourglass-split"></i>
                                    <span id="durationDisplay">Select duration...</span>
                                </button>
                                <ul class="dropdown-menu w-100" aria-labelledby="durationDropdown">
                                    <li><a class="dropdown-item" href="#" data-value="3600" data-label="1 Hour"><i class="bi bi-clock"></i> 1 Hour</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="86400" data-label="1 Day"><i class="bi bi-calendar"></i> 1 Day</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="604800" data-label="1 Week"><i class="bi bi-calendar2-week"></i> 1 Week</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="2592000" data-label="30 Days"><i class="bi bi-calendar2-month"></i> 30 Days</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" data-value="permanent" data-label="Permanent"><i class="bi bi-lock-fill"></i> Permanent</a></li>
                                </ul>
                            </div>
                            <input type="hidden" name="block_duration" id="block_duration" value="" required>
                            <small class="text-muted d-block mt-2">
                                Select "Permanent" for permanent block without expiration.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-danger w-100 shadow-sm"
                                onclick="return confirm('Block this IP address?')">
                            <i class="bi bi-shield-x"></i> Block IP
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Statistics --}}
        <div class="col-lg-8">
            <div class="row">
                <div class="col-sm-6 mb-4">
                    <div class="card shadow-sm border-0 border-left-danger">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="bi bi-shield-exclamation text-danger" style="font-size: 2rem;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-muted">Active Blocks</h6>
                                    <h3 class="mb-0">{{ $totalActive }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card shadow-sm border-0 border-left-info">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="bi bi-list-check text-info" style="font-size: 2rem;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-muted">Total Entries</h6>
                                    <h3 class="mb-0">{{ $blockedIPs->total() }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Blocked IPs Table --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th class="ps-4 py-3 border-0">IP Address</th>
                            <th class="border-0">Reason</th>
                            <th class="border-0">Blocked By</th>
                            <th class="border-0">Blocked At</th>
                            <th class="border-0">Expires</th>
                            <th class="border-0">Attempts</th>
                            <th class="text-center border-0">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($blockedIPs as $ip)
                        <tr>
                            <td class="ps-4">
                                <code class="bg-light p-2 rounded">{{ $ip->ip_address }}</code>
                            </td>
                            <td>
                                @php
                                    $reasonBadge = match($ip->reason) {
                                        'sql_injection' => 'danger',
                                        'xss_attack' => 'danger',
                                        'brute_force' => 'warning',
                                        'replay_attack' => 'warning',
                                        'suspicious_activity' => 'info',
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $reasonBadge }}">
                                    {{ str_replace('_', ' ', strtoupper($ip->reason)) }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $ip->blockedBy?->name ?? 'System' }}
                                </small>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $ip->created_at->format('d M Y H:i') }}
                                </small>
                            </td>
                            <td>
                                @if($ip->blocked_until)
                                    @if($ip->blocked_until->isFuture())
                                        <small class="text-warning">
                                            <i class="bi bi-clock"></i> 
                                            {{ $ip->blocked_until->diffForHumans() }}
                                        </small>
                                    @else
                                        <small class="text-success">
                                            <i class="bi bi-check-circle"></i> Expired
                                        </small>
                                    @endif
                                @else
                                    <span class="badge bg-danger">Permanent</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $ip->attempts }}</span>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('dashboard.unblock-ip', $ip->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border" 
                                            title="Unblock this IP"
                                            onclick="return confirm('Unblock this IP?')">
                                        <i class="bi bi-trash"></i> Unblock
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-shield-check display-4"></i>
                                    <p class="mt-2 mb-0">No blocked IP addresses.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($blockedIPs->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Showing {{ $blockedIPs->firstItem() }} to {{ $blockedIPs->lastItem() }} of {{ $blockedIPs->total() }} entries
                </small>
                <div>
                    {{ $blockedIPs->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Reason dropdown items handler
    const reasonItems = document.querySelectorAll('#reasonDropdown + .dropdown-menu .dropdown-item');
    const reasonInput = document.getElementById('reason');
    const reasonDisplay = document.getElementById('reasonDisplay');

    reasonItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const value = this.getAttribute('data-value');
            const text = this.textContent.trim();
            
            reasonInput.value = value;
            reasonDisplay.textContent = text || 'Select a reason...';
            
            // Update active state
            reasonItems.forEach(i => i.classList.remove('active'));
            if (value) {
                this.classList.add('active');
            }
        });
    });

    // Duration dropdown items handler
    const durationItems = document.querySelectorAll('#durationDropdown + .dropdown-menu .dropdown-item');
    const durationInput = document.getElementById('block_duration');
    const durationDisplay = document.getElementById('durationDisplay');

    durationItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const value = this.getAttribute('data-value');
            const label = this.getAttribute('data-label');
            
            durationInput.value = value;
            durationDisplay.textContent = label || 'Select duration...';
            
            // Update active state
            durationItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Set initial values if any (for form editing)
    const reasonValue = reasonInput.value;
    const durationValue = durationInput.value;
    
    if (reasonValue) {
        const reasonItem = document.querySelector(`#reasonDropdown + .dropdown-menu .dropdown-item[data-value="${reasonValue}"]`);
        if (reasonItem) {
            reasonItem.classList.add('active');
            reasonDisplay.textContent = reasonItem.textContent.trim();
        }
    }
    
    if (durationValue) {
        const durationItem = document.querySelector(`#durationDropdown + .dropdown-menu .dropdown-item[data-value="${durationValue}"]`);
        if (durationItem) {
            durationItem.classList.add('active');
            durationDisplay.textContent = durationItem.getAttribute('data-label');
        }
    }
});
</script>
@endpush

@endsection
