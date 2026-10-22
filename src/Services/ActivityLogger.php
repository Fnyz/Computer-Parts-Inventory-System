<?php

declare(strict_types=1);

namespace App\Services;

use App\Auth\Auth;
use App\Core\Database;

/** Records who changed what, for the Activity log screen. */
final class ActivityLogger
{
    public static function log(string $action, string $subjectType = '', ?int $subjectId = null, array $details = [], ?string $ip = null): void
    {
        $details = array_filter($details, static fn ($value) => $value !== null);
        Database::connection()->prepare(
            'INSERT INTO activity_log (user_id, action, subject_type, subject_id, details, ip, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
        )->execute([
            Auth::id() ?: null,
            $action,
            $subjectType,
            $subjectId,
            $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null),
            now(),
        ]);
    }
}
