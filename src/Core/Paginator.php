<?php

declare(strict_types=1);

namespace App\Core;

/** Runs a list query one page at a time. */
final class Paginator
{
    public const PER_PAGE = 25;

    /**
     * @param string $select e.g. "SELECT p.* FROM products p"   (no WHERE / ORDER / LIMIT)
     * @param string $from   e.g. "FROM products p"               (same joins, used for the count)
     * @return array{rows: list<array>, total: int, page: int, pages: int, perPage: int}
     */
    public static function run(string $select, string $from, string $where, array $params, string $orderBy, int $page, int $perPage = self::PER_PAGE): array
    {
        $pdo = Database::connection();
        $whereSql = $where !== '' ? ' WHERE ' . $where : '';

        $count = $pdo->prepare('SELECT COUNT(*) ' . $from . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare($select . $whereSql . ' ORDER BY ' . $orderBy . " LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
    }
}
