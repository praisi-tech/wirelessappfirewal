@extends('layouts.app')

@section('title', 'API Keys Management')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">API Keys & Credentials</h1>
            <p class="text-muted small mb-0">Generate and manage your API credentials for secure access.</p>
        </div>
        <a href="{{ route('profile') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left"></i> Back to Profile
        </a>
    </div>

    {{-- Info Alert --}}
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Security Notice:</strong> Your API credentials are used to sign requests for Crypto WAF protection. 
        Keep your secret key safe and never share it.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    {{-- Success Alert --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        {{-- Current Credentials --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-key"></i> Current API Credentials</h5>
                </div>
                <div class="card-body p-4">
                    @if($user->api_key)
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted">API Key</label>
                            <div class="input-group">
                                <input type="text" class="form-control border-end-0 bg-light" 
                                       value="{{ $user->api_key }}" id="apiKey" readonly>
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="copyToClipboard('apiKey')">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Use this key in the <code>X-API-Key</code> header of your requests.
                            </small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted">Secret Key</label>
                            <div class="input-group">
                                <input type="password" class="form-control border-end-0 bg-light" 
                                       value="{{ $user->secret_key }}" id="secretKey" readonly>
                                <button class="btn btn-outline-secondary" type="button" 
                                        id="toggleSecret">
                                    <i class="bi bi-eye"></i> Show
                                </button>
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="copyToClipboard('secretKey')">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                            <small class="text-warning d-block mt-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <strong>NEVER share or expose your secret key.</strong> 
                                It's used to generate request signatures.
                            </small>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3 text-secondary">Generate New Credentials</h6>
                        <p class="text-muted small mb-4">
                            Generate a new API key and secret key. The old credentials will no longer work.
                        </p>

                        <form action="{{ route('api-key.generate') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning shadow-sm" 
                                    onclick="return confirm('This will invalidate your current API credentials. Continue?')">
                                <i class="bi bi-arrow-clockwise"></i> Generate New Key
                            </button>
                        </form>

                        <form action="{{ route('api-key.revoke') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-danger shadow-sm" 
                                    onclick="return confirm('This will permanently revoke all API access. Continue?')">
                                <i class="bi bi-trash"></i> Revoke Access
                            </button>
                        </form>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-key-fill text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">You don't have API credentials yet.</p>
                            <form action="{{ route('api-key.generate') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary shadow-sm">
                                    <i class="bi bi-plus-circle"></i> Generate API Key
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Usage Example --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-code"></i> API Usage Example</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-3">Include your API credentials in every request:</p>
                    <pre class="bg-light p-3 rounded"><code>const crypto = require('crypto');

// Your credentials
const apiKey = '{{ $user->api_key ?? 'YOUR_API_KEY' }}';
const secretKey = '{{ $user->secret_key ?? 'YOUR_SECRET_KEY' }}';

// Generate request signature
const timestamp = Math.floor(Date.now() / 1000);
const nonce = crypto.randomUUID();

const data = {
    data: 'Hello World',
    timestamp: timestamp,
    nonce: nonce,
    method: 'POST',
    path: '/api/crypto/encrypt'
};

const signature = crypto
    .createHmac('sha256', secretKey)
    .update(JSON.stringify(data))
    .digest('hex');

// Make request
fetch('http://localhost:8000/api/crypto/encrypt', {
    method: 'POST',
    headers: {
        'X-API-Key': apiKey,
        'X-Timestamp': timestamp,
        'X-Nonce': nonce,
        'X-Signature': signature,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ data: 'Hello World' })
})
.then(r => r.json())
.then(d => console.log('Success:', d));</code></pre>
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 border-left-primary mb-4">
                <div class="card-body p-4">
                    <h6 class="mb-3"><i class="bi bi-shield-check"></i> Security Features</h6>
                    <ul class="small list-unstyled">
                        <li class="mb-2"><strong>✓ HMAC-SHA256</strong> - Request signing</li>
                        <li class="mb-2"><strong>✓ AES-256-GCM</strong> - Data encryption</li>
                        <li class="mb-2"><strong>✓ Nonce</strong> - Replay attack prevention</li>
                        <li class="mb-2"><strong>✓ Timestamp</strong> - Request freshness check</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0 border-left-success mb-4">
                <div class="card-body p-4">
                    <h6 class="mb-3"><i class="bi bi-info-circle"></i> Headers Required</h6>
                    <code class="d-block mb-2">X-API-Key</code>
                    <code class="d-block mb-2">X-Timestamp</code>
                    <code class="d-block mb-2">X-Nonce</code>
                    <code class="d-block">X-Signature</code>
                </div>
            </div>

            <div class="card shadow-sm border-0 border-left-warning">
                <div class="card-body p-4">
                    <h6 class="mb-3"><i class="bi bi-exclamation-triangle"></i> Important Notes</h6>
                    <ul class="small list-unstyled text-muted">
                        <li class="mb-2">• Secret keys are encrypted at rest</li>
                        <li class="mb-2">• Regenerate if compromised</li>
                        <li class="mb-2">• Old keys expire after regeneration</li>
                        <li class="mb-2">• All API calls are logged</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    // Toggle secret key visibility
    document.getElementById('toggleSecret')?.addEventListener('click', function() {
        const field = document.getElementById('secretKey');
        const btn = this;
        
        if (field.type === 'password') {
            field.type = 'text';
            btn.innerHTML = '<i class="bi bi-eye-slash"></i> Hide';
        } else {
            field.type = 'password';
            btn.innerHTML = '<i class="bi bi-eye"></i> Show';
        }
    });

    // Copy to clipboard
    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        element.select();
        document.execCommand('copy');
        
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
        
        setTimeout(() => {
            btn.innerHTML = originalText;
        }, 2000);
    }
</script>
@endsection
