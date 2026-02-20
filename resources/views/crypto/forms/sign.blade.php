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
                                      placeholder="-----BEGIN PRIVATE KEY-----" required>{{ old('private_key') }}</textarea>
                            <div class="form-text mt-2 text-danger small">
                                <i class="bi bi-shield-exclamation"></i> Never share your private key with anyone.
                            </div>
                        </div>

                        <div class="alert alert-info py-2 px-3 mb-4 small">
                            <strong>📋 Need a test key?</strong> Click <a href="javascript:void(0)" onclick="loadTestKey()" class="alert-link">Load Sample RSA Key</a> to test the feature.
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
// To generate your own private key, run in terminal/PowerShell:
// openssl genrsa -out private_key.pem 2048
// Then copy the entire content between BEGIN and END markers

function loadTestKey() {
    const validRSAKey = `-----BEGIN RSA PRIVATE KEY-----
MIIEogIBAAKCAQEArRAURY7TQs349molo9JsFoMIEkAS7O+dYREqEkeHy3I1oMCx
R+TNbUe4ZiqELK43XozbTwTyLGhY6lhaP0sLgIlFSTWAoiK9xcoa08JhobHP8A3g
hu7BtYXAkWkZp9tcxW4OwuSqge+FVNS4yfVQJgOKhrtITCkFIA/fjUvYhtXbXUC1
f1MgRQDICz8adPt6sWyfCPyVdxewXl+tuyjk4FS4kP2Y9wiE4obuQpTR5h61JuxM
e0ThYkCp1cvjU7G9Jbv0zj1sLBEHM//upGpmYnHaWvh3nLHOah2pixa14WWCMF56
vvbk8oISQNDSrNfEN3V6BuplLSJhlBCxh8zVfQIDAQABAoIBADir/Edu5uWId4+R
4PONk5GnpPv8+XWXXc0Mht9nhyNrKf6TOzqUxWSUyODD6XaMMluG+sYck4VRQlSz
TBJP69tSmFrJ526wu75KWZCgpcICbVpp4O2uv8ZGn3G+ngUmw3Fvgev2X5OfMOU/
p6eS6oUYQcOvDpTKFgCS0hz+ClDUqxwX0OvbRk705xHoT/B8qDo9yabgInFXwj9J
b1d6LdD5xh+vjgd9KnWKGVfGIIy807GOWDUnrXjsKXMJXR1Sk1WYYTDIirIRinSN
Kx41kVr1Umz5bdn6jw9BW50+NcF4rByljYoObqNCiMDnUpTpugD9bttnflUvW34L
4qP+22ECgYEA3+DEPhQiZbFjL4RRUeEqicUQ6W5y89EIBAX3ntlejKv6DDU8mwwM
lQL/w1jXTxpaipbRcikkqYu6+NxKFVQ2mZ4D06uK9R4Y5EdH2FwDsXbDqeHQ2lMK
8qjnu3NJiKn3W1NGZ6CwF2fiGfY22KlYIP/ojZkl5EWeLsKXGqRJr2UCgYEAxeTT
oPI1P29NaU3PYRlcoOtVCuWzzqZPL6GRhsP/mjEXNC8e7uAaV/pKBeBPofF0ycH0
QS/tibJoILUlebPDCWFui1ufqOu7OQDiCDDMV/2Np8qqKco8w83l0av+JMNP4cf/
DuNls5/R6jLQnaXIoTdj5BSoGsmOL8WKtfC5KDkCgYBU8ZFEBJuwfgemJcw9BvP+
5fiuuSuAexVRgXocmVkYgsATFbfzFDVJ7KNiWTkIWHV2FBdP+3BfrBa7CcpKIXO+
AYhuLa5BprPHGYyW+QKluOwwbu+xhsSmoZObOfjyk5q42fXip8Nofg/5zcOtnUNE
8hQoda082XVVRJvUjfUvSQKBgF022hCXxkkpDlep1SMxpRYPg7FsXXPblrUi77+B
Fyb0NK/Z+kIewYnrVW7LJ/dqGs7mUz1ZbBROOwodCZf3+siyYjW2ZNBWqYvCRLYC
9l4ECvOcXObuYg0BMV3AHCgI79m0MQo8Rq2DfIdKhEWdz5FQ2/aVXFGx2w3ZiOsG
htDRAoGAOAow4clg0IWUwgJb1pnP6QVOPOtUemMBrudg7rAo28MB/kMB16Rw/fr9
F7JSQ2ucLx55Hgbq+jrgluFHUZLLKpSCW0mmsu/nVpc0Dc03aM9L30cqK9fOmG2X
+mgbwhl8atQwKhn/s/BIVb0ps0CTK4rRLqSzJLEMXkheIj+hX+4=
-----END RSA PRIVATE KEY-----`;
    
    document.getElementById('private_key').value = validRSAKey;
    if (!document.getElementById('data').value) {
        document.getElementById('data').value = 'Test message for digital signature';
    }
}

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