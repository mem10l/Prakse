<?php

declare(strict_types=1);

function get_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $url = getenv('DATABASE_URL');
    if (!$url) {
        throw new RuntimeException('DATABASE_URL is not set');
    }

    // Parse postgres://user:pass@host:port/dbname
    $p = parse_url($url);
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $p['host'],
        $p['port'] ?? 5432,
        ltrim($p['path'] ?? '/northwind', '/')
    );

    $pdo = new PDO($dsn, $p['user'] ?? '', $p['pass'] ?? '', [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
