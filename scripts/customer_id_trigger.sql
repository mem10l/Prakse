-- Function to generate a random 5-character string that doesn't exist in customers table
CREATE OR REPLACE FUNCTION generate_unique_customer_id() RETURNS CHAR(5) AS $$
DECLARE
    new_id CHAR(5);
    done BOOLEAN := FALSE;
BEGIN
    WHILE NOT done LOOP
        -- Generate 5 random uppercase letters
        new_id := (
            SELECT string_agg(substring('ABCDEFGHIJKLMNOPQRSTUVWXYZ', (random() * 25 + 1)::integer, 1), '')
            FROM generate_series(1, 5)
        );
        
        -- Check if it exists
        IF NOT EXISTS (SELECT 1 FROM customers WHERE customerid = new_id) THEN
            done := TRUE;
        END IF;
    END LOOP;
    RETURN new_id;
END;
$$ LANGUAGE plpgsql;

-- Trigger function to fill customerid if empty
CREATE OR REPLACE FUNCTION customers_fill_id_trigger_fn() RETURNS TRIGGER AS $$
BEGIN
    IF NEW.customerid IS NULL OR trim(NEW.customerid) = '' THEN
        NEW.customerid := generate_unique_customer_id();
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create the trigger
DROP TRIGGER IF EXISTS trg_customers_fill_id ON customers;
CREATE TRIGGER trg_customers_fill_id
BEFORE INSERT ON customers
FOR EACH ROW
EXECUTE FUNCTION customers_fill_id_trigger_fn();
