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

### 3. Customer ID Generator (Optional)

The `customers` table uses a 5-character text primary key. To automatically generate unique IDs for new customers:

```bash
# Apply the database trigger
# This is already included if you ran setup.php recently,
# but can be applied manually to existing databases:
# Use any SQL tool to run: scripts/customer_id_trigger.sql
```

A PHP helper `generate_unique_customer_id(PDO $db)` is also available in `src/db.php`.

### 4. Start the server

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

### UI Routes

| Route            | Description                                              |
|------------------|----------------------------------------------------------|
| `GET /view`      | Main dashboard with virtualised table explorer           |
| `GET /table/:t`  | Dedicated view for a specific table (e.g. `/table/orders`) |
| `GET /reports`   | Monthly sales reports (grouped by customer, region, or employee) |
| `GET /bonuses`   | Quarterly employee bonus report (0.9% of total sales)    |
| `GET /insert`    | Web interface for bulk-inserting JSON or CSV data        |

### API Routes

| Route                         | Method | Description                                           |
|-------------------------------|--------|-------------------------------------------------------|
| `/api/table/:table`           | GET    | Paginated table data (`?page=1&limit=100&sort=id`)    |
| `/api/reports/sales`          | GET    | Sales stats (`?by=customer|region|employee`)         |
| `/api/reports/top-products`   | GET    | Top 5 products by region/year                         |
| `/api/reports/bonuses`        | GET    | Quarterly bonus data                                  |
| `/api/insert/:table`          | POST   | Insert rows: `{ "format": "json"|"csv", "data": "" }` |
| `/api/health`                 | GET    | System status check                                   |

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
    customer_id_trigger.sql    SQL for random Customer ID generation
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