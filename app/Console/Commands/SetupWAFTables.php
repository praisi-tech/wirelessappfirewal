<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupWAFTables extends Command
{
    protected $signature = 'waf:setup-tables';
    protected $description = 'Create WAF database tables';

    public function handle()
    {
        $this->info('Setting up WAF tables...');

        // Create waf_logs table
        if (!Schema::hasTable('waf_logs')) {
            DB::statement("
                CREATE TABLE waf_logs (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent TEXT NULL,
                    url TEXT NOT NULL,
                    method VARCHAR(10) NOT NULL,
                    request_data JSON NULL,
                    threat_type VARCHAR(50) NULL,
                    description TEXT NOT NULL,
                    severity TINYINT DEFAULT 1,
                    blocked BOOLEAN DEFAULT false,
                    user_id BIGINT UNSIGNED NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_ip_created (ip_address, created_at),
                    INDEX idx_threat_type (threat_type),
                    INDEX idx_severity (severity),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->info('✓ Created waf_logs table');
        } else {
            $this->info('✓ waf_logs table already exists');
        }

        // Create blocked_ips table
        if (!Schema::hasTable('blocked_ips')) {
            DB::statement("
                CREATE TABLE blocked_ips (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) UNIQUE NOT NULL,
                    reason VARCHAR(255) NOT NULL,
                    blocked_until TIMESTAMP NULL,
                    attempts INT DEFAULT 1,
                    blocked_by BIGINT UNSIGNED NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_ip_blocked (ip_address, blocked_until),
                    FOREIGN KEY (blocked_by) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->info('✓ Created blocked_ips table');
        } else {
            $this->info('✓ blocked_ips table already exists');
        }

        // Create tokens table
        if (!Schema::hasTable('tokens')) {
            DB::statement("
                CREATE TABLE tokens (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    token VARCHAR(64) UNIQUE NOT NULL,
                    user_id BIGINT UNSIGNED NOT NULL,
                    device_info VARCHAR(255) NULL,
                    ip_address VARCHAR(45) NULL,
                    expires_at TIMESTAMP NOT NULL,
                    last_used_at TIMESTAMP NULL,
                    is_active BOOLEAN DEFAULT true,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_token_active (token, expires_at, is_active),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->info('✓ Created tokens table');
        } else {
            $this->info('✓ tokens table already exists');
        }

        $this->info('WAF tables setup complete!');
        return Command::SUCCESS;
    }
}