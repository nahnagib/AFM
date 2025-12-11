<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AfmSessionToken extends Model
{
    protected $fillable = [
        'request_id',
        'nonce',
        'payload_hash',
        'sis_student_id',
        'student_name',
        'courses_json',
        'role',
        'issued_at',
        'expires_at',
        'consumed_at',
        'client_ip',
        'user_agent',
    ];

    protected $casts = [
        'courses_json' => 'array',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isConsumed();
    }

    public function markAsConsumed(): void
    {
        $this->consumed_at = now();
        $this->save();
    }
}
