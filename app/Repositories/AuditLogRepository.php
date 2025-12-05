<?php

namespace App\Repositories;

use App\Models\AuditLog;

class AuditLogRepository
{
    public function create(array $data): AuditLog
    {
        return AuditLog::create($data);
    }

    public function getLatest(int $limit = 50)
    {
        return AuditLog::orderBy('created_at', 'desc')->limit($limit)->get();
    }
}
