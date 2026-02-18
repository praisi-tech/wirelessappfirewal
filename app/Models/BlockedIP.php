<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedIP extends Model
{
    use HasFactory;

    protected $table = 'blocked_ips';

    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_until',
        'attempts',
        'blocked_by',
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
        'attempts' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function blockedBy()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function isBlocked(): bool
    {
        if (!$this->blocked_until) {
            return true; // Permanent block
        }
        
        return $this->blocked_until->isFuture();
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
        $this->save();
    }
}