#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';

// Load .env
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($val));
    }
}

$DATA_DIR = __DIR__ . '/../data';
$db = get_db();

echo "🌱  Loading Northwind CSVs into PostgreSQL…\n\n";

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
    return read_csv("$DATA_DIR/$name.csv");
}

function null_if_empty(mixed $v): mixed
{
    return ($v === '' || $v === null) ? null : $v;
}

function load(PDO $db, string $table, array $rows, callable $transform): void
{
    $inserted = 0;
    $skipped  = 0;

    foreach ($rows as $raw) {
        $row = $transform($raw);
        if ($row === null) { $skipped++; continue; }

        $cols         = array_keys($row);
        $colList      = implode(', ', array_map(fn($c) => "\"$c\"", $cols));
        $placeholders = implode(', ', array_map(fn($c) => ":$c", $cols));

        try {
            $stmt = $db->prepare("INSERT INTO \"$table\" ($colList) VALUES ($placeholders) ON CONFLICT DO NOTHING");
            $stmt->execute($row);
            $inserted++;
        } catch (Throwable $e) {
            echo "  ⚠  $table row skipped: " . explode("\n", $e->getMessage())[0] . "\n";
            $skipped++;
        }
    }

    echo "  ✓  $table: $inserted inserted, $skipped skipped\n";
}

// ── Insert in FK-safe order ───────────────────────────────────────────────────

load($db, 'categories', csv('categories'), fn($r) => [
    'categoryid'   => (int)$r['categoryid'],
    'categoryname' => $r['categoryname'],
    'description'  => null_if_empty($r['description']),
]);

load($db, 'suppliers', csv('suppliers'), fn($r) => [
    'supplierid'   => (int)$r['supplierid'],
    'companyname'  => $r['companyname'],
    'contactname'  => null_if_empty($r['contactname']),
    'contacttitle' => null_if_empty($r['contacttitle']),
    'address'      => null_if_empty($r['address']),
    'city'         => null_if_empty($r['city']),
    'region'       => null_if_empty($r['region']),
    'postalcode'   => null_if_empty($r['postalcode']),
    'country'      => null_if_empty($r['country']),
    'phone'        => null_if_empty($r['phone']),
    'fax'          => null_if_empty($r['fax']),
    'homepage'     => null_if_empty($r['homepage'] ?? ''),
]);

load($db, 'shippers', csv('shippers'), fn($r) => [
    'shipperid'   => (int)$r['shipperid'],
    'companyname' => $r['companyname'],
    'phone'       => null_if_empty($r['phone']),
]);

load($db, 'region', csv('region'), fn($r) => [
    'regionid'          => (int)$r['regionid'],
    'regiondescription' => trim($r['regiondescription']),
]);

load($db, 'territories', csv('territories'), fn($r) => [
    'territoryid'          => $r['territoryid'],
    'territorydescription' => trim($r['territorydescription']),
    'regionid'             => (int)$r['regionid'],
]);

load($db, 'products', csv('products'), fn($r) => [
    'productid'       => (int)$r['productid'],
    'productname'     => $r['productname'],
    'supplierid'      => $r['supplierid']  ? (int)$r['supplierid']  : null,
    'categoryid'      => $r['categoryid']  ? (int)$r['categoryid']  : null,
    'quantityperunit' => null_if_empty($r['quantityperunit']),
    'unitprice'       => $r['unitprice']   ? (float)$r['unitprice']  : 0,
    'unitsinstock'    => $r['unitsinstock'] ? (int)$r['unitsinstock'] : 0,
    'unitsonorder'    => $r['unitsonorder'] ? (int)$r['unitsonorder'] : 0,
    'reorderlevel'    => $r['reorderlevel'] ? (int)$r['reorderlevel'] : 0,
    'discontinued'    => ($r['discontinued'] === '1' || strtolower($r['discontinued']) === 'true'),
]);

load($db, 'customers', csv('customers'), fn($r) => [
    'customerid'   => trim($r['customerid']),
    'companyname'  => $r['companyname'],
    'contactname'  => null_if_empty($r['contactname']),
    'contacttitle' => null_if_empty($r['contacttitle']),
    'address'      => null_if_empty($r['address']),
    'city'         => null_if_empty($r['city']),
    'region'       => null_if_empty($r['region']),
    'postalcode'   => null_if_empty($r['postalcode']),
    'country'      => null_if_empty($r['country']),
    'phone'        => null_if_empty($r['phone']),
    'fax'          => null_if_empty($r['fax']),
]);

