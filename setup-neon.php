#!/usr/bin/env php
<?php

declare(strict_types=1);

// ── Credentials ───────────────────────────────────────────────────────────────

$host     = 'ep-green-violet-aqyhtco2-pooler.c-8.us-east-1.aws.neon.tech';
$dbname   = 'neondb';
$user     = 'neondb_owner';
$password = 'npg_CZVv6XFu8QjM';

$dsn = "pgsql:host=$host;dbname=$dbname;sslmode=require";

// ── Connect ───────────────────────────────────────────────────────────────────

echo "Connecting to Neon…\n";
try {
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "  Connected.\n\n";
} catch (Throwable $e) {
    die("  Connection failed: " . $e->getMessage() . "\n");
}

// ── Schema ────────────────────────────────────────────────────────────────────

echo "Creating schema…\n";

$schema = <<<SQL

DROP TABLE IF EXISTS employeeterritories, order_details, orders, products,
  customercustomerdemo, customerdemographics, customers, employees,
  territories, suppliers, shippers, region, categories CASCADE;

CREATE TABLE categories (
  categoryid   SERIAL PRIMARY KEY,
  categoryname VARCHAR(15) NOT NULL,
  description  TEXT
);

CREATE TABLE suppliers (
  supplierid   SERIAL PRIMARY KEY,
  companyname  VARCHAR(40) NOT NULL,
  contactname  VARCHAR(30),
  contacttitle VARCHAR(30),
  address      VARCHAR(60),
  city         VARCHAR(15),
  region       VARCHAR(15),
  postalcode   VARCHAR(10),
  country      VARCHAR(15),
  phone        VARCHAR(24),
  fax          VARCHAR(24),
  homepage     TEXT
);

