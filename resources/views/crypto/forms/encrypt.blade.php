@extends('layouts.app')

@section('title', 'Encrypt Data')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0"><i class="bi bi-shield-lock-fill me-2"></i>Encrypt Data</h5>
                    <a href="{{ route('crypto.dashboard') }}" class="btn btn-sm btn-light shadow-sm">
                        <i class="bi bi-arrow-left"></i> Back to Tools
                    </a>
                </div>
                <div class="card-body p-4">
                    
                    {{-- Status Messages --}}
                    @if(session('error'))
                        <div class="alert alert-danger border-0 shadow-sm mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('crypto.encrypt') }}" method="POST" id="encryptForm">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="data" class="form-label fw-bold text-dark">Plaintext Data</label>
                            <textarea class="form-control @error('data') is-invalid @enderror" 
                                      id="data" name="data" rows="4" 
                                      placeholder="Enter the sensitive information you wish to secure..." 
                                      required>{{ old('data') }}</textarea>
                            @error('data') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text mt-2">Data is encrypted using AES-256-GCM by default.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="key" class="form-label fw-bold text-dark">Custom Encryption Key (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-key"></i></span>
                                <input type="password" class="form-control" id="key" name="key" 
                                       placeholder="Leave blank for system master key">
                            </div>
                            <div class="form-text">Must be a 32-character string or Base64 encoded key if provided.</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                <i class="bi bi-cpu-fill me-2"></i>Generate Ciphertext
                            </button>
                            <button type="reset" class="btn btn-link btn-sm text-muted text-decoration-none">Clear Form</button>
                        </div>
                    </form>
                    
                    {{-- Encryption Result Section --}}
                    @if(session('result'))
                    <div class="mt-5 pt-4 border-top animate__animated animate__fadeIn">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success-subtle p-2 rounded-circle me-3">
                                <i class="bi bi-check-circle-fill text-success fs-4"></i>
                            </div>
                            <h5 class="text-success mb-0">Encryption Successful!</h5>
                        </div>
                        
                        <p class="text-muted small mb-4">
                            Ensure you store the <strong>IV</strong> and <strong>Tag</strong> securely. Both are required alongside the ciphertext for decryption.
                        </p>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Ciphertext</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light font-monospace" id="res_cipher" value="{{ session('result')['ciphertext'] }}" readonly>
                                <button class="btn btn-outline-primary" type="button" onclick="copyValue('res_cipher', this)">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">IV (Initialization Vector)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light font-monospace" id="res_iv" value="{{ session('result')['iv'] }}" readonly>
                                    <button class="btn btn-outline-primary" type="button" onclick="copyValue('res_iv', this)">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                            
                            @if(isset(session('result')['tag']))
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Auth Tag (GCM)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light font-monospace" id="res_tag" value="{{ session('result')['tag'] }}" readonly>
                                    <button class="btn btn-outline-primary" type="button" onclick="copyValue('res_tag', this)">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="alert alert-info py-2 px-3 mt-2 border-0 shadow-sm d-flex align-items-center">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <small>Algorithm: <strong>{{ session('result')['algorithm'] }}</strong> | Key Type: <strong>{{ session('result')['key_type'] === 'system' ? '🔐 System Master Key' : '🔑 Custom Key' }}</strong></small>
                        </div>
                        
                        <div class="alert alert-warning py-2 px-3 mt-3 border-0 shadow-sm">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                            <strong>⚠️  Important for Decryption:</strong><br>
                            <small>When decrypting, use the <strong>same key type</strong> as shown above.
                            @if(session('result')['key_type'] === 'system')
                                Leave the key field blank to use the system master key.
                            @else
                                Provide the exact custom key you used, encoded as Base64.
                            @endif
                            </small>
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Copies text to clipboard and provides visual feedback on the button
 */
async function copyValue(id, btn) {
    const input = document.getElementById(id);
    const originalHtml = btn.innerHTML;
    
    try {
        await navigator.clipboard.writeText(input.value);
        
        // Visual Feedback
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');
        btn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
        
        setTimeout(() => {
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-primary');
            btn.innerHTML = originalHtml;
        }, 2000);
        
    } catch (err) {
        console.error('Failed to copy: ', err);
        alert("Manual copy required: " + input.value);
    }
}
</script>

<style>
    /* Optional animations for the result section */
    .animate__animated { animation-duration: 0.5s; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate__fadeIn { animation-name: fadeIn; }
</style>
@endsection