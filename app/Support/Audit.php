<?php

namespace App\Support;

use App\Models\AuditLog;

class Audit
{
    public static function record(string $action, ?string $description = null, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
