#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/src/db.php';

// ── Connect ───────────────────────────────────────────────────────────────────

echo "🔌  Connecting to Neon PostgreSQL…\n";

try {
    $pdo = get_db();
    echo "✓  Connected to Neon database\n\n";
} catch (Throwable $e) {
    die("✗  Connection failed: " . $e->getMessage() . "\n");
}

// ── Step 1: Create schema ─────────────────────────────────────────────────────

echo "📐  Creating schema…\n";

$sql_file = __DIR__ . '/scripts/seed.sql';
if (!file_exists($sql_file)) {
    die("✗  scripts/seed.sql not found. Run this script from the project root.\n");
}

try {
    $pdo->exec(file_get_contents($sql_file));
    echo "✓  All 13 tables created\n\n";
} catch (Throwable $e) {
    die("✗  Schema creation failed: " . $e->getMessage() . "\n");
}

// ── Step 2: Load CSVs ─────────────────────────────────────────────────────────

echo "🌱  Loading CSV data…\n\n";

$DATA_DIR = __DIR__ . '/data';
if (!is_dir($DATA_DIR)) {
    die("✗  data/ directory not found. Run this script from the project root.\n");
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function read_csv(string $path): array
{
    $lines = array_values(array_filter(
        explode("\n", str_replace(["\r\n", "\r"], "\n", file_get_contents($path)))
    ));
    if (count($lines) < 2) return [];
    $headers = array_map(fn($h) => strtolower(trim($h)), str_getcsv($lines[0]));
    $rows = [];
    foreach (array_slice($lines, 1) as $line) {
        if (!trim($line)) continue;
        $vals = str_getcsv($line);
        $row  = [];
        foreach ($headers as $i => $h) $row[$h] = trim($vals[$i] ?? '');
        $rows[] = $row;
    }
    return $rows;
}

function csv(string $name): array
{
    global $DATA_DIR;
    $path = "$DATA_DIR/$name.csv";
    if (!file_exists($path)) {
        echo "  ⚠  $name.csv not found, skipping\n";
        return [];
    }
    return read_csv($path);
}

function nil(mixed $v): mixed
{
    return ($v === '' || $v === null) ? null : $v;
}

function load(PDO $pdo, string $table, array $rows, callable $transform): void
{
    if (!$rows) { echo "  -  $table: no data\n"; return; }

    $inserted = 0;
    $skipped  = 0;

    foreach ($rows as $raw) {
        $row = $transform($raw);
        if ($row === null) { $skipped++; continue; }

        $cols         = array_keys($row);
        $colList      = implode(', ', array_map(fn($c) => "\"$c\"", $cols));
        $placeholders = implode(', ', array_map(fn($c) => ":$c", $cols));

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO \"$table\" ($colList) VALUES ($placeholders) ON CONFLICT DO NOTHING"
            );
            $stmt->execute($row);
            $inserted++;
        } catch (Throwable $e) {
            echo "  ⚠  $table row skipped: " . explode("\n", $e->getMessage())[0] . "\n";
            $skipped++;
        }
    }

    echo "  ✓  $table: $inserted inserted" . ($skipped ? ", $skipped skipped" : "") . "\n";
}

// ── Insert in FK-safe order ───────────────────────────────────────────────────

load($pdo, 'categories', csv('categories'), fn($r) => [
    'categoryid'   => (int)$r['categoryid'],
    'categoryname' => $r['categoryname'],
    'description'  => nil($r['description']),
]);

load($pdo, 'suppliers', csv('suppliers'), fn($r) => [
    'supplierid'   => (int)$r['supplierid'],
    'companyname'  => $r['companyname'],
    'contactname'  => nil($r['contactname']),
    'contacttitle' => nil($r['contacttitle']),
    'address'      => nil($r['address']),
    'city'         => nil($r['city']),
    'region'       => nil($r['region']),
    'postalcode'   => nil($r['postalcode']),
    'country'      => nil($r['country']),
    'phone'        => nil($r['phone']),
    'fax'          => nil($r['fax']),
    'homepage'     => nil($r['homepage'] ?? ''),
]);

load($pdo, 'shippers', csv('shippers'), fn($r) => [
    'shipperid'   => (int)$r['shipperid'],
    'companyname' => $r['companyname'],
    'phone'       => nil($r['phone']),
]);

load($pdo, 'region', csv('region'), fn($r) => [
    'regionid'          => (int)$r['regionid'],
    'regiondescription' => trim($r['regiondescription']),
]);

load($pdo, 'territories', csv('territories'), fn($r) => [
    'territoryid'          => $r['territoryid'],
    'territorydescription' => trim($r['territorydescription']),
    'regionid'             => (int)$r['regionid'],
]);

