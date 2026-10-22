<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Gap-free document numbers per prefix and year: SO-2026-00001, SO-2026-00002, … PO-2026-00001.
 * The counter row is locked, so two cashiers can never get the same number.
 */
final class SequenceService
{
    public function next(string $prefix, ?string $year = null): string
    {
        $year ??= date('Y');

        return Database::transaction(function (\PDO $pdo) use ($prefix, $year): string {
            // Create the year's counter if needed (safe if two requests do it at once), then lock it
            $pdo->prepare('INSERT IGNORE INTO sequences (name, period, next_value) VALUES (?, ?, 1)')->execute([$prefix, $year]);
            $select = $pdo->prepare('SELECT next_value FROM sequences WHERE name = ? AND period = ? FOR UPDATE');
            $select->execute([$prefix, $year]);
            $value = (int) $select->fetchColumn();
            $pdo->prepare('UPDATE sequences SET next_value = next_value + 1 WHERE name = ? AND period = ?')->execute([$prefix, $year]);

            return self::format($prefix, $year, $value);
        });
    }

    public static function format(string $prefix, string $year, int $value): string
    {
        return sprintf('%s-%s-%05d', $prefix, $year, $value);
    }
}
