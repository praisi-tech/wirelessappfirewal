@extends('layouts.app')

@section('title', 'Crypto Tools')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Cryptographic Toolkit</h1>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow border-left-primary h-100">
                <div class="card-body">
                    <div class="text-center">
                        <i class="bi bi-lock-fill fa-3x text-primary mb-3"></i>
                        <h5 class="font-weight-bold">Encryption</h5>
                        <p class="text-muted">Encrypt sensitive data using the AES-256-GCM authenticated algorithm.</p>
                        <a href="{{ route('crypto.encrypt.form') }}" class="btn btn-primary btn-block">Open Tool</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow border-left-success h-100">
                <div class="card-body">
                    <div class="text-center">
                        <i class="bi bi-unlock-fill fa-3x text-success mb-3"></i>
                        <h5 class="font-weight-bold">Decryption</h5>
                        <p class="text-muted">Revert encrypted messages back to plain text using the correct keys.</p>
                        <a href="{{ route('crypto.decrypt.form') }}" class="btn btn-success btn-block">Open Tool</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow border-left-info h-100">
                <div class="card-body">
                    <div class="text-center">
                        <i class="bi bi-hash fa-3x text-info mb-3"></i>
                        <h5 class="font-weight-bold">Password Hashing</h5>
                        <p class="text-muted">Generate secure, non-reversible hashes using Argon2id or Bcrypt.</p>
                        <a href="{{ route('crypto.hash.form') }}" class="btn btn-info btn-block text-white">Open Tool</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection