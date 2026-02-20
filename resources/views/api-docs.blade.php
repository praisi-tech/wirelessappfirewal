<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoWAF - API Documentation</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --bg-dark: #0b1120;
            --bg-card: #161f32;
            --accent-teal: #00bcd4;
            --glow-color: rgba(0, 188, 212, 0.5);
            --text-muted: #94a3b8;
        }

        body {
            background-color: var(--bg-dark);
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            padding: 20px;
        }

        .navbar {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--accent-teal);
            margin-bottom: 30px;
        }

        .navbar-brand {
            color: var(--accent-teal) !important;
            font-weight: 700;
            font-size: 1.3rem;
        }

        .page-header {
            margin-bottom: 40px;
        }

        .page-header h1 {
            color: var(--accent-teal);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .api-section {
            background-color: var(--bg-card);
            border: 1px solid rgba(0, 188, 212, 0.3);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .endpoint-card {
            background-color: rgba(0, 188, 212, 0.08);
            border-left: 4px solid var(--accent-teal);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .method-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.85rem;
            margin-right: 10px;
        }

        .method-post {
            background-color: #ff9800;
            color: white;
        }

        .method-get {
            background-color: #4caf50;
            color: white;
        }

        .method-delete {
            background-color: #f44336;
            color: white;
        }

        .endpoint-url {
            background-color: var(--bg-dark);
            padding: 10px 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: var(--accent-teal);
            word-break: break-all;
            margin: 15px 0;
        }

        .code-block {
            background-color: var(--bg-dark);
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            border: 1px solid rgba(0, 188, 212, 0.2);
        }

        .param-table {
            width: 100%;
            margin-top: 15px;
        }

        .param-table th {
            background-color: rgba(0, 188, 212, 0.2);
            color: var(--accent-teal);
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid var(--accent-teal);
        }

        .param-table td {
            padding: 10px;
            border-bottom: 1px solid rgba(0, 188, 212, 0.1);
        }

        .section-title {
            color: var(--accent-teal);
            font-weight: 700;
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent-teal);
        }

        .response-example {
            background-color: var(--bg-dark);
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #4caf50;
        }

        .back-btn {
            color: var(--accent-teal);
            text-decoration: none;
            margin-bottom: 20px;
            display: inline-block;
        }

        .back-btn:hover {
            color: #ffffff;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-lg">
            <a class="navbar-brand" href="/"><i class="bi bi-shield-lock"></i> CryptoWAF</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Back to Home</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-lg">
        <div class="page-header">
            <h1><i class="bi bi-file-earmark-text"></i> API Documentation</h1>
            <p class="text-muted">Complete guide to CryptoWAF API endpoints and usage</p>
        </div>

        <!-- Authentication Section -->
        <div class="api-section">
            <h2 class="section-title">Authentication</h2>
            <p>All API endpoints require authentication headers. Register and login to get your access token.</p>
            
            <div class="endpoint-card">
                <span class="method-badge method-post">POST</span>
                <strong>Register User</strong>
                <div class="endpoint-url">/api/auth/register</div>
                <p><strong>Request Body:</strong></p>
                <div class="code-block">
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepassword123"
}
                </div>
            </div>

            <div class="endpoint-card">
                <span class="method-badge method-post">POST</span>
                <strong>Login</strong>
                <div class="endpoint-url">/api/auth/login</div>
                <p><strong>Request Body:</strong></p>
                <div class="code-block">
{
  "email": "john@example.com",
  "password": "securepassword123"
}
                </div>
            </div>
        </div>

        <!-- Encryption Section -->
        <div class="api-section">
            <h2 class="section-title">Encryption & Decryption</h2>
            
            <div class="endpoint-card">
                <span class="method-badge method-post">POST</span>
                <strong>Encrypt Data</strong>
                <div class="endpoint-url">/api/crypto/encrypt</div>
                <p><strong>Description:</strong> Encrypts data using AES-256-GCM algorithm</p>
                <p><strong>Request Body:</strong></p>
                <div class="code-block">
{
  "data": "Sensitive information to encrypt",
  "key": "your-encryption-key"
}
                </div>
                <p><strong>Response:</strong></p>
                <div class="response-example code-block">
{
  "success": true,
  "ciphertext": "encrypted_data_here",
  "iv": "initialization_vector",
  "tag": "authentication_tag"
}
                </div>
            </div>

            <div class="endpoint-card">
                <span class="method-badge method-post">POST</span>
                <strong>Decrypt Data</strong>
                <div class="endpoint-url">/api/crypto/decrypt</div>
                <p><strong>Description:</strong> Decrypts AES-256-GCM encrypted data</p>
                <p><strong>Request Body:</strong></p>
                <div class="code-block">
{
  "ciphertext": "encrypted_data",
  "iv": "initialization_vector",
  "tag": "authentication_tag",
  "key": "decryption-key"
}
                </div>
            </div>
        </div>

        <!-- HMAC Section -->
        <div class="api-section">
            <h2 class="section-title">HMAC Operations</h2>
            
            <div class="endpoint-card">
                <span class="method-badge method-post">POST</span>
                <strong>Generate HMAC</strong>
                <div class="endpoint-url">/api/crypto/hmac</div>
                <p><strong>Description:</strong> Generate HMAC-SHA256 for data integrity verification</p>
                <p><strong>Request Body:</strong></p>
                <div class="code-block">
{
  "data": "Data to generate HMAC for",
  "key": "secret-key"
}
                </div>
            </div>

            <div class="endpoint-card">
                <span class="method-badge method-post">POST</span>
                <strong>Verify HMAC</strong>
                <div class="endpoint-url">/api/crypto/verify-hmac</div>
                <p><strong>Description:</strong> Verify HMAC signature authenticity</p>
                <p><strong>Request Body:</strong></p>
                <div class="code-block">
{
  "data": "Original data",
  "hmac": "received_hmac_signature",
  "key": "secret-key"
}
                </div>
            </div>
        </div>

        <!-- Digital Signature Section -->
        <div class="api-section">
            <h2 class="section-title">Digital Signatures</h2>
            
            <div class="endpoint-card">
                <span class="method-badge method-post">POST</span>
                <strong>Create Signed Request</strong>
                <div class="endpoint-url">/api/crypto/sign</div>
                <p><strong>Description:</strong> Create digital signature using RSA-2048</p>
                <p><strong>Request Body:</strong></p>
                <div class="code-block">
{
  "data": "Data to sign",
  "private_key": "your_private_key_here"
}
                </div>
            </div>

            <div class="endpoint-card">
                <span class="method-badge method-post">POST</span>
                <strong>Encrypt & Sign</strong>
                <div class="endpoint-url">/api/crypto/encrypt-sign</div>
                <p><strong>Description:</strong> Encrypt data and sign it for maximum security</p>
                <p><strong>Request Body:</strong></p>
                <div class="code-block">
{
  "data": "Data to encrypt and sign",
  "encryption_key": "encryption-key",
  "private_key": "signing-key"
}
                </div>
            </div>
        </div>

        <!-- Admin Section -->
        <div class="api-section">
            <h2 class="section-title">Admin Monitoring (Requires Authentication)</h2>
            
            <div class="endpoint-card">
                <span class="method-badge method-get">GET</span>
                <strong>Get WAF Statistics</strong>
                <div class="endpoint-url">/api/admin/waf/stats</div>
                <p><strong>Description:</strong> Retrieve Web Application Firewall statistics</p>
            </div>

            <div class="endpoint-card">
                <span class="method-badge method-get">GET</span>
                <strong>Get WAF Logs</strong>
                <div class="endpoint-url">/api/admin/waf/logs</div>
                <p><strong>Description:</strong> View detailed WAF activity logs</p>
            </div>

            <div class="endpoint-card">
                <span class="method-badge method-get">GET</span>
                <strong>Get Blocked IPs</strong>
                <div class="endpoint-url">/api/admin/waf/blocked-ips</div>
                <p><strong>Description:</strong> List all currently blocked IP addresses</p>
            </div>

            <div class="endpoint-card">
                <span class="method-badge method-post">POST</span>
                <strong>Block IP Address</strong>
                <div class="endpoint-url">/api/admin/waf/block-ip</div>
                <p><strong>Request Body:</strong></p>
                <div class="code-block">
{
  "ip_address": "192.168.1.100",
  "reason": "Suspicious activity"
}
                </div>
            </div>

            <div class="endpoint-card">
                <span class="method-badge method-delete">DELETE</span>
                <strong>Unblock IP Address</strong>
                <div class="endpoint-url">/api/admin/waf/unblock-ip/{id}</div>
                <p><strong>Description:</strong> Remove an IP from the blocklist</p>
            </div>
        </div>

        <!-- Security Features -->
        <div class="api-section">
            <h2 class="section-title">Security Features</h2>
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="bi bi-shield-check"></i> Web Application Firewall (WAF)</h5>
                    <p class="text-muted">Automatic protection against common attacks including SQL injection, XSS, and protocol manipulation.</p>
                </div>
                <div class="col-md-6">
                    <h5><i class="bi bi-pencil-square"></i> Signature Verification</h5>
                    <p class="text-muted">All requests are verified with cryptographic signatures to ensure authenticity and integrity.</p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <h5><i class="bi bi-stopwatch"></i> Rate Limiting</h5>
                    <p class="text-muted">60 requests per minute per endpoint to prevent abuse and DoS attacks.</p>
                </div>
                <div class="col-md-6">
                    <h5><i class="bi bi-key"></i> Encryption</h5>
                    <p class="text-muted">Military-grade AES-256-GCM encryption for sensitive data at rest and in transit.</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="margin-top: 50px; padding-top: 30px; border-top: 1px solid var(--accent-teal);">
            <p class="text-muted">
                <small>API Version 1.0 | Last Updated: February 2026</small><br>
                <small>For support, contact: support@cryptowaf.local</small>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
