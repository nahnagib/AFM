<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationOutbox extends Model
{
    protected $table = 'notification_outbox';

    protected $fillable = [
        'channel',
        'recipient',
        'subject',
        'body',
        'status',
        'send_after',
        'attempts',
        'last_error',
    ];

    protected $casts = [
        'send_after' => 'datetime',
        'attempts' => 'integer',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('send_after', '<=', now());
    }

    public function markAsSent(): void
    {
        $this->status = 'sent';
        $this->save();
    }

    public function markAsFailed(string $error): void
    {
        $this->status = 'failed';
        $this->last_error = $error;
        $this->attempts++;
        $this->save();
    }
}
