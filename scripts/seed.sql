-- Northwind PostgreSQL schema + seed
-- Run: psql $DATABASE_URL -f scripts/seed.sql

DROP TABLE IF EXISTS employeeterritories, order_details, orders, products,
  customercustomerdemo, customerdemographics, customers, employees,
  territories, suppliers, shippers, region, categories CASCADE;

-- ── Tables ────────────────────────────────────────────────────────────────────

CREATE TABLE categories (
  categoryid   SERIAL PRIMARY KEY,
  categoryname VARCHAR(15) NOT NULL,
  description  TEXT,
  picture      BYTEA
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
  customerid     CHAR(5)    NOT NULL REFERENCES customers(customerid),
  customertypeid CHAR(10)   NOT NULL REFERENCES customerdemographics(customertypeid),
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
  reportsto       INT REFERENCES employees(employeeid),
  photopath       VARCHAR(255)
);

CREATE TABLE employeeterritories (
  employeeid  INT          NOT NULL REFERENCES employees(employeeid),
  territoryid VARCHAR(20)  NOT NULL REFERENCES territories(territoryid),
  PRIMARY KEY (employeeid, territoryid)
);

CREATE TABLE orders (
  orderid        SERIAL PRIMARY KEY,
  customerid     CHAR(5)  REFERENCES customers(customerid),
  employeeid     INT      REFERENCES employees(employeeid),
  orderdate      DATE,
  requireddate   DATE,
  shippeddate    DATE,
  shipvia        INT      REFERENCES shippers(shipperid),
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

-- ── Check Constraints ────────────────────────────────────────────────────────

ALTER TABLE products 
  ADD CONSTRAINT chk_unitprice CHECK (unitprice >= 0),
  ADD CONSTRAINT chk_unitsinstock CHECK (unitsinstock >= 0),
  ADD CONSTRAINT chk_unitsonorder CHECK (unitsonorder >= 0),
  ADD CONSTRAINT chk_reorderlevel CHECK (reorderlevel >= 0);

ALTER TABLE order_details
  ADD CONSTRAINT chk_unitprice CHECK (unitprice >= 0),
  ADD CONSTRAINT chk_quantity CHECK (quantity > 0),
  ADD CONSTRAINT chk_discount CHECK (discount >= 0 AND discount <= 1);

ALTER TABLE orders
  ADD CONSTRAINT chk_shippeddate CHECK (shippeddate IS NULL OR shippeddate >= orderdate);

-- ── Indexes ──────────────────────────────────────────────────────────────────

CREATE INDEX idx_products_categoryid ON products(categoryid);
CREATE INDEX idx_products_supplierid ON products(supplierid);
CREATE INDEX idx_orders_customerid ON orders(customerid);
CREATE INDEX idx_orders_employeeid ON orders(employeeid);
CREATE INDEX idx_orders_shipvia    ON orders(shipvia);
CREATE INDEX idx_orders_orderdate  ON orders(orderdate);
CREATE INDEX idx_order_details_orderid   ON order_details(orderid);
CREATE INDEX idx_order_details_productid ON order_details(productid);
CREATE INDEX idx_employees_reportsto ON employees(reportsto);
CREATE INDEX idx_territories_regionid ON territories(regionid);
CREATE INDEX idx_employeeterritories_employeeid  ON employeeterritories(employeeid);
CREATE INDEX idx_employeeterritories_territoryid ON employeeterritories(territoryid);
CREATE INDEX idx_customers_region ON customers(region);
CREATE INDEX idx_customers_country ON customers(country);
