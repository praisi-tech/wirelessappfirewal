<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'api_key',
        'secret_key',
        'is_admin',
        'last_login_at',
        'login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'secret_key',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'login_attempts' => 'integer',
    ];

    public function wafLogs()
    {
        return $this->hasMany(WAFLog::class);
    }

    public function tokens()
    {
        return $this->hasMany(Token::class);
    }

    public function blockedIPs()
    {
        return $this->hasMany(BlockedIP::class, 'blocked_by');
    }

    public function generateApiKey(): string
    {
        $this->api_key = bin2hex(random_bytes(32));
        $this->secret_key = bin2hex(random_bytes(64));
        $this->save();
        
        return $this->api_key;
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function incrementLoginAttempts(): void
    {
        $this->increment('login_attempts');
        
        if ($this->login_attempts >= config('waf.brute_force.max_attempts')) {
            $this->locked_until = now()->addSeconds(config('waf.brute_force.block_duration'));
            $this->save();
        }
    }

    public function resetLoginAttempts(): void
    {
        $this->login_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }
}