<?php

declare(strict_types=1);

// Buffer all output so stray warnings/notices never corrupt JSON responses
ob_start();

// Suppress notices/warnings from leaking into API responses — errors go to
// the log, not stdout
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/schema.php';

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = $uri;

// Detect the actual base path by looking for 'public' in the script name
$script = $_SERVER['SCRIPT_NAME']; // e.g. /my-app/public/index.php
$base = '';

if (strpos($script, '/public/') !== false) {
    $base = substr($script, 0, strpos($script, '/public/') + 7);
}

if ($base !== '' && strpos($uri, $base) === 0) {
    $path = substr($uri, strlen($base));
}

// Remove index.php if present
$path = str_replace('/index.php', '', $path);
$path = '/' . ltrim($path, '/');
$path = rtrim($path, '/') ?: '/';
// ── Router ────────────────────────────────────────────────────────────────────

if ($path === '/') {
    redirect('/view');
} elseif ($path === '/view') {
    require __DIR__ . '/view.php';
} elseif ($path === '/reports') {
    require __DIR__ . '/reports.php';
} elseif ($path === '/bonuses') {
    require __DIR__ . '/bonuses.php';
} elseif ($path === '/insert') {
    require __DIR__ . '/insert.php';
} elseif ($path === '/api/health') {
    json_response(['status' => 'ok']);
} elseif ($path === '/api/reports/sales') {
    handle_sales_report();
} elseif ($path === '/api/reports/bonuses') {
    handle_bonus_report();
} elseif (strpos($path, '/api/insert/') === 0) {
    $table = substr($path, 12);
    handle_insert($table);
} elseif (strpos($path, '/api/table/') === 0) {
    $table = substr($path, 11);
    handle_table_data($table);
} elseif (strpos($path, '/table/') === 0) {
    $tableName = substr($path, 7);
    require __DIR__ . '/table.php';
} else {
    not_found();
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function redirect(string $to): void
{
    global $base;
    $target = rtrim($base, '/') . '/' . ltrim($to, '/');
    header("Location: $target", true, 302);
    exit;
}

function json_response(mixed $data, int $status = 200): void
{
    // Discard any buffered output (warnings, notices) so they don't corrupt JSON
    if (ob_get_level()) ob_clean();
    http_response_code($status);
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function not_found(): void
{
    http_response_code(404);
    echo "404 Not Found";
    exit;
}

// ── GET /api/table/:name  (paginated JSON for the viewer) ─────────────────────

function handle_table_data(string $table): void
{
    try {
        $table = strtolower($table);
        if (!array_key_exists($table, TABLES)) {
            json_response(['error' => "Unknown table \"$table\""], 400);
        }

        $page   = max(1, (int)($_GET['page']  ?? 1));
        $limit  = min(500, max(1, (int)($_GET['limit'] ?? 100)));
        $offset = ($page - 1) * $limit;

        $sort  = $_GET['sort'] ?? null;
        $order = strtoupper($_GET['order'] ?? 'ASC');
        if (!in_array($order, ['ASC', 'DESC'])) $order = 'ASC';

        $db    = get_db();
        $total = (int)$db->query("SELECT COUNT(*) FROM \"$table\"")->fetchColumn();

        $orderBy = "";
        if ($sort && in_array(strtolower($sort), TABLES[$table]['columns'])) {
            $orderBy = "ORDER BY \"$sort\" $order";
        }

        $rows  = $db->query("SELECT * FROM \"$table\" $orderBy LIMIT $limit OFFSET $offset")->fetchAll();
        json_response(['total' => $total, 'rows' => $rows]);

    } catch (Throwable $e) {
        json_response(['error' => $e->getMessage()], 500);
    }
}

function handle_sales_report(): void
{
    try {
        $db = get_db();
        $groupBy = $_GET['by'] ?? 'customer'; // 'customer' or 'region'
        $startDate = $_GET['from'] ?? null;
        $endDate = $_GET['to'] ?? null;
        $sort = $_GET['sort'] ?? 'month';
        $order = strtoupper($_GET['order'] ?? 'DESC');
        if (!in_array($order, ['ASC', 'DESC'])) $order = 'DESC';

        $where = [];
        $params = [];

        if ($startDate) {
            $where[] = "o.orderdate >= :start";
            $params['start'] = $startDate;
        }
        if ($endDate) {
            $where[] = "o.orderdate <= :end";
            $params['end'] = $endDate;
        }

        $whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";
        
        $allowedSorts = ['month', 'order_count', 'total_sum'];
        if ($groupBy === 'region') {
            $allowedSorts[] = 'region';
            $sql = "
                SELECT 
                    COALESCE(NULLIF(c.region, ''), 'Unknown') AS region,
                    TO_CHAR(o.orderdate, 'YYYY-MM') AS month,
                    COUNT(DISTINCT o.orderid) AS order_count,
                    ROUND(SUM(CAST(od.unitprice * od.quantity * (1 - od.discount) AS NUMERIC)), 2) AS total_sum
                FROM orders o
                JOIN customers c ON o.customerid = c.customerid
                JOIN order_details od ON o.orderid = od.orderid
                $whereSql
                GROUP BY 1, 2
            ";
        } elseif ($groupBy === 'employee') {
            $allowedSorts[] = 'employee';
            $sql = "
                SELECT 
                    e.firstname || ' ' || e.lastname AS employee,
                    TO_CHAR(o.orderdate, 'YYYY-MM') AS month,
                    COUNT(DISTINCT o.orderid) AS order_count,
                    ROUND(SUM(CAST(od.unitprice * od.quantity * (1 - od.discount) AS NUMERIC)), 2) AS total_sum
                FROM orders o
                JOIN employees e ON o.employeeid = e.employeeid
                JOIN order_details od ON o.orderid = od.orderid
                $whereSql
                GROUP BY employee, month
            ";
        } else {
            $allowedSorts[] = 'client';
            $sql = "
                SELECT 
                    c.companyname AS client,
                    TO_CHAR(o.orderdate, 'YYYY-MM') AS month,
                    COUNT(DISTINCT o.orderid) AS order_count,
                    ROUND(SUM(CAST(od.unitprice * od.quantity * (1 - od.discount) AS NUMERIC)), 2) AS total_sum
                FROM orders o
                JOIN customers c ON o.customerid = c.customerid
                JOIN order_details od ON o.orderid = od.orderid
                $whereSql
                GROUP BY c.companyname, month
            ";
        }

        if (in_array($sort, $allowedSorts)) {
            $sql .= " ORDER BY \"$sort\" $order";
            if ($sort !== 'month') {
                $sql .= ", month DESC";
            }
        } else {
            $sql .= " ORDER BY month DESC, total_sum DESC";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        json_response(['rows' => $rows]);
    } catch (Throwable $e) {
        json_response(['error' => $e->getMessage()], 500);
    }
}

function handle_bonus_report(): void
{
    try {
        $db = get_db();
        // Requirements: 0.9% bonus, last 2 years (8 full quarters + current), 
        // sorted by Year DESC, Quarter DESC, Employee ASC.
        // We use the MAX(orderdate) as the reference point because sample data might be old.
        $sql = "
            WITH latest_order AS (
                SELECT MAX(orderdate) as max_date FROM orders
            )
            SELECT 
                e.firstname || ' ' || e.lastname AS employee,
                CAST(EXTRACT(YEAR FROM o.orderdate) AS INTEGER) AS year,
                CAST(EXTRACT(QUARTER FROM o.orderdate) AS INTEGER) AS quarter,
                COUNT(DISTINCT o.orderid) AS order_count,
                ROUND(SUM(CAST(od.unitprice * od.quantity * (1 - od.discount) AS NUMERIC)), 2) AS total_sum,
                ROUND(SUM(CAST(od.unitprice * od.quantity * (1 - od.discount) AS NUMERIC)) * 0.009, 2) AS bonus
            FROM orders o
            JOIN employees e ON o.employeeid = e.employeeid
            JOIN order_details od ON o.orderid = od.orderid
            CROSS JOIN latest_order lo
            WHERE o.orderdate >= date_trunc('quarter', lo.max_date) - INTERVAL '2 years'
            GROUP BY 1, 2, 3
            ORDER BY year DESC, quarter DESC, employee ASC
        ";

        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll();
        json_response(['rows' => $rows]);
    } catch (Throwable $e) {
        json_response(['error' => $e->getMessage()], 500);
    }
}

function handle_top_products_report(): void
{
    try {
        $db = get_db();
        $startDate = $_GET['from'] ?? null;
        $endDate = $_GET['to'] ?? null;
        
        $where = ["o.orderdate IS NOT NULL"];
        $params = [];

        if ($startDate) {
            $where[] = "o.orderdate >= :start";
            $params['start'] = $startDate;
        }
        if ($endDate) {
            $where[] = "o.orderdate <= :end";
            $params['end'] = $endDate;
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        $sql = "
            WITH product_sales AS (
                SELECT
                    COALESCE(NULLIF(o.shipregion, ''), NULLIF(c.region, ''), o.shipcountry, 'Unknown') AS region,
                    CAST(EXTRACT(YEAR FROM o.orderdate) AS INTEGER) AS year,
                    p.productname,
                    SUM(od.quantity) AS total_quantity,
                    ROUND(CAST(SUM(od.unitprice * od.quantity * (1 - od.discount)) AS NUMERIC), 2) AS total_amount
                FROM orders o
                LEFT JOIN customers c ON o.customerid = c.customerid
                JOIN order_details od ON o.orderid = od.orderid
                JOIN products p ON od.productid = p.productid
                $whereSql
                GROUP BY 1, 2, 3
            ),
            ranked_products AS (
                SELECT
                    region,
                    year,
                    productname,
                    total_quantity,
                    total_amount,
                    ROW_NUMBER() OVER(PARTITION BY region, year ORDER BY total_quantity DESC) as rank
                FROM product_sales
            )
            SELECT * FROM ranked_products 
            WHERE rank <= 5
            ORDER BY year DESC, region ASC, rank ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        json_response(['rows' => $rows]);
    } catch (Throwable $e) {
        json_response(['error' => $e->getMessage()], 500);
    }
}

// ── POST /api/insert/:table ───────────────────────────────────────────────────

function handle_insert(string $table): void
{
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'Method not allowed'], 405);
        }

        $table = strtolower($table);
        if (!array_key_exists($table, TABLES)) {
            json_response(['error' => "Unknown table \"$table\". Valid: " . implode(', ', array_keys(TABLES))], 400);
        }

        $raw_body = file_get_contents('php://input');
        $body     = json_decode($raw_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            json_response(['error' => 'Invalid JSON body: ' . json_last_error_msg()], 400);
        }
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

    } catch (Throwable $e) {
        json_response(['error' => 'Server error: ' . $e->getMessage()], 500);
    }
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
