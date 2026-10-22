<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Paginator;

final class ActivityRepository
{
    private const SELECT = 'SELECT a.*, u.name AS user_name, u.username FROM activity_log a LEFT JOIN users u ON u.id = a.user_id';
    private const FROM = 'FROM activity_log a LEFT JOIN users u ON u.id = a.user_id';

    public function paginate(array $filters, int $page): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['user'])) {
            $where[] = 'a.user_id = ?';
            $params[] = (int) $filters['user'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'a.subject_type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(a.action LIKE ? OR a.details LIKE ?)';
            array_push($params, '%' . $filters['q'] . '%', '%' . $filters['q'] . '%');
        }
        if (!empty($filters['from'])) {
            $where[] = 'a.created_at >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[] = 'a.created_at <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }

        return Paginator::run(self::SELECT, self::FROM, implode(' AND ', $where), $params, 'a.id DESC', $page, 50);
    }

    public function recent(int $limit = 8): array
    {
        return Database::connection()->query(self::SELECT . ' ORDER BY a.id DESC LIMIT ' . max(1, $limit))->fetchAll();
    }

    /** @return list<string> */
    public function subjectTypes(): array
    {
        return Database::connection()->query("SELECT DISTINCT subject_type FROM activity_log WHERE subject_type <> '' ORDER BY subject_type")->fetchAll(\PDO::FETCH_COLUMN);
    }

    /** Link to the record an entry is about, when there is a page for it. */
    public static function link(array $entry): ?string
    {
        $id = $entry['subject_id'] ?? null;

        return $id === null ? null : match ($entry['subject_type']) {
            'product' => '/products/' . $id,
            'supplier' => '/suppliers/' . $id,
            'purchase_order' => '/purchase-orders/' . $id,
            'sale' => '/sales/' . $id,
            'user' => '/users/' . $id . '/edit',
            'category' => '/categories/' . $id . '/edit',
            default => null,
        };
    }

    /** "sku: CPU-1 · from: 3 · to: 5" */
    public static function details(?string $json): string
    {
        $details = json_decode((string) $json, true);
        if (!is_array($details)) {
            return '';
        }
        $parts = [];
        foreach ($details as $key => $value) {
            $text = is_array($value) ? implode(' → ', array_map('strval', $value)) : (is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value);
            $parts[] = str_replace('_', ' ', (string) $key) . ': ' . $text;
        }

        return implode(' · ', $parts);
    }
}
