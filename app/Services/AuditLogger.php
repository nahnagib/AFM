<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Log an audit event
     *
     * @param string $eventType
     * @param string $actorType
     * @param string|null $actorId
     * @param string|null $requestId
     * @param string|null $payloadHash
     * @param array $meta
     * @return AuditLog
     */
    public function log(
        string $eventType,
        string $actorType,
        ?string $actorId = null,
        ?string $requestId = null,
        ?string $payloadHash = null,
        array $meta = []
    ): AuditLog {
        return AuditLog::create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'event_type' => $eventType,
            'request_id' => $requestId,
            'payload_hash' => $payloadHash,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'meta_json' => $meta,
        ]);
    }

    /**
     * Log SSO validation success
     */
    public function logSsoValidated(string $requestId, string $payloadHash, string $studentId): AuditLog
    {
        return $this->log(
            'sso_validated',
            'system',
            null,
            $requestId,
            $payloadHash,
            ['student_id' => $studentId]
        );
    }

    /**
     * Log SSO validation failure
     */
    public function logSsoRejected(string $requestId, string $reason): AuditLog
    {
        return $this->log(
            'sso_rejected',
            'system',
            null,
            $requestId,
            null,
            ['reason' => $reason]
        );
    }

    /**
     * Log feedback submission
     */
    public function logFeedbackSubmitted(string $studentId, int $formId, string $courseRegNo): AuditLog
    {
        return $this->log(
            'feedback_submitted',
            'student',
            $studentId,
            null,
            null,
            [
                'form_id' => $formId,
                'course_reg_no' => $courseRegNo,
            ]
        );
    }

    /**
     * Log export generation
     */
    public function logExportGenerated(string $actorId, string $exportType, array $filters): AuditLog
    {
        return $this->log(
            'export_generated',
            'qa_officer',
            $actorId,
            null,
            null,
            [
                'export_type' => $exportType,
                'filters' => $filters,
            ]
        );
    }

    /**
     * Log alerts run
     */
    public function logAlertsRun(int $nonCompletersCount): AuditLog
    {
        return $this->log(
            'alerts_run',
            'system',
            null,
            null,
            null,
            ['non_completers_count' => $nonCompletersCount]
        );
    }
}
