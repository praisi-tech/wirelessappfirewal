<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WAFLog extends Model
{
    use HasFactory;

    protected $table = 'waf_logs';

    protected $fillable = [
        'ip_address',
        'user_agent',
        'url',
        'method',
        'request_data',
        'threat_type',
        'description',
        'severity',
        'blocked',
        'user_id',
    ];

    protected $casts = [
        'request_data' => 'array',
        'blocked' => 'boolean',
        'severity' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeHighSeverity($query)
    {
        return $query->where('severity', '>=', 3);
    }

    public function scopeBlocked($query)
    {
        return $query->where('blocked', true);
    }

    public function getSeverityLabelAttribute(): string
    {
        return match($this->severity) {
            1 => 'Low',
            2 => 'Medium',
            3 => 'High',
            4 => 'Critical',
            default => 'Unknown',
        };
    }
}