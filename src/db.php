<?php

declare(strict_types=1);

function get_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    // Load .env if not already loaded
    static $env_loaded = false;
    if (!$env_loaded) {
        $env_file = __DIR__ . '/../.env';
        if (file_exists($env_file)) {
            foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
                [$key, $val] = explode('=', $line, 2);
                putenv(trim($key) . '=' . trim($val));
            }
        }
        $env_loaded = true;
    }

    $url = getenv('DATABASE_URL');
    if (!$url) {
        $url = 'postgresql://neondb_owner:npg_CZVv6XFu8QjM@ep-green-violet-aqyhtco2-pooler.c-8.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require';
    }

    $p = parse_url($url);

    // Parse any extra options from the query string (e.g. sslmode=require)
    $options = [];
    if (!empty($p['query'])) {
        parse_str($p['query'], $options);
    }

    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s',
        $p['host'],
        $p['port'] ?? 5432,
        ltrim($p['path'] ?? '/neondb', '/')
    );

    // Append sslmode if present
    if (!empty($options['sslmode'])) {
        $dsn .= ';sslmode=' . $options['sslmode'];
    }

    $pdo = new PDO($dsn, $p['user'] ?? '', rawurldecode($p['pass'] ?? ''), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

/**
 * Generates a unique 5-character string that doesn't exist in the customers table.
 */
function generate_unique_customer_id(PDO $db): string
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    while (true) {
        $id = '';
        for ($i = 0; $i < 5; $i++) {
            $id .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $db->prepare("SELECT 1 FROM customers WHERE customerid = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            return $id;
        }
    }
}
