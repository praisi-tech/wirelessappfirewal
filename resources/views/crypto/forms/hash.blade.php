@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mt-5 shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Password Hashing (Argon2id)</h5>
                </div>
                <div class="card-body">
                    
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('crypto.hash-password') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">Enter Password to Hash</label>
                            <input type="text" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required placeholder="Minimum 8 characters">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Generate Secure Hash</button>
                        </div>
                    </form>

                    @if(session('result'))
                        <hr class="my-4">
                        <div class="bg-light p-3 border rounded">
                            <h6 class="text-primary">Hashing Results:</h6>
                            <div class="mb-2">
                                <label class="small fw-bold">Hash Result:</label>
                                <textarea class="form-control bg-white" rows="2" readonly>{{ session('result')['hash'] }}</textarea>
                            </div>
                            <small class="text-muted">Algorithm: {{ session('result')['algorithm'] }}</small>
                        </div>
                    @endif
                </div>
            </div>
            <div class="text-center mt-3">
                <a href="{{ route('crypto.dashboard') }}" class="text-decoration-none">← Back to Tools</a>
            </div>
        </div>
    </div>
</div>
@endsection