load($pdo, 'products', csv('products'), fn($r) => [
    'productid'       => (int)$r['productid'],
    'productname'     => $r['productname'],
    'supplierid'      => $r['supplierid']   ? (int)$r['supplierid']   : null,
    'categoryid'      => $r['categoryid']   ? (int)$r['categoryid']   : null,
    'quantityperunit' => nil($r['quantityperunit']),
    'unitprice'       => $r['unitprice']    ? (float)$r['unitprice']  : 0,
    'unitsinstock'    => $r['unitsinstock'] ? (int)$r['unitsinstock'] : 0,
    'unitsonorder'    => $r['unitsonorder'] ? (int)$r['unitsonorder'] : 0,
    'reorderlevel'    => $r['reorderlevel'] ? (int)$r['reorderlevel'] : 0,
    'discontinued'    => ($r['discontinued'] === '1' || strtolower($r['discontinued']) === 'true'),
]);

load($pdo, 'customers', csv('customers'), fn($r) => [
    'customerid'   => trim($r['customerid']),
    'companyname'  => $r['companyname'],
    'contactname'  => nil($r['contactname']),
    'contacttitle' => nil($r['contacttitle']),
    'address'      => nil($r['address']),
    'city'         => nil($r['city']),
    'region'       => nil($r['region']),
    'postalcode'   => nil($r['postalcode']),
    'country'      => nil($r['country']),
    'phone'        => nil($r['phone']),
    'fax'          => nil($r['fax']),
]);

load($pdo, 'customerdemographics', csv('customerdemographics'), fn($r) => [
    'customertypeid' => trim($r['customertypeid']),
    'customerdesc'   => nil($r['customerdesc'] ?? ''),
]);

load($pdo, 'customercustomerdemo', csv('customercustomerdemo'), function ($r) {
    if (!trim($r['customerid'] ?? '') || !trim($r['customertypeid'] ?? '')) return null;
    return ['customerid' => trim($r['customerid']), 'customertypeid' => trim($r['customertypeid'])];
});

// Employees: insert without reportsto first (self-referencing FK), then patch
$emp_rows = csv('employees');
$emp_ins  = 0;
foreach ($emp_rows as $r) {
    $row = [
        'employeeid'      => (int)$r['employeeid'],
        'lastname'        => $r['lastname'],
        'firstname'       => $r['firstname'],
        'title'           => nil($r['title']),
        'titleofcourtesy' => nil($r['titleofcourtesy']),
        'birthdate'       => nil($r['birthdate']),
        'hiredate'        => nil($r['hiredate']),
        'address'         => nil($r['address']),
        'city'            => nil($r['city']),
        'region'          => nil($r['region']),
        'postalcode'      => nil($r['postalcode']),
        'country'         => nil($r['country']),
        'homephone'       => nil($r['homephone']),
        'extension'       => nil($r['extension']),
        'notes'           => nil($r['notes']),
        'reportsto'       => null,
    ];
    $cols = array_keys($row);
    try {
        $pdo->prepare(
            'INSERT INTO "employees" (' . implode(',', array_map(fn($c) => "\"$c\"", $cols)) . ')' .
            ' VALUES (' . implode(',', array_map(fn($c) => ":$c", $cols)) . ')' .
            ' ON CONFLICT DO NOTHING'
        )->execute($row);
        $emp_ins++;
    } catch (Throwable $e) {
        echo "  ⚠  employees row skipped: " . explode("\n", $e->getMessage())[0] . "\n";
    }
}
foreach ($emp_rows as $r) {
    if (!empty($r['reportsto'])) {
        $pdo->prepare('UPDATE employees SET reportsto = :rt WHERE employeeid = :id')
            ->execute(['rt' => (int)$r['reportsto'], 'id' => (int)$r['employeeid']]);
    }
}
echo "  ✓  employees: $emp_ins inserted (reportsto patched)\n";

load($pdo, 'employeeterritories', csv('employeeterritories'), function ($r) {
    if (!$r['employeeid'] || !$r['territoryid']) return null;
    return ['employeeid' => (int)$r['employeeid'], 'territoryid' => $r['territoryid']];
});

load($pdo, 'orders', csv('orders'), fn($r) => [
    'orderid'        => (int)$r['orderid'],
    'customerid'     => nil(trim($r['customerid'])),
    'employeeid'     => $r['employeeid']  ? (int)$r['employeeid']  : null,
    'orderdate'      => nil($r['orderdate']),
    'requireddate'   => nil($r['requireddate']),
    'shippeddate'    => nil($r['shippeddate']),
    'shipvia'        => $r['shipvia']     ? (int)$r['shipvia']      : null,
    'freight'        => $r['freight']     ? (float)$r['freight']    : 0,
    'shipname'       => nil($r['shipname']),
    'shipaddress'    => nil($r['shipaddress']),
    'shipcity'       => nil($r['shipcity']),
    'shipregion'     => nil($r['shipregion']),
    'shippostalcode' => nil($r['shippostalcode']),
    'shipcountry'    => nil($r['shipcountry']),
]);

load($pdo, 'order_details', csv('order_details'), fn($r) => [
    'orderid'   => (int)$r['orderid'],
    'productid' => (int)$r['productid'],
    'unitprice' => (float)$r['unitprice'],
    'quantity'  => (int)$r['quantity'],
    'discount'  => (float)$r['discount'],
]);

echo "\n✅  Done! Your Neon database is ready.\n";
echo "    Connection string: postgresql://$user:****@$host/$db?sslmode=$ssl\n";
