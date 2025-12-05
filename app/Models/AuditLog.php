<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
    ];

    const UPDATED_AT = null;

    // Scopes
    public function scopeByActor($query, $actorId)
    {
        return $query->where('actor_id', $actorId);
    }

    public function scopeByType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Methods
    public static function logEvent($type, $action, $metadata = [], $actorId = null, $actorRole = null, $targetType = null, $targetId = null)
    {
        // Try to infer actor from session if not provided
        if (!$actorId && session('afm_token_id')) {
            // This assumes we can get info from session or token service
            // For now, we'll leave it null or passed explicitly
        }

        return static::create([
            'event_type' => $type,
            'action' => $action,
            'metadata' => $metadata,
            'actor_id' => $actorId,
            'actor_role' => $actorRole,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
