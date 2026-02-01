<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'user_id',
        'device_info',
        'ip_address',
        'expires_at',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return $this->is_active && $this->expires_at->isFuture();
    }

    public function markAsUsed(): void
    {
        $this->last_used_at = now();
        $this->save();
    }

    public function revoke(): void
    {
        $this->is_active = false;
        $this->save();
    }
}