load($db, 'customerdemographics', csv('customerdemographics'), fn($r) => [
    'customertypeid' => trim($r['customertypeid']),
    'customerdesc'   => null_if_empty($r['customerdesc'] ?? ''),
]);

load($db, 'customercustomerdemo', csv('customercustomerdemo'), function ($r) {
    if (!trim($r['customerid'] ?? '') || !trim($r['customertypeid'] ?? '')) return null;
    return ['customerid' => trim($r['customerid']), 'customertypeid' => trim($r['customertypeid'])];
});

// Employees: insert without reportsto, then patch (self-referencing FK)
$emp_rows = csv('employees');
$emp_ins  = 0;
foreach ($emp_rows as $r) {
    $row = [
        'employeeid'      => (int)$r['employeeid'],
        'lastname'        => $r['lastname'],
        'firstname'       => $r['firstname'],
        'title'           => null_if_empty($r['title']),
        'titleofcourtesy' => null_if_empty($r['titleofcourtesy']),
        'birthdate'       => null_if_empty($r['birthdate']),
        'hiredate'        => null_if_empty($r['hiredate']),
        'address'         => null_if_empty($r['address']),
        'city'            => null_if_empty($r['city']),
        'region'          => null_if_empty($r['region']),
        'postalcode'      => null_if_empty($r['postalcode']),
        'country'         => null_if_empty($r['country']),
        'homephone'       => null_if_empty($r['homephone']),
        'extension'       => null_if_empty($r['extension']),
        'notes'           => null_if_empty($r['notes']),
        'reportsto'       => null,
    ];
    $cols = array_keys($row);
    $stmt = $db->prepare(
        'INSERT INTO "employees" (' . implode(',', array_map(fn($c) => "\"$c\"", $cols)) . ')' .
        ' VALUES (' . implode(',', array_map(fn($c) => ":$c", $cols)) . ')' .
        ' ON CONFLICT DO NOTHING'
    );
    try { $stmt->execute($row); $emp_ins++; } catch (Throwable) {}
}
// Patch reportsto
foreach ($emp_rows as $r) {
    if (!empty($r['reportsto'])) {
        $db->prepare('UPDATE employees SET reportsto = :rt WHERE employeeid = :id')
           ->execute(['rt' => (int)$r['reportsto'], 'id' => (int)$r['employeeid']]);
    }
}
echo "  ✓  employees: $emp_ins inserted (reportsto patched)\n";

load($db, 'employeeterritories', csv('employeeterritories'), function ($r) {
    if (!$r['employeeid'] || !$r['territoryid']) return null;
    return ['employeeid' => (int)$r['employeeid'], 'territoryid' => $r['territoryid']];
});

load($db, 'orders', csv('orders'), fn($r) => [
    'orderid'        => (int)$r['orderid'],
    'customerid'     => null_if_empty(trim($r['customerid'])),
    'employeeid'     => $r['employeeid']  ? (int)$r['employeeid']  : null,
    'orderdate'      => null_if_empty($r['orderdate']),
    'requireddate'   => null_if_empty($r['requireddate']),
    'shippeddate'    => null_if_empty($r['shippeddate']),
    'shipvia'        => $r['shipvia']     ? (int)$r['shipvia']      : null,
    'freight'        => $r['freight']     ? (float)$r['freight']    : 0,
    'shipname'       => null_if_empty($r['shipname']),
    'shipaddress'    => null_if_empty($r['shipaddress']),
    'shipcity'       => null_if_empty($r['shipcity']),
    'shipregion'     => null_if_empty($r['shipregion']),
    'shippostalcode' => null_if_empty($r['shippostalcode']),
    'shipcountry'    => null_if_empty($r['shipcountry']),
]);

load($db, 'order_details', csv('order_details'), fn($r) => [
    'orderid'   => (int)$r['orderid'],
    'productid' => (int)$r['productid'],
    'unitprice' => (float)$r['unitprice'],
    'quantity'  => (int)$r['quantity'],
    'discount'  => (float)$r['discount'],
]);

echo "\n✅  Done!\n";
