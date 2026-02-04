@extends('layouts.app')

@section('title', 'Encrypt Data')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Encrypt Data</h4>
                    <a href="{{ route('crypto.dashboard') }}" class="btn btn-sm btn-outline-secondary float-end">
                        ← Back to Dashboard
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('crypto.encrypt') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="data" class="form-label">Data to Encrypt</label>
                            <textarea class="form-control" id="data" name="data" rows="6" 
                                      placeholder="Enter the text you want to encrypt..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="key" class="form-label">Encryption Key (Optional)</label>
                            <input type="text" class="form-control" id="key" name="key" 
                                   placeholder="Leave empty to use default key">
                            <div class="form-text">If provided, must be 32 characters for AES-256</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-shield-lock"></i> Encrypt Data
                            </button>
                        </div>
                    </form>
                    
                    @if(session('encrypted_data'))
                    <div class="mt-4">
                        <h5>Encryption Result:</h5>
                        <div class="alert alert-success">
                            <h6>Encrypted Data:</h6>
                            <pre class="mb-0"><code>{{ json_encode(session('encrypted_data'), JSON_PRETTY_PRINT) }}</code></pre>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection