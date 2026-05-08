<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogger
{
    public function log(string $action, string $description, ?User $user = null, ?Request $request = null): ActivityLog
    {
        $request ??= request();
        $user ??= $request->user();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public static function record(string $action, string $description, ?User $user = null, ?Request $request = null): ActivityLog
    {
        return app(self::class)->log($action, $description, $user, $request);
    }
}
