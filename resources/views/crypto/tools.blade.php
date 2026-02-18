@extends('layouts.app')

@section('title', 'Cryptographic Toolkit')

@section('content')
<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Cryptographic Toolkit</h1>
        <span class="badge bg-secondary">System Standard: AES-256-GCM / Argon2id</span>
    </div>

    <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="text-center">
                        <i class="bi bi-lock-fill fs-1 text-primary mb-3"></i>
                        <h5 class="fw-bold">Encryption</h5>
                        <p class="text-muted small">Encrypt sensitive data using the AES-256-GCM authenticated algorithm.</p>
                        <div class="d-grid">
                            <a href="{{ route('crypto.encrypt.form') }}" class="btn btn-primary">Open Tool</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="text-center">
                        <i class="bi bi-unlock-fill fs-1 text-success mb-3"></i>
                        <h5 class="fw-bold">Decryption</h5>
                        <p class="text-muted small">Revert encrypted messages back to plain text using the correct keys and IV.</p>
                        <div class="d-grid">
                            <a href="{{ route('crypto.decrypt.form') }}" class="btn btn-success">Open Tool</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="text-center">
                        <i class="bi bi-shield-lock fs-1 text-info mb-3"></i>
                        <h5 class="fw-bold">Password Hashing</h5>
                        <p class="text-muted small">Generate secure, non-reversible hashes using high-entropy Argon2id.</p>
                        <div class="d-grid">
                            <a href="{{ route('crypto.hash.form') }}" class="btn btn-info text-white">Open Tool</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="text-center">
                        <i class="bi bi-patch-check fs-1 text-warning mb-3"></i>
                        <h5 class="fw-bold">HMAC Authentication</h5>
                        <p class="text-muted small">Create or verify Hash-based Message Authentication Codes for data integrity.</p>
                        <div class="d-grid">
                            <a href="{{ route('crypto.hmac.form') }}" class="btn btn-warning text-white">Open Tool</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow border-start border-danger border-4 h-100">
                <div class="card-body">
                    <div class="text-center">
                        <i class="bi bi-pen fs-1 text-danger mb-3"></i>
                        <h5 class="fw-bold">Digital Signatures</h5>
                        <p class="text-muted small">Generate RSA/ECDSA signatures to prove message authenticity and origin.</p>
                        <div class="d-grid">
                            <a href="{{ route('crypto.sign.form') }}" class="btn btn-danger">Open Tool</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow border-start border-secondary border-4 h-100 bg-light">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="text-center">
                        <i class="bi bi-code-slash fs-1 text-secondary mb-3"></i>
                        <h5 class="fw-bold">API Documentation</h5>
                        <p class="text-muted small">Learn how to integrate these crypto tools into your external applications.</p>
                        <div class="d-grid border-top pt-2">
                             <span class="text-uppercase small fw-bold text-secondary">Endpoint Prefix: /api/v1</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection