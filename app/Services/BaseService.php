<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

abstract class BaseService
{
    /**
     * Log an audit event.
     *
     * @param string $type
     * @param string $action
     * @param array $metadata
     * @param string|null $targetType
     * @param string|null $targetId
     * @return AuditLog
     */
    protected function logAudit($type, $action, $metadata = [], $targetType = null, $targetId = null)
    {
        $actorId = null;
        $actorRole = null;

        // Try to get actor from session or auth
        if (Auth::check()) {
            $actorId = Auth::id();
            // In a real app with strict roles, we'd get this from the user model or session
            // For now, let's assume session has it or we default
            $actorRole = session('afm_role', 'user'); 
        } elseif (session('afm_token_id')) {
             // If using custom session token logic
             // We might need to look up the token or trust the session
             $actorRole = session('afm_role');
             // actor_id might be student_id or staff_id stored in session
             // Let's assume we store it in session during handshake
             $actorId = session('afm_user_id'); 
        }

        return AuditLog::logEvent(
            $type,
            $action,
            $metadata,
            $actorId,
            $actorRole,
            $targetType,
            $targetId
        );
    }
}
