-- ============================================================
-- SuperMart POS System - Database Setup
-- MySQL 8+
-- Run this file ONCE to initialize the database
-- ============================================================

CREATE DATABASE IF NOT EXISTS supermarket_pos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE supermarket_pos;

-- ─── Drop tables in reverse FK order (safe re-run) ───────────
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS cash_drawer_logs;
DROP TABLE IF EXISTS hold_orders;
DROP TABLE IF EXISTS transaction_items;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

-- ─── Users ───────────────────────────────────────────────────
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','cashier') NOT NULL DEFAULT 'cashier',
    status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Products ────────────────────────────────────────────────
CREATE TABLE products (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    barcode       VARCHAR(100) UNIQUE NOT NULL,
    sku           VARCHAR(100),
    product_name  VARCHAR(255) NOT NULL,
    category      VARCHAR(100),
    brand         VARCHAR(100),
    selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cost_price    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    unit          VARCHAR(50) DEFAULT 'pcs',
    image         VARCHAR(255),
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Transactions ─────────────────────────────────────────────
CREATE TABLE transactions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    transaction_no VARCHAR(50) UNIQUE NOT NULL,
    user_id        INT NOT NULL,
    subtotal       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash','credit_card','debit_card','duitnow_qr','tng_ewallet','grabpay','boost') NOT NULL,
    cash_received  DECIMAL(10,2) DEFAULT 0.00,
    change_amount  DECIMAL(10,2) DEFAULT 0.00,
    status         ENUM('completed','cancelled','refunded') DEFAULT 'completed',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Transaction Items ────────────────────────────────────────
CREATE TABLE transaction_items (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    product_id     INT NOT NULL,
    product_name   VARCHAR(255) NOT NULL,
    qty            INT NOT NULL DEFAULT 1,
    price          DECIMAL(10,2) NOT NULL,
    discount       DECIMAL(10,2) DEFAULT 0.00,
    total          DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    FOREIGN KEY (product_id)     REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Hold Orders ──────────────────────────────────────────────
CREATE TABLE hold_orders (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    label      VARCHAR(100),
    cart_data  LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Cash Drawer Logs ─────────────────────────────────────────
CREATE TABLE cash_drawer_logs (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    transaction_id INT DEFAULT NULL,
    action_type    ENUM('open','close') NOT NULL DEFAULT 'open',
    method         ENUM('AUTO','MANUAL') NOT NULL DEFAULT 'AUTO',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Audit Logs ───────────────────────────────────────────────
CREATE TABLE audit_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    action      VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address  VARCHAR(45),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── System Settings ──────────────────────────────────────────
CREATE TABLE system_settings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Default Settings ─────────────────────────────────────────
INSERT INTO system_settings (setting_key, setting_value) VALUES
('store_name',            'SuperMart POS'),
('store_address',         '123 Main Street, Kuala Lumpur'),
('store_phone',           '+60 3-1234 5678'),
('tax_rate',              '6'),
('receipt_footer',        'Thank you for shopping with us!'),
('auto_cash_drawer',      '1'),
('cashier_manual_drawer', '0'),
('drawer_cooldown',       '30'),
('currency',              'RM'),
('printer_name',          'EPSON TM-T82');

-- ─── Default Admin (password: admin123) ───────────────────────
-- Hash generated by PHP: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (username, password, role, status) VALUES
('admin',
 '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77bqiy',
 'admin',
 'active');

-- ─── Sample Products ──────────────────────────────────────────
INSERT INTO products (barcode, sku, product_name, category, brand, selling_price, cost_price, unit, status) VALUES
('8888001001', 'SKU001', 'Mineral Water 500ml',  'Beverages', 'Spritzer', 1.50,  0.80,  'bottle', 'active'),
('8888001002', 'SKU002', 'White Rice 5kg',        'Staples',   'Jasmine',  18.90, 12.00, 'bag',    'active'),
('8888001003', 'SKU003', 'Instant Noodles',       'Snacks',    'Mamee',    2.50,  1.20,  'pcs',    'active'),
('8888001004', 'SKU004', 'Teh Tarik 3in1 Box',    'Beverages', 'BOH',      9.90,  5.00,  'box',    'active'),
('8888001005', 'SKU005', 'Cooking Oil 1L',         'Cooking',   'Knife',    7.50,  4.50,  'bottle', 'active'),
('8888001006', 'SKU006', 'Bread Loaf',             'Bakery',    'Gardenia', 3.80,  2.00,  'pcs',    'active'),
('8888001007', 'SKU007', 'Fresh Milk 1L',          'Dairy',     'Dutch Lady',6.90, 4.20,  'bottle', 'active'),
('8888001008', 'SKU008', 'Sugar 1kg',              'Staples',   'CSR',      3.20,  2.00,  'bag',    'active');
