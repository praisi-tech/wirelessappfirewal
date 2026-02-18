@extends('layouts.app')

@section('title', 'Security Audit Logs')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Security Audit Logs</h1>
            <p class="text-muted small mb-0">Review and filter all security events detected by WAF.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dashboard.logs.export') }}" class="btn btn-success btn-sm shadow-sm">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-light rounded-3">
            <form action="{{ route('dashboard.logs') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-bold text-uppercase text-muted mb-1">Search IP Address</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="ip" class="form-control border-start-0" placeholder="e.g. 192.168.1.1" value="{{ request('ip') }}">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-bold text-uppercase text-muted mb-1">Threat Type</label>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="threatTypeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shield-exclamation"></i>
                            {{ request('type') ? strtoupper(str_replace('_', ' ', request('type'))) : 'All Threats' }}
                        </button>
                        <ul class="dropdown-menu w-100" aria-labelledby="threatTypeDropdown">
                            <li><a class="dropdown-item" href="?type=&severity={{ request('severity') }}">All Threats</a></li>
                            @foreach($threatTypes as $type)
                                <li><a class="dropdown-item {{ request('type') == $type ? 'active' : '' }}" 
                                       href="?type={{ $type }}&severity={{ request('severity') }}">
                                    {{ strtoupper(str_replace('_', ' ', $type)) }}
                                </a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-bold text-uppercase text-muted mb-1">Min. Severity</label>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="severityDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-speedometer"></i>
                            {{ request('severity') ? 'Level ' . request('severity') . '+' : 'Any Severity' }}
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="severityDropdown">
                            <li><a class="dropdown-item {{ !request('severity') ? 'active' : '' }}" href="?severity=&type={{ request('type') }}">Any Severity</a></li>
                            <li><a class="dropdown-item {{ request('severity') == '1' ? 'active' : '' }}" href="?severity=1&type={{ request('type') }}">Level 1+</a></li>
                            <li><a class="dropdown-item {{ request('severity') == '3' ? 'active' : '' }}" href="?severity=3&type={{ request('type') }}">Level 3+</a></li>
                            <li><a class="dropdown-item {{ request('severity') == '5' ? 'active' : '' }}" href="?severity=5&type={{ request('type') }}">Critical Only</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 d-flex justify-content-lg-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                        <i class="bi bi-filter"></i> Apply Filters
                    </button>
                    <a href="{{ route('dashboard.logs') }}" class="btn btn-white btn-sm px-4 border shadow-sm">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th class="ps-4 py-3 border-0">Timestamp</th>
                            <th class="border-0">IP Address</th>
                            <th class="border-0">Threat Type</th>
                            <th class="border-0">Severity</th>
                            <th class="border-0">Request Path</th>
                            <th class="text-center border-0">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($logs as $log)
                        <tr>
                            <td class="ps-4 small text-muted">
                                {{ $log->created_at->format('d/m/Y') }}<br>
                                <strong class="text-dark">{{ $log->created_at->format('H:i:s') }}</strong>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="badge bg-white text-dark border fw-medium mb-1">{{ $log->ip_address }}</span>
                                    <span class="x-small text-muted">{{ $log->user->email ?? 'Guest User' }}</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $threatColor = match($log->threat_type) {
                                        'sql_injection' => 'danger',
                                        'replay_attack' => 'warning',
                                        'invalid_signature' => 'info',
                                        'manual_block' => 'dark',
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $threatColor }}-soft text-{{ $threatColor }} border border-{{ $threatColor }}-subtle px-2 py-1">
                                    <i class="bi bi-shield-exclamation me-1"></i>
                                    {{ strtoupper(str_replace('_', ' ', $log->threat_type ?? 'NORMAL')) }}
                                </span>
                            </td>
                            <td>
                                @php 
                                    $sevColor = $log->severity >= 4 ? 'danger' : ($log->severity >= 3 ? 'warning' : 'info'); 
                                @endphp
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-{{ $sevColor }} me-2" style="width: 8px; height: 8px; border-radius: 50%; padding: 0;"> </span>
                                    <span class="small fw-medium">Level {{ $log->severity }}</span>
                                </div>
                            </td>
                            <td>
                                <code class="small text-truncate d-inline-block text-primary" style="max-width: 180px;" title="{{ $log->url }}">
                                    {{ $log->method }}: {{ Str::after($log->url, '/api') }}
                                </code>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-light border shadow-sm px-3" 
                                        onclick='viewLogDetail(@json($log->url), @json($log->user_agent), @json($log->request_data), @json($log->description))'>
                                    <i class="bi bi-search"></i> Inspect
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-shield-check display-4"></i>
                                    <p class="mt-2 mb-0">No security events found matching your criteria.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($logs->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="small text-muted">
                    Showing <strong>{{ $logs->firstItem() }}</strong> to <strong>{{ $logs->lastItem() }}</strong> of {{ $logs->total() }} entries
                </div>
                <div class="pagination-wrapper">
                    {{ $logs->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@include('crypto.forms.payload-modal') 
@endsection

@push('styles')
<style>
    
    .x-small { font-size: 0.7rem; }

    /* Fix Pagination */
    .pagination-wrapper nav svg { width: 1rem !important; height: 1rem !important; }
    .pagination-wrapper .pagination { margin-bottom: 0; display: flex; padding-left: 0; list-style: none; }
    .pagination-wrapper .page-link { padding: 0.25rem 0.6rem; font-size: 0.8rem; }
    .pagination-wrapper nav div:first-child { display: none !important; }
</style>
@endpush

@push('scripts')
<script>
    function viewLogDetail(url, ua, payload, description) {
        // Pastikan elemen ID di payload-modal.blade.php sesuai
        const modalUrl = document.getElementById('modalUrl');
        const modalUA = document.getElementById('modalUA');
        const modalPayload = document.getElementById('modalPayload');
        const modalDesc = document.getElementById('modalDescription'); // Opsional jika modal punya field desc

        if(modalUrl) modalUrl.innerText = url || 'N/A';
        if(modalUA) modalUA.innerText = ua || 'Unknown User Agent';
        if(modalDesc) modalDesc.innerText = description || 'No detailed description.';
        
        let formattedPayload = "No payload data available.";
        if (payload) {
            try {
                // Handle jika payload sudah berupa object atau string JSON
                const obj = typeof payload === 'string' ? JSON.parse(payload) : payload;
                formattedPayload = JSON.stringify(obj, null, 4);
            } catch (e) {
                formattedPayload = payload;
            }
        }
        
        if(modalPayload) modalPayload.innerText = formattedPayload;
        
        const modalEl = document.getElementById('payloadModal');
        if (modalEl) {
            const logModal = new bootstrap.Modal(modalEl);
            logModal.show();
        }
    }
</script>
@endpush