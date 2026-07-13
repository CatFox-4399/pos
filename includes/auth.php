<?php
require_once __DIR__ . '/config.php';

function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function isCashier() {
    return isLoggedIn() && ($_SESSION['role'] === 'cashier' || $_SESSION['role'] === 'admin');
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
    checkSessionTimeout();
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/cashier/pos.php');
        exit;
    }
}

function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            logout();
        }
    }
    $_SESSION['last_activity'] = time();
}

function login($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        startSecureSession();
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['last_activity'] = time();
        auditLog($user['id'], 'login', 'User logged in');
        return $user;
    }
    return false;
}

function logout() {
    startSecureSession();
    if (isset($_SESSION['user_id'])) {
        auditLog($_SESSION['user_id'], 'logout', 'User logged out');
    }
    session_destroy();
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

function auditLog($userId, $action, $description = '') {
    global $pdo;
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $description, $ip]);
    } catch (Exception $e) {
        // Silent fail for audit log
    }
}

function getSetting($key) {
    global $pdo;
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $val = $row ? $row['setting_value'] : null;
        $cache[$key] = $val;
        return $val;
    } catch (Exception $e) {
        // Table may not exist yet — return built-in defaults
        $defaults = [
            'store_name'           => 'SuperMart POS',
            'store_address'        => '123 Main Street',
            'store_phone'          => '',
            'tax_rate'             => '6',
            'receipt_footer'       => 'Thank you for shopping with us!',
            'auto_cash_drawer'     => '1',
            'cashier_manual_drawer'=> '0',
            'drawer_cooldown'      => '30',
            'currency'             => 'RM',
            'printer_name'         => 'EPSON TM-T82',
        ];
        return $defaults[$key] ?? null;
    }
}

function generateTransactionNo() {
    return 'TXN' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generateBarcode() {
    return '8' . date('ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function formatCurrency($amount) {
    $currency = getSetting('currency') ?? 'RM';
    return $currency . ' ' . number_format($amount, 2);
}

function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
