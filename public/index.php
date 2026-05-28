<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/schema.php';

// Load .env if it exists
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($val));
    }
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

// ── Router ────────────────────────────────────────────────────────────────────

match (true) {
    $path === '/'                                      => redirect('/view'),
    $path === '/view'                                  => require __DIR__ . '/view.php',
    $path === '/insert'                                => require __DIR__ . '/insert.php',
    $path === '/api/health'                            => json_response(['status' => 'ok']),
    preg_match('#^/api/insert/(\w+)$#', $path, $m)    => handle_insert($m[1]),
    preg_match('#^/api/table/(\w+)$#', $path, $m)     => handle_table_data($m[1]),
    default                                            => not_found(),
};

// ── Helpers ───────────────────────────────────────────────────────────────────

function redirect(string $to): void
{
    header("Location: $to", true, 302);
    exit;
}

function json_response(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function not_found(): void
{
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

// ── GET /api/table/:name  (paginated JSON for the viewer) ─────────────────────

function handle_table_data(string $table): void
{
    global $TABLES;
    $table = strtolower($table);
    if (!array_key_exists($table, TABLES)) {
        json_response(['error' => "Unknown table \"$table\""], 400);
    }

    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(500, max(1, (int)($_GET['limit'] ?? 100)));
    $offset = ($page - 1) * $limit;

    try {
        $db    = get_db();
        $total = (int)$db->query("SELECT COUNT(*) FROM \"$table\"")->fetchColumn();
        $rows  = $db->query("SELECT * FROM \"$table\" LIMIT $limit OFFSET $offset")->fetchAll();
        json_response(['total' => $total, 'rows' => $rows]);
    } catch (Throwable $e) {
        json_response(['error' => $e->getMessage()], 500);
    }
}

// ── POST /api/insert/:table ───────────────────────────────────────────────────

function handle_insert(string $table): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['error' => 'Method not allowed'], 405);
    }

    $table = strtolower($table);
    if (!array_key_exists($table, TABLES)) {
        json_response(['error' => "Unknown table \"$table\". Valid: " . implode(', ', array_keys(TABLES))], 400);
    }

    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body || empty($body['data'])) {
        json_response(['error' => 'Body must be JSON: { "format": "json"|"csv", "data": "..." }'], 400);
    }

    $format = $body['format'] ?? 'json';
    $data   = $body['data'];

    try {
        $rows = $format === 'csv' ? parse_csv($data) : parse_json_rows($data);
    } catch (Throwable $e) {
        json_response(['error' => 'Parse error: ' . $e->getMessage()], 400);
    }

    $result = insert_rows($table, $rows);
    $status = ($result['errors'] && !$result['inserted']) ? 400 : 200;
    json_response($result, $status);
}

function parse_csv(string $text): array
{
    $lines = array_filter(explode("\n", str_replace(["\r\n", "\r"], "\n", trim($text))));
    $lines = array_values($lines);
    if (count($lines) < 2) throw new RuntimeException('CSV must have a header row and at least one data row');

    $headers = array_map(fn($h) => strtolower(trim($h)), str_getcsv($lines[0]));
    $rows = [];
    foreach (array_slice($lines, 1) as $line) {
        $values = str_getcsv($line);
        $row = [];
        foreach ($headers as $i => $h) {
            $row[$h] = trim($values[$i] ?? '');
        }
        $rows[] = $row;
    }
    return $rows;
}

function parse_json_rows(string $text): array
{
    $parsed = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
    if (is_array($parsed) && array_is_list($parsed)) return $parsed;
    if (is_array($parsed)) return [$parsed];
    throw new RuntimeException('JSON must be an object or array of objects');
}

function insert_rows(string $table, array $rows): array
{
    $schema   = TABLES[$table];
    $db       = get_db();
    $inserted = 0;
    $errors   = [];

    foreach ($rows as $i => $raw) {
        // Normalise keys to lowercase
        $row = array_combine(
            array_map('strtolower', array_keys($raw)),
            array_values($raw)
        );

        // Validate required fields
        $missing = array_filter($schema['required'], fn($col) => !isset($row[$col]) || $row[$col] === '');
        if ($missing) {
            $errors[] = "Row " . ($i + 1) . ": missing required field(s): " . implode(', ', $missing);
            continue;
        }

        // Filter to known columns; empty string → NULL
        $filtered = [];
        foreach ($schema['columns'] as $col) {
            if (array_key_exists($col, $row)) {
                $filtered[$col] = ($row[$col] === '') ? null : $row[$col];
            }
        }

        if (!$filtered) {
            $errors[] = "Row " . ($i + 1) . ": no recognisable columns found";
            continue;
        }

        try {
            $cols        = array_keys($filtered);
            $colList     = implode(', ', array_map(fn($c) => "\"$c\"", $cols));
            $placeholders = implode(', ', array_map(fn($c) => ":$c", $cols));

            $stmt = $db->prepare(
                "INSERT INTO \"$table\" ($colList) VALUES ($placeholders) ON CONFLICT DO NOTHING"
            );
            $stmt->execute($filtered);
            $inserted++;
        } catch (Throwable $e) {
            $errors[] = "Row " . ($i + 1) . ": " . $e->getMessage();
        }
    }

    return ['inserted' => $inserted, 'errors' => $errors];
}
