@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Login to CryptoWAF</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary" id="loginBtn">
                            <span class="spinner-border spinner-border-sm d-none" id="loginSpinner"></span>
                            Login
                        </button>
                        <a href="{{ route('register') }}" class="btn btn-link">Don't have an account? Register</a>
                    </div>
                </form>

                <div class="mt-4">
                    <h6>Security Information:</h6>
                    <div class="alert alert-info small">
                        <i class="bi bi-shield-check"></i> 
                        Your login is protected by:
                        <ul class="mb-0 mt-2">
                            <li>Brute force detection and IP blocking</li>
                            <li>Password hashing with Argon2id</li>
                            <li>Secure session management</li>
                            <li>Real-time threat monitoring</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h6>API Authentication</h6>
                <p class="small text-muted">
                    For API access, you'll need to generate API keys from your dashboard.
                    All API requests require cryptographic signatures for enhanced security.
                </p>
                <a href="/docs/api" class="btn btn-sm btn-outline-secondary">View API Documentation</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    this.querySelector('i').className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
});

document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    const spinner = document.getElementById('loginSpinner');
    btn.disabled = true;
    spinner.classList.remove('d-none');
    btn.innerHTML = 'Logging in...';
});
</script>
@endpush