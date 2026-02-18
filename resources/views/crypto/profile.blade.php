@extends('layouts.app')

@section('title', 'User Profile')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">My Profile</h1>
            <p class="text-muted small mb-0">Manage your account information and settings.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
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
        {{-- Profile Card --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Account Information</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">Full Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3 text-secondary">Change Password (Optional)</h6>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                   id="current_password" name="current_password" placeholder="Required if changing password">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control @error('new_password') is-invalid @enderror" 
                                   id="new_password" name="new_password" placeholder="Min. 8 characters">
                            @error('new_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" 
                                   id="new_password_confirmation" name="new_password_confirmation" placeholder="Confirm password">
                        </div>

                        <button type="submit" class="btn btn-primary shadow-sm">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Info Card --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Account Details</h5>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <small class="text-muted d-block">Member Since</small>
                        <strong>{{ $user->created_at->format('d M Y') }}</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Last Login</small>
                        <strong>
                            @if($user->last_login_at)
                                {{ $user->last_login_at->diffForHumans() }}
                            @else
                                Never logged in
                            @endif
                        </strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Account Status</small>
                        <span class="badge bg-success">Active</span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Account Type</small>
                        <strong>{{ $user->is_admin ? 'Administrator' : 'Standard User' }}</strong>
                    </div>
                </div>
            </div>

            {{-- API Keys Link --}}
            <div class="card shadow-sm border-0 border-left-primary">
                <div class="card-body p-3">
                    <h6 class="mb-2"><i class="bi bi-key"></i> API Credentials</h6>
                    <p class="text-muted small mb-3">Manage your API keys and secret credentials for secure API access.</p>
                    <a href="{{ route('api-keys') }}" class="btn btn-outline-primary btn-sm w-100">
                        View API Keys
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .border-left-primary {
        border-left: 4px solid #4e73df !important;
    }
</style>
@endsection
