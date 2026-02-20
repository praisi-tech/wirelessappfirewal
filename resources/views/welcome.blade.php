<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoWAF - Secure Gateway</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300bcd4'><path d='M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z'/><path d='M10 17h4v-5h-4z'/><path d='M12 5c-1.66 0-3 1.34-3 3v3h6V8c0-1.66-1.34-3-3-3z'/></svg>">
    <style>
        :root {
            --bg-dark: #0b1120;        /* Warna navy gelap sesuai screenshot */
            --bg-card: #161f32;        /* Card sedikit lebih terang */
            --accent-teal: #00bcd4;    /* Cyan terang */
            --glow-color: rgba(0, 188, 212, 0.5);
            --text-muted: #94a3b8;
        }

        body {
            background-color: var(--bg-dark);
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        /* Logo Styling */
        .logo-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            color: var(--accent-teal);
            font-size: 3rem;
            filter: drop-shadow(0 0 10px var(--glow-color));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-name {
            font-weight: 700;
            font-size: 2.5rem;
            letter-spacing: -1px;
            margin: 0;
        }

        .brand-name span {
            color: var(--accent-teal);
            text-shadow: 0 0 15px var(--glow-color);
        }

        /* Card & Effects */
        .card-custom {
            background-color: var(--bg-card);
            border: 1px solid rgba(0, 188, 212, 0.15);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            position: relative;
        }

        /* Glow Effect on Button */
        .btn-primary-custom {
            background-color: var(--accent-teal);
            border: none;
            color: #000;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 0 0px var(--glow-color);
        }

        .btn-primary-custom:hover {
            background-color: #26c6da;
            transform: translateY(-2px);
            box-shadow: 0 0 20px var(--glow-color);
            color: #000;
        }

        .btn-outline-custom {
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #cbd5e1;
            background: transparent;
            transition: 0.3s;
        }

        .btn-outline-custom:hover {
            border-color: var(--accent-teal);
            color: var(--accent-teal);
            background: rgba(0, 188, 212, 0.05);
        }

        /* Feature List */
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 25px 0;
            text-align: left;
        }

        .feature-list li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.95rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
        }

        .feature-list li::before {
            content: "→";
            color: var(--accent-teal);
            margin-right: 12px;
            font-weight: bold;
            filter: drop-shadow(0 0 3px var(--accent-teal));
        }

        .text-teal {
            color: var(--accent-teal);
            font-weight: 600;
        }

        /* Subtle Background Decoration */
        .bg-glow {
            position: absolute;
            width: 300px;
            height: 300px;
            background: var(--accent-teal);
            filter: blur(150px);
            opacity: 0.1;
            z-index: -1;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
    </style>
</head>
<body>

    <div class="bg-glow"></div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 text-center">
                
                <div class="logo-wrapper">
                    <i class="bi bi-shield-lock-fill logo-icon"></i>
                    <h1 class="brand-name">Crypto<span>WAF</span></h1>
                </div>

                <p class="text-muted mb-5" style="letter-spacing: 0.5px;">
                    Next-Gen Cryptography Web Application Firewall
                </p>
                
                <div class="card-custom">
                    <h5 class="mb-4 fw-bold text-uppercase" style="letter-spacing: 2px; font-size: 0.9rem; color: var(--accent-teal);">
                        System Status: Protected
                    </h5>
                    
                    <ul class="feature-list">
                        <li>SQL Injection Detection</li>
                        <li>XSS Attack Protection</li>
                        <li>AES-256-GCM Encryption</li>
                        <li>Real-time Monitoring</li>
                    </ul>

                    <div class="d-grid gap-3 mt-4">
                        <a href="/login" class="btn btn-primary-custom btn-lg shadow-sm">Enter Dashboard</a>
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="/register" class="btn btn-outline-custom w-100 py-2">Sign Up</a>
                            </div>
                            <div class="col-6">
                                <a href="/api-docs" class="btn btn-outline-custom w-100 py-2">API Docs</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="mt-4 small text-muted">
                    Secure connection established via <span class="text-teal">SSL/TLS</span>
                </p>
            </div>
        </div>
    </div>

</body>
</html>