CREATE TABLE products (
  productid       SERIAL PRIMARY KEY,
  productname     VARCHAR(40) NOT NULL,
  supplierid      INT REFERENCES suppliers(supplierid),
  categoryid      INT REFERENCES categories(categoryid),
  quantityperunit VARCHAR(20),
  unitprice       NUMERIC(10,2) DEFAULT 0,
  unitsinstock    SMALLINT DEFAULT 0,
  unitsonorder    SMALLINT DEFAULT 0,
  reorderlevel    SMALLINT DEFAULT 0,
  discontinued    BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE shippers (
  shipperid   SERIAL PRIMARY KEY,
  companyname VARCHAR(40) NOT NULL,
  phone       VARCHAR(24)
);

CREATE TABLE region (
  regionid          INT PRIMARY KEY,
  regiondescription VARCHAR(50) NOT NULL
);

CREATE TABLE territories (
  territoryid          VARCHAR(20) PRIMARY KEY,
  territorydescription VARCHAR(50) NOT NULL,
  regionid             INT NOT NULL REFERENCES region(regionid)
);

CREATE TABLE customers (
  customerid   CHAR(5) PRIMARY KEY,
  companyname  VARCHAR(40) NOT NULL,
  contactname  VARCHAR(30),
  contacttitle VARCHAR(30),
  address      VARCHAR(60),
  city         VARCHAR(15),
  region       VARCHAR(15),
  postalcode   VARCHAR(10),
  country      VARCHAR(15),
  phone        VARCHAR(24),
  fax          VARCHAR(24)
);

CREATE TABLE customerdemographics (
  customertypeid CHAR(10) PRIMARY KEY,
  customerdesc   TEXT
);

CREATE TABLE customercustomerdemo (
  customerid     CHAR(5)   NOT NULL REFERENCES customers(customerid),
  customertypeid CHAR(10)  NOT NULL REFERENCES customerdemographics(customertypeid),
  PRIMARY KEY (customerid, customertypeid)
);

CREATE TABLE employees (
  employeeid      SERIAL PRIMARY KEY,
  lastname        VARCHAR(20) NOT NULL,
  firstname       VARCHAR(10) NOT NULL,
  title           VARCHAR(30),
  titleofcourtesy VARCHAR(25),
  birthdate       DATE,
  hiredate        DATE,
  address         VARCHAR(60),
  city            VARCHAR(15),
  region          VARCHAR(15),
  postalcode      VARCHAR(10),
  country         VARCHAR(15),
  homephone       VARCHAR(24),
  extension       VARCHAR(4),
  notes           TEXT,
  reportsto       INT REFERENCES employees(employeeid)
);

CREATE TABLE employeeterritories (
  employeeid  INT         NOT NULL REFERENCES employees(employeeid),
  territoryid VARCHAR(20) NOT NULL REFERENCES territories(territoryid),
  PRIMARY KEY (employeeid, territoryid)
);

CREATE TABLE orders (
  orderid        SERIAL PRIMARY KEY,
  customerid     CHAR(5) REFERENCES customers(customerid),
  employeeid     INT     REFERENCES employees(employeeid),
  orderdate      DATE,
  requireddate   DATE,
  shippeddate    DATE,
  shipvia        INT     REFERENCES shippers(shipperid),
  freight        NUMERIC(10,2) DEFAULT 0,
  shipname       VARCHAR(40),
  shipaddress    VARCHAR(60),
  shipcity       VARCHAR(15),
  shipregion     VARCHAR(15),
  shippostalcode VARCHAR(10),
  shipcountry    VARCHAR(15)
);

CREATE TABLE order_details (
  orderid   INT           NOT NULL REFERENCES orders(orderid),
  productid INT           NOT NULL REFERENCES products(productid),
  unitprice NUMERIC(10,2) NOT NULL DEFAULT 0,
  quantity  SMALLINT      NOT NULL DEFAULT 1,
  discount  REAL          NOT NULL DEFAULT 0,
  PRIMARY KEY (orderid, productid)
);

SQL;

try {
    $db->exec($schema);
    echo "  Schema created (13 tables).\n\n";
} catch (Throwable $e) {
    die("  Schema error: " . $e->getMessage() . "\n");
}

// ── CSV loader ────────────────────────────────────────────────────────────────

$DATA_DIR = __DIR__ . '/data';

if (!is_dir($DATA_DIR)) {
    die("  ERROR: data/ directory not found next to this script.\n");
}

function read_csv(string $path): array
{
    $text  = file_get_contents($path);
    $lines = array_values(array_filter(
        explode("\n", str_replace(["\r\n", "\r"], "\n", $text))
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

function nil(mixed $v): mixed
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
        $cols  = array_keys($row);
        $clist = implode(', ', array_map(fn($c) => "\"$c\"", $cols));
        $plist = implode(', ', array_map(fn($c) => ":$c", $cols));
        try {
            $db->prepare("INSERT INTO \"$table\" ($clist) VALUES ($plist) ON CONFLICT DO NOTHING")
               ->execute($row);
            $inserted++;
        } catch (Throwable $e) {
            echo "    ⚠  row skipped: " . explode("\n", $e->getMessage())[0] . "\n";
            $skipped++;
        }
    }
    echo "  ✓  $table: $inserted inserted" . ($skipped ? ", $skipped skipped" : "") . "\n";
}

function csv(string $name): array
{
    global $DATA_DIR;
    $path = "$DATA_DIR/$name.csv";
    if (!file_exists($path)) { echo "  ⚠  $name.csv not found, skipping\n"; return []; }
    return read_csv($path);
}

// ── Seed in FK-safe order ─────────────────────────────────────────────────────

echo "Loading CSV data…\n";

load($db, 'categories', csv('categories'), fn($r) => [
    'categoryid'   => (int)$r['categoryid'],
    'categoryname' => $r['categoryname'],
    'description'  => nil($r['description']),
]);

load($db, 'suppliers', csv('suppliers'), fn($r) => [
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

load($db, 'shippers', csv('shippers'), fn($r) => [
    'shipperid'   => (int)$r['shipperid'],
    'companyname' => $r['companyname'],
    'phone'       => nil($r['phone']),
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
    'supplierid'      => $r['supplierid']   ? (int)$r['supplierid']   : null,
    'categoryid'      => $r['categoryid']   ? (int)$r['categoryid']   : null,
    'quantityperunit' => nil($r['quantityperunit']),
    'unitprice'       => $r['unitprice']    ? (float)$r['unitprice']  : 0,
    'unitsinstock'    => $r['unitsinstock'] ? (int)$r['unitsinstock'] : 0,
    'unitsonorder'    => $r['unitsonorder'] ? (int)$r['unitsonorder'] : 0,
    'reorderlevel'    => $r['reorderlevel'] ? (int)$r['reorderlevel'] : 0,
    'discontinued'    => ($r['discontinued'] === '1' || strtolower($r['discontinued']) === 'true'),
]);

load($db, 'customers', csv('customers'), fn($r) => [
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

load($db, 'customerdemographics', csv('customerdemographics'), fn($r) => [
    'customertypeid' => trim($r['customertypeid']),
    'customerdesc'   => nil($r['customerdesc'] ?? ''),
]);

load($db, 'customercustomerdemo', csv('customercustomerdemo'), function ($r) {
    if (!trim($r['customerid'] ?? '') || !trim($r['customertypeid'] ?? '')) return null;
    return ['customerid' => trim($r['customerid']), 'customertypeid' => trim($r['customertypeid'])];
});

// Employees: insert without reportsto first, then patch self-ref FK
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
    $cols  = array_keys($row);
    $clist = implode(', ', array_map(fn($c) => "\"$c\"", $cols));
    $plist = implode(', ', array_map(fn($c) => ":$c", $cols));
    try {
        $db->prepare("INSERT INTO employees ($clist) VALUES ($plist) ON CONFLICT DO NOTHING")
           ->execute($row);
        $emp_ins++;
    } catch (Throwable $e) {
        echo "    ⚠  employee row skipped: " . explode("\n", $e->getMessage())[0] . "\n";
    }
}
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
    'customerid'     => nil(trim($r['customerid'])),
    'employeeid'     => $r['employeeid']  ? (int)$r['employeeid']  : null,
    'orderdate'      => nil($r['orderdate']),
    'requireddate'   => nil($r['requireddate']),
    'shippeddate'    => nil($r['shippeddate']),
    'shipvia'        => $r['shipvia']     ? (int)$r['shipvia']     : null,
    'freight'        => $r['freight']     ? (float)$r['freight']   : 0,
    'shipname'       => nil($r['shipname']),
    'shipaddress'    => nil($r['shipaddress']),
    'shipcity'       => nil($r['shipcity']),
    'shipregion'     => nil($r['shipregion']),
    'shippostalcode' => nil($r['shippostalcode']),
    'shipcountry'    => nil($r['shipcountry']),
]);

load($db, 'order_details', csv('order_details'), fn($r) => [
    'orderid'   => (int)$r['orderid'],
    'productid' => (int)$r['productid'],
    'unitprice' => (float)$r['unitprice'],
    'quantity'  => (int)$r['quantity'],
    'discount'  => (float)$r['discount'],
]);

echo "\nDone!\n";