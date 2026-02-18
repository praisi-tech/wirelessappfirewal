<?php

namespace App\Console\Commands;

use App\Models\Nonce;
use Illuminate\Console\Command;

class CleanupExpiredNonces extends Command
{
    protected $signature = 'nonce:cleanup';
    protected $description = 'Delete expired nonces from database';

    public function handle()
    {
        $deleted = Nonce::where('expires_at', '<', now())->delete();
        
        $this->info("✓ Deleted {$deleted} expired nonces");
        
        return 0;
    }
}
