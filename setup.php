<?php
/**
 * SuperMart POS - Setup / Install Script
 * Run this ONCE in your browser: http://localhost/pos/setup.php
 * Delete or rename this file after setup is complete.
 */

// ── Config (edit these if needed) ──────────────────────────────────────────
$dbHost   = 'localhost';
$dbUser   = 'root';
$dbPass   = '';
$dbName   = 'supermarket_pos';
$appUrl   = 'http://localhost/pos';

$adminUser = 'admin';
$adminPass = 'admin123';
// ───────────────────────────────────────────────────────────────────────────

$errors = [];
$steps  = [];

try {
    // Step 1: Connect (no DB selected yet)
    $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $steps[] = ['ok', 'Connected to MySQL'];

    // Step 2: Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");
    $steps[] = ['ok', "Database `$dbName` ready"];

    // Step 3: Create tables
    $sql = "
    DROP TABLE IF EXISTS audit_logs;
    DROP TABLE IF EXISTS cash_drawer_logs;
    DROP TABLE IF EXISTS hold_orders;
    DROP TABLE IF EXISTS transaction_items;
    DROP TABLE IF EXISTS transactions;
    DROP TABLE IF EXISTS system_settings;
    DROP TABLE IF EXISTS products;
    DROP TABLE IF EXISTS users;

    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin','cashier') NOT NULL DEFAULT 'cashier',
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        barcode VARCHAR(100) UNIQUE NOT NULL,
        sku VARCHAR(100),
        product_name VARCHAR(255) NOT NULL,
        category VARCHAR(100),
        brand VARCHAR(100),
        selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        unit VARCHAR(50) DEFAULT 'pcs',
        image VARCHAR(255),
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_no VARCHAR(50) UNIQUE NOT NULL,
        user_id INT NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        tax DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        payment_method ENUM('cash','credit_card','debit_card','duitnow_qr','tng_ewallet','grabpay','boost') NOT NULL,
        cash_received DECIMAL(10,2) DEFAULT 0.00,
        change_amount DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('completed','cancelled','refunded') DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE transaction_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_id INT NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        qty INT NOT NULL DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        discount DECIMAL(10,2) DEFAULT 0.00,
        total DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (transaction_id) REFERENCES transactions(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE hold_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        label VARCHAR(100),
        cart_data LONGTEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE cash_drawer_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        transaction_id INT DEFAULT NULL,
        action_type ENUM('open','close') NOT NULL DEFAULT 'open',
        method ENUM('AUTO','MANUAL') NOT NULL DEFAULT 'AUTO',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    foreach (array_filter(array_map('trim', explode(';', $sql))) as $q) {
        $pdo->exec($q);
    }
    $steps[] = ['ok', 'All tables created'];

    // Step 4: Insert default settings
    $settings = [
        'store_name'            => 'SuperMart POS',
        'store_address'         => '123 Main Street, Kuala Lumpur',
        'store_phone'           => '+60 3-1234 5678',
        'tax_rate'              => '6',
        'receipt_footer'        => 'Thank you for shopping with us!',
        'auto_cash_drawer'      => '1',
        'cashier_manual_drawer' => '0',
        'drawer_cooldown'       => '30',
        'currency'              => 'RM',
        'printer_name'          => 'EPSON TM-T82',
    ];
    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $k => $v) $stmt->execute([$k, $v]);
    $steps[] = ['ok', 'Default settings inserted (' . count($settings) . ' settings)'];

    // Step 5: Create admin account
    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, 'admin', 'active')");
    $stmt->execute([$adminUser, $hash]);
    $steps[] = ['ok', "Admin account created: <strong>$adminUser</strong> / <strong>$adminPass</strong>"];

    // Step 6: Sample products
    $products = [
        ['8888001001','SKU001','Mineral Water 500ml','Beverages','Spritzer',1.50,0.80,'bottle'],
        ['8888001002','SKU002','White Rice 5kg','Staples','Jasmine',18.90,12.00,'bag'],
        ['8888001003','SKU003','Instant Noodles','Snacks','Mamee',2.50,1.20,'pcs'],
        ['8888001004','SKU004','Teh Tarik 3in1','Beverages','BOH',9.90,5.00,'box'],
        ['8888001005','SKU005','Cooking Oil 1L','Cooking','Knife',7.50,4.50,'bottle'],
        ['8888001006','SKU006','Bread Loaf','Bakery','Gardenia',3.80,2.00,'pcs'],
        ['8888001007','SKU007','Fresh Milk 1L','Dairy','Dutch Lady',6.90,4.20,'bottle'],
        ['8888001008','SKU008','Sugar 1kg','Staples','CSR',3.20,2.00,'bag'],
    ];
    $stmt2 = $pdo->prepare("INSERT INTO products (barcode,sku,product_name,category,brand,selling_price,cost_price,unit,status) VALUES (?,?,?,?,?,?,?,?,'active')");
    foreach ($products as $p) $stmt2->execute($p);
    $steps[] = ['ok', count($products) . ' sample products inserted'];

    // Step 7: Update config hint
    $steps[] = ['info', "Edit <code>includes/config.php</code> if your DB credentials differ from: host=<strong>$dbHost</strong>, user=<strong>$dbUser</strong>, db=<strong>$dbName</strong>"];

    $success = true;

} catch (PDOException $e) {
    $errors[] = $e->getMessage();
    $success = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Setup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:640px">
    <div class="text-center mb-4">
        <i class="bi bi-shop-window text-primary" style="font-size:3rem"></i>
        <h2 class="fw-bold mt-2">SuperMart POS Setup</h2>
        <p class="text-muted">Database initialization</p>
    </div>

    <div class="card border-0 shadow">
        <div class="card-body p-4">
            <?php foreach ($steps as [$type, $msg]): ?>
            <div class="d-flex align-items-start mb-2">
                <span class="me-2 mt-1">
                    <?php if ($type === 'ok'): ?><i class="bi bi-check-circle-fill text-success"></i>
                    <?php elseif ($type === 'info'): ?><i class="bi bi-info-circle-fill text-info"></i>
                    <?php else: ?><i class="bi bi-x-circle-fill text-danger"></i><?php endif; ?>
                </span>
                <span><?= $msg ?></span>
            </div>
            <?php endforeach; ?>

            <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger mt-3"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>

            <?php if ($success ?? false): ?>
            <hr>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Setup complete!</strong> Your POS system is ready.
            </div>
            <div class="d-grid gap-2">
                <a href="<?= $appUrl ?>/login.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
                </a>
            </div>
            <div class="alert alert-warning mt-3 small">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Security:</strong> Delete or rename <code>setup.php</code> after login to prevent re-running setup.
            </div>
            <?php else: ?>
            <hr>
            <div class="alert alert-danger">
                <strong>Setup failed.</strong> Check your database credentials in this file and try again.
            </div>
            <div class="small text-muted">
                Edit the top of <code>setup.php</code>:<br>
                <code>$dbHost</code>, <code>$dbUser</code>, <code>$dbPass</code>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
