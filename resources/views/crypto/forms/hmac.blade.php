@extends('layouts.app')

@section('title', 'HMAC Generation')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 text-dark"><i class="bi bi-patch-check-fill me-2"></i>HMAC Generation</h5>
                    <a href="{{ route('crypto.dashboard') }}" class="btn btn-sm btn-outline-dark shadow-sm">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body p-4">
                    
                    <p class="text-muted small">
                        HMAC (Hash-based Message Authentication Code) ensures that a message has not been tampered with and originates from a trusted sender.
                    </p>

                    @if(session('error'))
                        <div class="alert alert-danger border-0 shadow-sm mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('crypto.hmac') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="data" class="form-label fw-bold">Message / Data</label>
                            <textarea class="form-control @error('data') is-invalid @enderror" 
                                      id="data" name="data" rows="3" 
                                      placeholder="The content you want to sign..." required>{{ old('data') }}</textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="key" class="form-label fw-bold">Secret Key</label>
                            <input type="password" class="form-control" id="key" name="key" 
                                   placeholder="Shared secret key used for hashing" required>
                            <div class="form-text">Both parties must use the exact same key to verify the HMAC.</div>
                        </div>

                        <div class="mb-4">
                            <label for="algo" class="form-label fw-bold">Hashing Algorithm</label>
                            <select class="form-select" name="algo" id="algo">
                                <option value="sha256" selected>SHA-256 (Recommended)</option>
                                <option value="sha512">SHA-512</option>
                                <option value="md5">MD5 (Legacy)</option>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning btn-lg shadow-sm text-dark fw-bold">
                                <i class="bi bi-fingerprint me-2"></i>Generate HMAC
                            </button>
                        </div>
                    </form>
                    
                    @if(session('result'))
                    <div class="mt-5 pt-4 border-top">
                        <h5 class="text-dark mb-3">Generated HMAC Result:</h5>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">HMAC Signature (Hex)</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light font-monospace" id="res_hmac" value="{{ session('result')['hmac'] }}" readonly>
                                <button class="btn btn-outline-warning text-dark" type="button" onclick="copyValue('res_hmac', this)">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                        </div>

                        <div class="alert alert-secondary py-2 small border-0 shadow-sm">
                            <i class="bi bi-info-circle me-1"></i> Algorithm used: <strong>{{ session('result')['algorithm'] }}</strong>
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function copyValue(id, btn) {
    const input = document.getElementById(id);
    const originalHtml = btn.innerHTML;
    try {
        await navigator.clipboard.writeText(input.value);
        btn.innerHTML = '<i class="bi bi-check2"></i> Done!';
        setTimeout(() => { btn.innerHTML = originalHtml; }, 2000);
    } catch (err) { console.error('Error copying', err); }
}
</script>
@endsection