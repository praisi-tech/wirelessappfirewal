<?php

namespace Database\Seeders;

use App\Models\WafLog;
use Illuminate\Database\Seeder;

class WAFLogTestSeeder extends Seeder
{
    /**
     * Seed the database with test WAF logs for testing filtering
     */
    public function run(): void
    {
        // SQL INJECTION LOGS - Critical Severity (4)
        WafLog::create([
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'url' => url('/api/crypto/encrypt?id=1%27%20OR%201%3D1%20--'),
            'method' => 'POST',
            'request_data' => [
                'id' => "1' OR 1=1 --",
                'query' => 'admin',
                'timestamp' => time()
            ],
            'threat_type' => 'sql_injection',
            'description' => 'Detected SQL injection: OR 1=1 pattern in query parameter',
            'severity' => 4,
            'blocked' => true,
            'user_id' => 1,
        ]);

        WafLog::create([
            'ip_address' => '192.168.1.101',
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64)',
                'url' => url('/api/crypto/decrypt?query=UNION%20SELECT%20password%20FROM%20users--'),
            'method' => 'POST',
            'request_data' => [
                'query' => 'UNION SELECT password FROM users--',
            ],
            'threat_type' => 'sql_injection',
            'description' => 'Detected UNION SELECT injection attempt',
            'severity' => 4,
            'blocked' => true,
            'user_id' => 1,
        ]);

        WafLog::create([
            'ip_address' => '192.168.1.102',
            'user_agent' => 'curl/7.64.1',
                'url' => url('/api/crypto/hmac'),
            'method' => 'POST',
            'request_data' => [
                'data' => "'; DROP TABLE users; --",
            ],
            'threat_type' => 'sql_injection',
            'description' => 'Detected DROP TABLE injection pattern',
            'severity' => 4,
            'blocked' => true,
            'user_id' => 1,
        ]);

        // INVALID SIGNATURE LOGS - High Severity (3)
        WafLog::create([
            'ip_address' => '192.168.1.103',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6)',
                'url' => url('/api/crypto/encrypt'),
            'method' => 'POST',
            'request_data' => [
                'data' => 'unauthorized_access_attempt',
                'api_key' => '***REDACTED***'
            ],
            'threat_type' => 'invalid_signature',
            'description' => 'Invalid HMAC signature detected - request could not be verified',
            'severity' => 3,
            'blocked' => false,
            'user_id' => 1,
        ]);

        WafLog::create([
            'ip_address' => '192.168.1.104',
            'user_agent' => 'PostmanRuntime/7.28.0',
                'url' => url('/api/crypto/sign'),
            'method' => 'POST',
            'request_data' => [
                'message' => 'test',
                'signature_header' => 'invalid_signature_xyz'
            ],
            'threat_type' => 'invalid_signature',
            'description' => 'Request signature verification failed',
            'severity' => 3,
            'blocked' => false,
            'user_id' => 1,
        ]);

        // REPLAY ATTACK LOGS - High Severity (3)
        WafLog::create([
            'ip_address' => '192.168.1.105',
            'user_agent' => 'Mozilla/5.0 (Android 11)',
                'url' => url('/api/crypto/decrypt'),
            'method' => 'POST',
            'request_data' => [
                'nonce' => 'old_nonce_123',
                'timestamp' => '2026-02-10T10:00:00Z'
            ],
            'threat_type' => 'replay_attack',
            'description' => 'Duplicate nonce detected - possible replay attack',
            'severity' => 3,
            'blocked' => true,
            'user_id' => 1,
        ]);

        WafLog::create([
            'ip_address' => '192.168.1.106',
            'user_agent' => 'curl/7.75.0',
                'url' => url('/api/crypto/verify-hmac'),
            'method' => 'POST',
            'request_data' => [
                'timestamp' => '2026-01-15T00:00:00Z',  // Old timestamp
                'nonce' => 'recycled_nonce_456'
            ],
            'threat_type' => 'replay_attack',
            'description' => 'Stale timestamp detected - request is too old',
            'severity' => 3,
            'blocked' => true,
            'user_id' => 1,
        ]);

        // XSS ATTACK LOGS - Medium Severity (2)
        WafLog::create([
            'ip_address' => '192.168.1.107',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0)',
                'url' => url('/api/crypto/encrypt'),
            'method' => 'POST',
            'request_data' => [
                'comment' => '<img src=x onerror="alert(\'XSS\')">'
            ],
            'threat_type' => 'xss_attack',
            'description' => 'Detected XSS payload in comment parameter',
            'severity' => 2,
            'blocked' => true,
            'user_id' => 1,
        ]);

        WafLog::create([
            'ip_address' => '192.168.1.108',
            'user_agent' => 'Mozilla/5.0',
                'url' => url('/api/crypto/sign'),
            'method' => 'POST',
            'request_data' => [
                'data' => '<script>alert("XSS")</script>'
            ],
            'threat_type' => 'xss_attack',
            'description' => 'Detected script tag in payload',
            'severity' => 2,
            'blocked' => true,
            'user_id' => 1,
        ]);

        // MANUAL BLOCK LOGS - Variable Severity
        WafLog::create([
            'ip_address' => '192.168.1.109',
            'user_agent' => 'Mozilla/5.0',
                'url' => url('/api/crypto/encrypt'),
            'method' => 'POST',
            'request_data' => [
                'action' => 'attempt_access',
            ],
            'threat_type' => 'manual_block',
            'description' => 'IP address manually blocked by administrator',
            'severity' => 4,
            'blocked' => true,
            'user_id' => 1,
        ]);

        // NORMAL REQUESTS (no threat_type) - Low Severity (1)
        WafLog::create([
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
                'url' => url('/api/crypto/encrypt'),
            'method' => 'POST',
            'request_data' => [
                'plaintext' => 'Hello World',
                'key' => '***REDACTED***'
            ],
            'threat_type' => null,
            'description' => 'Normal request processed successfully',
            'severity' => 1,
            'blocked' => false,
            'user_id' => 1,
        ]);

        WafLog::create([
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
                'url' => url('/api/crypto/decrypt'),
            'method' => 'POST',
            'request_data' => [
                'ciphertext' => '***REDACTED***',
                'key' => '***REDACTED***'
            ],
            'threat_type' => null,
            'description' => 'Normal request processed successfully',
            'severity' => 1,
            'blocked' => false,
            'user_id' => 1,
        ]);

        $this->command->info('✅ WAF Test Logs Seeded Successfully!');
        $this->command->info('Logs created: 12 test entries');
        $this->command->info('  - SQL Injection: 3');
        $this->command->info('  - Invalid Signature: 2');
        $this->command->info('  - Replay Attack: 2');
        $this->command->info('  - XSS Attack: 2');
        $this->command->info('  - Manual Block: 1');
        $this->command->info('  - Normal Requests: 2');
        $this->command->line('');
        $this->command->info('Now test the logs filter at: /dashboard/logs');
    }
}
