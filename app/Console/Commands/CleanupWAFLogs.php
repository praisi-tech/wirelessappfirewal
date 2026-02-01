<?php

namespace App\Console\Commands;

use App\WAF\Logger\WAFLogger;
use Illuminate\Console\Command;

class CleanupWAFLogs extends Command
{
    protected $signature = 'waf:cleanup {--days=30 : Number of days to keep logs}';
    protected $description = 'Cleanup old WAF logs';

    public function handle(WAFLogger $logger): void
    {
        $days = $this->option('days');
        $deleted = $logger->cleanupOldLogs();
        
        $this->info("Deleted {$deleted} WAF logs older than {$days} days.");
    }
}