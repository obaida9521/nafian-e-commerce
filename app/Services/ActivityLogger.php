<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AdminActivityLog;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Record an admin action to the append-only audit log.
     *
     * @param  array<string, mixed>|null  $oldValue
     * @param  array<string, mixed>|null  $newValue
     */
    public function log(
        ?Admin $admin,
        string $action,
        string $resourceType,
        ?int $resourceId,
        string $description,
        ?array $oldValue = null,
        ?array $newValue = null,
    ): AdminActivityLog {
        return AdminActivityLog::create([
            'admin_id' => $admin?->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'description' => $description,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 500),
        ]);
    }
}
