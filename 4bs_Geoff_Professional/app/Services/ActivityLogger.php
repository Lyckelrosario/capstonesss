<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogger
{
    public function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
        ?Request $request = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $request?->user()?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => $request?->ip(),
        ]);
    }

    public function recent(int $limit = 50)
    {
        return ActivityLog::with('user:id,name')->recent($limit)->get();
    }
}
