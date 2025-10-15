-- SPACE NET SaaS - Complete Database Schema
-- File: database/schema.sql
-- Run this file to create the complete database structure

CREATE DATABASE IF NOT EXISTS spacenet_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spacenet_saas;

-- Drop tables if they exist (for clean installation)
DROP TABLE IF EXISTS radacct;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS customer_sessions;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS tenant_packages;
DROP TABLE IF EXISTS package_templates;
DROP TABLE IF EXISTS tenant_users;
DROP TABLE IF EXISTS tenants;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS admin_users;

-- System settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Super admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tenants (ISP Clients) table
CREATE TABLE tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    subdomain VARCHAR(100) UNIQUE NOT NULL,
    contact_person VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    subscription_plan ENUM('standard', 'professional', 'enterprise') DEFAULT 'standard',
    status ENUM('trial', 'active', 'suspended', 'expired') DEFAULT 'trial',
    trial_start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    trial_end_date TIMESTAMP NULL,
    subscription_start_date TIMESTAMP NULL,
    subscription_end_date TIMESTAMP NULL,
    mikrotik_ip VARCHAR(45),
    mikrotik_username VARCHAR(100),
    mikrotik_password VARCHAR(255),
    custom_branding JSON,
    settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_subdomain (subdomain),
    INDEX idx_status (status),
    INDEX idx_trial_end (trial_end_date)
);

-- Tenant users (ISP staff)
CREATE TABLE tenant_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operator') DEFAULT 'operator',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_username (tenant_id, username),
    UNIQUE KEY unique_tenant_email (tenant_id, email),
    INDEX idx_tenant_active (tenant_id, is_active)
);

-- Package templates (default packages that tenants can customize)
CREATE TABLE package_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    duration_type ENUM('hours', 'days', 'months') NOT NULL,
    duration_value INT NOT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    speed_limit INT NOT NULL COMMENT 'Speed limit in Mbps',
    device_limit INT DEFAULT 1,
    is_unlimited BOOLEAN DEFAULT FALSE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active)
);

-- Tenant packages (customized from templates)
CREATE TABLE tenant_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    template_id INT NULL,
    name VARCHAR(255) NOT NULL,
    duration_type ENUM('hours', 'days', 'months') NOT NULL,
    duration_value INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    speed_limit INT NOT NULL COMMENT 'Speed limit in Mbps',
    device_limit INT DEFAULT 1,
    device_multiplier JSON COMMENT 'Price multipliers for multiple devices: {2: 1.5, 3: 2.0, 4: 2.5}',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES package_templates(id) ON DELETE SET NULL,
    INDEX idx_tenant_active (tenant_id, is_active)
);

-- Customer users (end users who buy internet packages)
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    password_hash VARCHAR(255) NULL,
    mac_address VARCHAR(17) NULL,
    device_info JSON NULL,
    status ENUM('active', 'suspended', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_username (tenant_id, username),
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_phone (phone),
    INDEX idx_mac (mac_address)
);

-- Customer sessions/tickets (active internet sessions)
CREATE TABLE customer_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    customer_id INT NOT NULL,
    package_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    session_id VARCHAR(255) NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    duration_minutes INT NULL,
    bytes_in BIGINT DEFAULT 0,
    bytes_out BIGINT DEFAULT 0,
    status ENUM('active', 'expired', 'terminated') DEFAULT 'active',
    payment_reference VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES tenant_packages(id) ON DELETE CASCADE,
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_end_time (end_time),
    INDEX idx_payment_ref (payment_reference)
);

-- Payment transactions
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    customer_id INT NULL,
    transaction_type ENUM('subscription', 'package_purchase') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    payment_method ENUM('mpesa', 'pesapal', 'manual') NOT NULL,
    payment_reference VARCHAR(255) UNIQUE NOT NULL,
    external_reference VARCHAR(255) NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_payment_ref (payment_reference),
    INDEX idx_external_ref (external_reference),
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_created_date (created_at)
);

-- Subscription billing for tenants
CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    plan ENUM('standard', 'professional', 'enterprise') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    billing_cycle ENUM('monthly', 'annually') DEFAULT 'monthly',
    status ENUM('active', 'past_due', 'cancelled') DEFAULT 'active',
    current_period_start TIMESTAMP NOT NULL,
    current_period_end TIMESTAMP NOT NULL,
    next_billing_date TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_next_billing (next_billing_date),
    INDEX idx_status (status)
);

-- RADIUS accounting table (for FreeRADIUS integration)
CREATE TABLE radacct (
    radacctid BIGINT AUTO_INCREMENT PRIMARY KEY,
    acctsessionid VARCHAR(64) NOT NULL DEFAULT '',
    acctuniqueid VARCHAR(32) NOT NULL DEFAULT '',
    username VARCHAR(64) NOT NULL DEFAULT '',
    groupname VARCHAR(64) NOT NULL DEFAULT '',
    realm VARCHAR(64) DEFAULT '',
    nasipaddress VARCHAR(15) NOT NULL DEFAULT '',
    nasportid VARCHAR(15),
    nasporttype VARCHAR(32),
    acctstarttime DATETIME NULL,
    acctupdatetime DATETIME NULL,
    acctstoptime DATETIME NULL,
    acctinterval INT,
    acctsessiontime INT UNSIGNED,
    acctauthentic VARCHAR(32),
    connectinfo_start VARCHAR(50),
    connectinfo_stop VARCHAR(50),
    acctinputoctets BIGINT,
    acctoutputoctets BIGINT,
    calledstationid VARCHAR(50) NOT NULL DEFAULT '',
    callingstationid VARCHAR(50) NOT NULL DEFAULT '',
    acctterminatecause VARCHAR(32) NOT NULL DEFAULT '',
    servicetype VARCHAR(32),
    framedprotocol VARCHAR(32),
    framedipaddress VARCHAR(15) NOT NULL DEFAULT '',
    
    INDEX username (username),
    INDEX framedipaddress (framedipaddress),
    INDEX acctsessionid (acctsessionid),
    INDEX acctuniqueID (acctuniqueid),
    INDEX acctstarttime (acctstarttime),
    INDEX acctstoptime (acctstoptime),
    INDEX nasipaddress (nasipaddress)
);

