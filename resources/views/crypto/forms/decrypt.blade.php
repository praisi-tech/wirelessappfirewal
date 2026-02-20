@extends('layouts.app')

@section('title', 'Decrypt Message')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0"><i class="bi bi-unlock-fill me-2"></i>Decrypt Message</h5>
                    <a href="{{ route('crypto.dashboard') }}" class="btn btn-sm btn-light shadow-sm">
                        <i class="bi bi-arrow-left"></i> Back to Tools
                    </a>
                </div>
                <div class="card-body p-4">
                    
                    {{-- Alert for Errors --}}
                    @if(session('error'))
                        <div class="alert alert-danger border-0 shadow-sm mb-4">
                            <i class="bi bi-exclamation-octagon-fill me-2"></i>{{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('crypto.decrypt') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="ciphertext" class="form-label fw-bold text-dark">Ciphertext (Encrypted Data)</label>
                            <textarea name="ciphertext" id="ciphertext" 
                                      class="form-control @error('ciphertext') is-invalid @enderror font-monospace" 
                                      rows="3" required placeholder="Paste the encrypted string here...">{{ old('ciphertext') }}</textarea>
                            @error('ciphertext') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="iv" class="form-label fw-bold text-dark">Initialization Vector (IV)</label>
                                <input type="text" name="iv" id="iv" 
                                       class="form-control @error('iv') is-invalid @enderror font-monospace" 
                                       required value="{{ old('iv') }}" placeholder="Base64 IV from encryption">
                                @error('iv') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="tag" class="form-label fw-bold text-dark">Authentication Tag</label>
                                <input type="text" name="tag" id="tag" 
                                       class="form-control font-monospace" 
                                       value="{{ old('tag') }}" placeholder="Required for GCM mode">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="key" class="form-label fw-bold text-dark">Decryption Key (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-key-fill"></i></span>
                                <input type="password" name="key" id="key" class="form-control" 
                                       placeholder="Leave blank to use system master key">
                            </div>
                            <div class="form-text mt-2">⚠️ <strong>Critical:</strong> Must match the key used during encryption (exactly!).</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg shadow-sm">
                                <i class="bi bi-cpu-fill me-2"></i>Decrypt Now
                            </button>
                            <button type="reset" class="btn btn-link btn-sm text-muted text-decoration-none">Clear Fields</button>
                        </div>
                    </form>

                    {{-- Result Section --}}
                    @if(session('result'))
                        <div class="mt-5 pt-4 border-top animate__animated animate__fadeIn">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success-subtle p-2 rounded-circle me-3">
                                    <i class="bi bi-shield-check text-success fs-4"></i>
                                </div>
                                <h5 class="text-success mb-0">Decryption Successful!</h5>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Plaintext Result</label>
                                <div class="input-group">
                                    <textarea class="form-control bg-white" id="res_decrypted" rows="4" readonly>{{ session('result')['decrypted'] }}</textarea>
                                    <button class="btn btn-outline-success" type="button" onclick="copyValue('res_decrypted', this)">
                                        <i class="bi bi-clipboard"></i> Copy
                                    </button>
                                </div>
                            </div>

                            <div class="alert alert-info py-2 px-3 border-0 shadow-sm d-flex align-items-center">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <small>Verified via Algorithm: <strong>{{ session('result')['algorithm'] }}</strong></small>
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
 * Auto-trim whitespace from form inputs to prevent copy-paste errors
 */
document.addEventListener('DOMContentLoaded', function() {
    const fieldsToTrim = ['ciphertext', 'iv', 'tag', 'key'];
    
    fieldsToTrim.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('blur', function() {
                this.value = this.value.trim();
            });
        }
    });
});

/**
 * Copies text to clipboard and provides visual feedback
 */
async function copyValue(id, btn) {
    const input = document.getElementById(id);
    const originalHtml = btn.innerHTML;
    
    try {
        await navigator.clipboard.writeText(input.value);
        
        btn.classList.remove('btn-outline-success');
        btn.classList.add('btn-success', 'text-white');
        btn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
        
        setTimeout(() => {
            btn.classList.remove('btn-success', 'text-white');
            btn.classList.add('btn-outline-success');
            btn.innerHTML = originalHtml;
        }, 2000);
        
    } catch (err) {
        console.error('Failed to copy: ', err);
    }
}
</script>

<style>
    .animate__animated { animation-duration: 0.5s; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate__fadeIn { animation-name: fadeIn; }
</style>
@endsection