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
