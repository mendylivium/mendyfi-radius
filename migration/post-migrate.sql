-- post-migrate.sql
-- Runs after pgloader migration to fix PostgreSQL-specific schema issues.
-- Designed to be idempotent (safe to re-run).

-- 1. Fix sequence ownership (pgloader sometimes misses this)
DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN
        SELECT c.table_name, c.column_name,
               pg_get_serial_sequence(c.table_name, c.column_name) as seq_name
        FROM information_schema.columns c
        WHERE c.column_default LIKE 'nextval%'
          AND c.table_schema = 'public'
    LOOP
        IF r.seq_name IS NOT NULL THEN
            EXECUTE format('ALTER SEQUENCE %s OWNED BY %I.%I',
                           r.seq_name, r.table_name, r.column_name);
        END IF;
    END LOOP;
END $$;

-- 2. Ensure sequences are ahead of max(id)
DO $$
DECLARE
    r RECORD;
    max_val BIGINT;
BEGIN
    FOR r IN
        SELECT c.table_name, c.column_name,
               pg_get_serial_sequence(c.table_name, c.column_name) as seq_name
        FROM information_schema.columns c
        WHERE c.column_default LIKE 'nextval%'
          AND c.table_schema = 'public'
    LOOP
        IF r.seq_name IS NOT NULL THEN
            EXECUTE format('SELECT COALESCE(MAX(%I), 0) FROM %I', r.column_name, r.table_name) INTO max_val;
            EXECUTE format('SELECT setval(%L, GREATEST(%s, 1))', r.seq_name, max_val);
        END IF;
    END LOOP;
END $$;

-- 3. Add missing indexes that Laravel migrations would create but pgloader might skip
-- Guards check table existence so this script is safe to run on both central and tenant DBs.
DO $$
BEGIN
    IF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'sales_records') THEN
        CREATE INDEX IF NOT EXISTS idx_sales_records_user_id ON sales_records(user_id);
        CREATE INDEX IF NOT EXISTS idx_sales_records_transact_date ON sales_records(transact_date);
        CREATE INDEX IF NOT EXISTS idx_sales_records_profile ON sales_records(hotspot_profile_id);
    END IF;

    IF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'hotspot_vouchers') THEN
        CREATE INDEX IF NOT EXISTS idx_hotspot_vouchers_user_id ON hotspot_vouchers(user_id);
        CREATE INDEX IF NOT EXISTS idx_hotspot_vouchers_code ON hotspot_vouchers(code);
    END IF;

    IF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'resellers') THEN
        CREATE INDEX IF NOT EXISTS idx_resellers_user_id ON resellers(user_id);
    END IF;
END $$;

-- 4. Set search_path for application user
DO $$
BEGIN
    EXECUTE format('ALTER DATABASE %I SET search_path TO public', current_database());
END $$;
