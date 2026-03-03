-- seed-test-data.sql
-- Creates a realistic mendyfi MySQL dataset for migration testing.
-- Run against a fresh MySQL 8 instance.

-- Central database tables (simplified -- matches mendyfi schema)
CREATE DATABASE IF NOT EXISTS mendyfi_central;
USE mendyfi_central;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tenants (
    id CHAR(36) PRIMARY KEY,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    data JSON
);

CREATE TABLE IF NOT EXISTS domains (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    username VARCHAR(255) DEFAULT NULL,
    tenant_id CHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS app_settings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seed admin user
INSERT INTO users (name, email, password) VALUES
    ('Admin', 'admin@mendyfi.test', '$2y$12$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012');

-- Seed 3 tenants
INSERT INTO tenants (id, data) VALUES
    ('t-alpha-0001-0001-000000000001', '{"plan": "basic"}'),
    ('t-bravo-0002-0002-000000000002', '{"plan": "pro"}'),
    ('t-charlie-003-0003-000000000003', '{"plan": "basic"}');

INSERT INTO domains (domain, username, tenant_id) VALUES
    ('alpha.mendyfi.test', 'alpha', 't-alpha-0001-0001-000000000001'),
    ('bravo.mendyfi.test', 'bravo', 't-bravo-0002-0002-000000000002'),
    ('charlie.mendyfi.test', 'charlie', 't-charlie-003-0003-000000000003');

INSERT INTO app_settings (`key`, value) VALUES
    ('app_name', 'MendyFi'),
    ('timezone', 'America/Guyana');

-- Create tenant databases with sample data
-- Tenant: alpha
CREATE DATABASE IF NOT EXISTS mendyfi_tenant_t_alpha_0001_0001_000000000001;
USE mendyfi_tenant_t_alpha_0001_0001_000000000001;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    logo_path VARCHAR(255) DEFAULT NULL,
    accent_color VARCHAR(7) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS resellers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    contact VARCHAR(255) DEFAULT NULL,
    discount DECIMAL(5,2) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS hotspot_profiles (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) DEFAULT 0,
    uptime_limit INT DEFAULT 0,
    data_limit BIGINT DEFAULT 0,
    validity INT DEFAULT 0,
    shared_users INT DEFAULT 1,
    rate_limit VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS hotspot_vouchers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    code VARCHAR(255) NOT NULL,
    hotspot_profile_id BIGINT DEFAULT NULL,
    reseller_id BIGINT DEFAULT NULL,
    status TINYINT(1) DEFAULT 0,
    mac_address VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(255) DEFAULT NULL,
    router_ip VARCHAR(255) DEFAULT NULL,
    server_name VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hotspot_profile_id) REFERENCES hotspot_profiles(id) ON DELETE SET NULL,
    FOREIGN KEY (reseller_id) REFERENCES resellers(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS sales_records (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    code VARCHAR(255) NOT NULL,
    hotspot_profile_id BIGINT DEFAULT NULL,
    reseller_id BIGINT DEFAULT NULL,
    amount DECIMAL(10,2) DEFAULT 0,
    mac_address VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(255) DEFAULT NULL,
    router_ip VARCHAR(255) DEFAULT NULL,
    server_name VARCHAR(255) DEFAULT NULL,
    transact_date DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hotspot_profile_id) REFERENCES hotspot_profiles(id) ON DELETE SET NULL,
    FOREIGN KEY (reseller_id) REFERENCES resellers(id) ON DELETE SET NULL
);

-- Seed alpha tenant data
INSERT INTO users (name, email, password) VALUES
    ('Alpha Admin', 'admin@alpha.test', '$2y$12$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012');

INSERT INTO hotspot_profiles (user_id, name, price, uptime_limit, data_limit, validity, shared_users) VALUES
    (1, '1 Hour', 100.00, 3600, 524288000, 3600, 1),
    (1, '1 Day', 300.00, 86400, 2147483648, 86400, 2),
    (1, '1 Week', 1500.00, 604800, 10737418240, 604800, 3);

INSERT INTO resellers (user_id, name, contact, discount, is_active) VALUES
    (1, 'John Reseller', '5921234567', 10.00, 1),
    (1, 'Jane Reseller', '5929876543', 15.00, 1);

-- Generate 10 vouchers
INSERT INTO hotspot_vouchers (user_id, code, hotspot_profile_id, reseller_id, status) VALUES
    (1, 'ALPHA-001', 1, NULL, 0), (1, 'ALPHA-002', 1, NULL, 0),
    (1, 'ALPHA-003', 1, 1, 0), (1, 'ALPHA-004', 2, NULL, 1),
    (1, 'ALPHA-005', 2, 1, 1), (1, 'ALPHA-006', 2, 2, 0),
    (1, 'ALPHA-007', 3, NULL, 0), (1, 'ALPHA-008', 3, 2, 1),
    (1, 'ALPHA-009', 1, NULL, 0), (1, 'ALPHA-010', 1, 1, 0);