-- Insert default package templates
INSERT INTO package_templates (name, duration_type, duration_value, base_price, speed_limit, device_limit, description, is_active) VALUES
('1 Hour Basic', 'hours', 1, 10.00, 3, 1, 'Basic 1-hour internet access with 3Mbps speed', 1),
('2 Hours Basic', 'hours', 2, 20.00, 3, 1, 'Extended 2-hour internet access with 3Mbps speed', 1),
('6 Hours Premium', 'hours', 6, 50.00, 5, 1, 'Half-day premium access with 5Mbps speed', 1),
('Daily Standard', 'days', 1, 80.00, 5, 1, 'Full day internet access with 5Mbps speed', 1),
('Weekly Popular', 'days', 7, 250.00, 8, 4, 'Weekly internet package with multi-device support and 8Mbps speed', 1),
('Monthly Unlimited', 'days', 30, 1400.00, 10, 5, 'Monthly unlimited internet with high speed (10Mbps) and up to 5 devices', 1);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('trial_duration_days', '15', 'Free trial duration in days for new ISP clients'),
('standard_plan_price', '1999.00', 'Standard subscription plan monthly price in KES'),
('professional_plan_price', '4999.00', 'Professional subscription plan monthly price in KES'),
('enterprise_plan_price', '9999.00', 'Enterprise subscription plan monthly price in KES'),
('standard_plan_max_users', '1000', 'Maximum users allowed on standard plan'),
('professional_plan_max_users', '5000', 'Maximum users allowed on professional plan'),
('enterprise_plan_max_users', '-1', 'Maximum users allowed on enterprise plan (-1 for unlimited)'),
('mpesa_shortcode', '174379', 'M-Pesa business shortcode for payments'),
('mpesa_environment', 'sandbox', 'M-Pesa environment: sandbox or production'),
('company_name', 'SPACE NET SaaS', 'Company name displayed on the platform'),
('support_email', 'support@spacenet.co.ke', 'Support email address for customer inquiries'),
('support_phone', '+254746971061', 'Support phone number'),
('app_url', 'https://spacenet.co.ke', 'Main application URL'),
('app_version', '1.0.0', 'Current application version'),
('timezone', 'Africa/Nairobi', 'Default timezone for the application'),
('currency', 'KES', 'Default currency (Kenya Shillings)'),
('session_timeout', '7200', 'Session timeout in seconds (2 hours)'),
('max_concurrent_sessions', '3', 'Maximum concurrent sessions per user'),
('backup_retention_days', '30', 'Number of days to retain backup files'),
('log_retention_days', '90', 'Number of days to retain log files'),
('enable_registration', '1', 'Enable new tenant registration (1=enabled, 0=disabled)'),
('maintenance_mode', '0', 'Maintenance mode flag (1=enabled, 0=disabled)');

-- Create indexes for better performance
CREATE INDEX idx_tenants_email ON tenants(email);
CREATE INDEX idx_customers_created ON customers(created_at);
CREATE INDEX idx_sessions_dates ON customer_sessions(start_time, end_time);
CREATE INDEX idx_transactions_dates ON transactions(created_at, status);

-- Create views for common queries
CREATE VIEW active_tenants AS
SELECT t.*, 
       COUNT(DISTINCT c.id) as total_customers,
       COUNT(DISTINCT cs.id) as active_sessions,
       COALESCE(SUM(CASE WHEN tr.status = 'completed' AND tr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN tr.amount ELSE 0 END), 0) as monthly_revenue
FROM tenants t
LEFT JOIN customers c ON t.id = c.tenant_id AND c.status = 'active'
LEFT JOIN customer_sessions cs ON t.id = cs.tenant_id AND cs.status = 'active' AND cs.end_time > NOW()
LEFT JOIN transactions tr ON t.id = tr.tenant_id
WHERE t.status IN ('trial', 'active')
GROUP BY t.id;

-- Create view for package analytics
CREATE VIEW package_analytics AS
SELECT tp.tenant_id,
       tp.id as package_id,
       tp.name,
       tp.price,
       COUNT(cs.id) as total_purchases,
       COUNT(CASE WHEN cs.status = 'active' THEN 1 END) as active_sessions,
       COALESCE(SUM(CASE WHEN t.status = 'completed' THEN t.amount ELSE 0 END), 0) as total_revenue
FROM tenant_packages tp
LEFT JOIN customer_sessions cs ON tp.id = cs.package_id
LEFT JOIN transactions t ON cs.payment_reference = t.payment_reference
WHERE tp.is_active = 1
GROUP BY tp.id;

-- Sample super admin user (password: admin123456)
INSERT INTO admin_users (name, email, password_hash, role) VALUES 
('Super Admin', 'admin@spacenet.co.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');

-- Set proper charset and collation for all tables
ALTER DATABASE spacenet_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Show final table status
SHOW TABLES;

-- Display table row counts
SELECT 
    'package_templates' as table_name, COUNT(*) as row_count FROM package_templates
UNION ALL SELECT 
    'system_settings' as table_name, COUNT(*) as row_count FROM system_settings
UNION ALL SELECT 
    'admin_users' as table_name, COUNT(*) as row_count FROM admin_users;

-- Success message
SELECT 'SPACE NET SaaS database installation completed successfully!' as message;