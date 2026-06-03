# Northwind (PHP)

Northwind database viewer and data inserter built with **PHP 8.1+** and **PostgreSQL** (PDO).
No framework, no Composer dependencies — just PHP and a PostgreSQL connection.

**Live demo:** https://prakse-mem10l.wasmer.app/view

## Requirements

- PHP 8.1+ with `pdo_pgsql` extension enabled
- PostgreSQL database (pre-configured for **Neon** — no local install needed)
- A web server (Apache with `mod_rewrite`, Nginx, or PHP's built-in server)

## Setup

### 1. Configure the database

Copy `.env.example` to `.env` and fill in your Neon connection string:

```bash
cp .env.example .env
```

### 2. Initialize the database

Run the setup script to create the schema (13 tables) and load all CSV seed data:

```bash
php setup.php
```

To verify the connection and list tables without seeding:

```bash
php test_db.php
```

### 3. Start the server

**PHP built-in server (quickest for local dev):**

```bash
php -S localhost:8000 router.php
```

**Apache** — point `DocumentRoot` to `public/`, ensure `mod_rewrite` is on.
The included `.htaccess` handles routing automatically.

**Nginx** — add a `try_files` rule:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Open **http://localhost:8000**

## Routes

| Route                      | Description                                        |
|----------------------------|----------------------------------------------------|
| `GET /view`                | Virtualised table viewer                           |
| `GET /reports`             | Monthly sales reports by customer or region       |
| `GET /insert`              | Paste JSON or CSV to bulk-insert into any table    |
| `POST /api/insert/:table`  | JSON API: `{ "format": "json"\|"csv", "data": "" }`|
| `GET /api/table/:table`    | Paginated JSON rows (`?page=1&limit=100`)          |
| `GET /api/reports/sales`   | JSON API for monthly sales data                    |
| `GET /api/health`          | `{ "status": "ok" }`                              |

## Tables

The database contains 13 Northwind tables, seeded from CSV files in `data/`:

`categories`, `customers`, `customerdemographics`, `customercustomerdemo`,
`employees`, `employeeterritories`, `orders`, `order_details`,
`products`, `region`, `shippers`, `suppliers`, `territories`

## Project structure

```
northwind-php/
  data/                        CSV seed files (one per table, 13 total)
  scripts/
    seed.sql                   CREATE TABLE statements for all 13 tables
    load-csv.php               CLI script to load CSVs into PostgreSQL
  src/
    db.php                     PDO connection (singleton, reads .env)
    schema.php                 TABLES constant — columns & required fields
  public/
    .htaccess                  Apache rewrite rules
    index.php                  Front controller, router, and API handlers
    view.php                   Virtualised table viewer UI
    insert.php                 Bulk-insert UI (JSON or CSV input)
  router.php                   Dev-server router (php -S)
  setup.php                    One-shot: create schema + seed all CSVs
  test_db.php                  Quick connection check + table list
  .env.example
  .gitignore
```