-- Generate 10 sales records
INSERT INTO sales_records (user_id, code, hotspot_profile_id, reseller_id, amount, mac_address, ip_address, transact_date) VALUES
    (1, 'ALPHA-004', 2, NULL, 300.00, 'AA:BB:CC:DD:EE:01', '10.0.0.101', '2026-02-15 10:30:00'),
    (1, 'ALPHA-005', 2, 1, 270.00, 'AA:BB:CC:DD:EE:02', '10.0.0.102', '2026-02-15 11:00:00'),
    (1, 'ALPHA-008', 3, 2, 1275.00, 'AA:BB:CC:DD:EE:03', '10.0.0.103', '2026-02-16 09:15:00'),
    (1, 'ALPHA-011', 1, NULL, 100.00, 'DA:BB:CC:DD:EE:04', '10.0.0.104', '2026-02-17 14:00:00'),
    (1, 'ALPHA-012', 1, 1, 90.00, 'AA:BB:CC:DD:EE:05', '10.0.0.105', '2026-02-18 08:45:00'),
    (1, 'ALPHA-013', 2, NULL, 300.00, 'AA:BB:CC:DD:EE:06', '10.0.0.106', '2026-02-19 16:20:00'),
    (1, 'ALPHA-014', 3, 2, 1275.00, 'AA:BB:CC:DD:EE:07', '10.0.0.107', '2026-02-20 12:00:00'),
    (1, 'ALPHA-015', 1, NULL, 100.00, 'AA:BB:CC:DD:EE:08', '10.0.0.108', '2026-02-21 09:30:00'),
    (1, 'ALPHA-016', 2, 1, 270.00, 'AA:BB:CC:DD:EE:09', '10.0.0.109', '2026-02-22 13:45:00'),
    (1, 'ALPHA-017', 1, NULL, 100.00, 'AA:BB:CC:DD:EE:10', '10.0.0.110', '2026-02-23 10:00:00');

-- Tenant: bravo (smaller dataset)
CREATE DATABASE IF NOT EXISTS mendyfi_tenant_t_bravo_0002_0002_000000000002;
USE mendyfi_tenant_t_bravo_0002_0002_000000000002;

CREATE TABLE IF NOT EXISTS users LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.users;
CREATE TABLE IF NOT EXISTS resellers LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.resellers;
CREATE TABLE IF NOT EXISTS hotspot_profiles LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.hotspot_profiles;
CREATE TABLE IF NOT EXISTS hotspot_vouchers LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.hotspot_vouchers;
CREATE TABLE IF NOT EXISTS sales_records LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.sales_records;

INSERT INTO users (name, email, password) VALUES
    ('Bravo Admin', 'admin@bravo.test', '$2y$12$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012');

INSERT INTO hotspot_profiles (user_id, name, price, uptime_limit, data_limit, validity, shared_users) VALUES
    (1, '30 Min', 50.00, 1800, 262144000, 1800, 1),
    (1, '3 Hours', 200.00, 10800, 1073741824, 10800, 2);

INSERT INTO hotspot_vouchers (user_id, code, hotspot_profile_id, status) VALUES
    (1, 'BRAVO-001', 1, 0), (1, 'BRAVO-002', 1, 1),
    (1, 'BRAVO-003', 2, 0), (1, 'BRAVO-004', 2, 0),
    (1, 'BRAVO-005', 1, 1);

INSERT INTO sales_records (user_id, code, hotspot_profile_id, amount, mac_address, ip_address, transact_date) VALUES
    (1, 'BRAVO-002', 1, 50.00, 'BB:CC:DD:EE:FF:01', '192.168.1.101', '2026-02-20 10:00:00'),
    (1, 'BRAVO-005', 1, 50.00, 'BB:CC:DD:EE:FF:02', '192.168.1.102', '2026-02-22 14:30:00');

-- Tenant: charlie (empty -- edge case test)
CREATE DATABASE IF NOT EXISTS mendyfi_tenant_t_charlie_003_0003_000000000003;
USE mendyfi_tenant_t_charlie_003_0003_000000000003;

CREATE TABLE IF NOT EXISTS users LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.users;
CREATE TABLE IF NOT EXISTS resellers LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.resellers;
CREATE TABLE IF NOT EXISTS hotspot_profiles LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.hotspot_profiles;
CREATE TABLE IF NOT EXISTS hotspot_vouchers LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.hotspot_vouchers;
CREATE TABLE IF NOT EXISTS sales_records LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.sales_records;

INSERT INTO users (name, email, password) VALUES
    ('Charlie Admin', 'admin@charlie.test', '$2y$12$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012');
