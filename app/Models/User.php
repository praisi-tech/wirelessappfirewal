<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

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
        'password' => 'hashed', // Laravel akan otomatis meng-hash teks polos
        'secret_key' => 'encrypted', // Akan di-encrypt saat disimpan dan di-decrypt saat dibaca
    ];

    /**
     * Relasi dan Logika Bisnis
     */
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

    /**
     * MODIFIKASI: Method khusus untuk mengisi data API key ke model 
     * SEBELUM record pertama kali dibuat (untuk registrasi).
     */
    public function generateKeysForNewUser(): void
    {
        $this->api_key = 'apk_' . Str::random(32);
        $this->secret_key = 'sk_' . Str::random(64);
    }

    /**
     * Method eksisiting untuk regenerasi key dan langsung simpan.
     */
    public function generateApiKey(): array
    {
        $newApiKey = 'apk_' . Str::random(32);
        $newSecretKey = 'sk_' . Str::random(64);

        $this->forceFill([
            'api_key' => $newApiKey,
            'secret_key' => $newSecretKey,
        ])->save();
        
        return [
            'api_key' => $newApiKey,
            'secret_key' => $newSecretKey
        ];
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function incrementLoginAttempts(): void
    {
        $this->increment('login_attempts');
        
        $maxAttempts = config('waf.max_login_attempts', 5);
        $blockDuration = config('waf.block_duration', 900);

        if ($this->login_attempts >= $maxAttempts) {
            $this->locked_until = now()->addSeconds($blockDuration);
            $this->save();
        }
    }

    public function resetLoginAttempts(): void
    {
        $this->update([
            'login_attempts' => 0,
            'locked_until' => null,
        ]);
    }
}