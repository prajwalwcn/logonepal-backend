<?php

namespace App\Services\Admin;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;

class AdminActivityLogger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function log(string $action, ?Model $subject, string $description, array $meta = []): AdminActivityLog
    {
        $request = request();

        return AdminActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => mb_substr($description, 0, 500),
            'meta' => $meta === [] ? null : $meta,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);
    }
}
