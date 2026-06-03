-- Database optimizations for Northwind

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

-- ── Indexes for Foreign Keys (Postgres doesn't index FKs automatically) ───────

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
