@extends('layouts.app')

@section('title', 'Digital Signatures')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0"><i class="bi bi-pen-fill me-2"></i>Digital Signatures</h5>
                    <a href="{{ route('crypto.dashboard') }}" class="btn btn-sm btn-light shadow-sm">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body p-4">
                    
                    <p class="text-muted small">
                        Use your private key to sign a message. Others can verify this signature using your public key to ensure the message is authentic.
                    </p>

                    {{-- Status Messages --}}
                    @if(session('error'))
                        <div class="alert alert-danger border-0 shadow-sm mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('crypto.sign') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="data" class="form-label fw-bold text-dark">Message to Sign</label>
                            <textarea class="form-control @error('data') is-invalid @enderror" 
                                      id="data" name="data" rows="3" 
                                      placeholder="Enter the data or document content to sign..." 
                                      required>{{ old('data') }}</textarea>
                            @error('data') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="private_key" class="form-label fw-bold text-dark">Private Key (PEM Format)</label>
                            <textarea class="form-control font-monospace @error('private_key') is-invalid @enderror" 
                                      id="private_key" name="private_key" rows="5" 
                                      placeholder="-----BEGIN PRIVATE KEY-----" required></textarea>
                            <div class="form-text mt-2 text-danger small">
                                <i class="bi bi-shield-exclamation"></i> Never share your private key with anyone.
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger btn-lg shadow-sm">
                                <i class="bi bi-vector-pen me-2"></i>Generate Signature
                            </button>
                        </div>
                    </form>
                    
                    {{-- Signature Result Section --}}
                    @if(session('result'))
                    <div class="mt-5 pt-4 border-top animate__animated animate__fadeIn">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-danger-subtle p-2 rounded-circle me-3">
                                <i class="bi bi-pencil-square text-danger fs-4"></i>
                            </div>
                            <h5 class="text-danger mb-0">Signature Generated!</h5>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Digital Signature (Base64)</label>
                            <div class="input-group">
                                <textarea class="form-control bg-light font-monospace" id="res_sign" rows="3" readonly>{{ session('result')['signature'] }}</textarea>
                                <button class="btn btn-outline-danger" type="button" onclick="copyValue('res_sign', this)">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                        </div>

                        <div class="alert alert-secondary py-2 px-3 border-0 shadow-sm d-flex align-items-center">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <small>Algorithm: <strong>{{ session('result')['algorithm'] ?? 'RSA-SHA256' }}</strong></small>
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
        btn.classList.replace('btn-outline-danger', 'btn-success');
        btn.innerHTML = '<i class="bi bi-check2"></i> Copied';
        setTimeout(() => {
            btn.classList.replace('btn-success', 'btn-outline-danger');
            btn.innerHTML = originalHtml;
        }, 2000);
    } catch (err) { console.error(err); }
}
</script>
@endsection