<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Write an immutable audit log entry for the current actor.
     */
    public static function log(string $action, string $description = '', ?User $user = null): AuditLog
    {
        $user ??= Auth::user();

        $request = request();

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
        ]);
    }
}
