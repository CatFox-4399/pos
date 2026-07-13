<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? APP_URL . '/admin/dashboard.php' : APP_URL . '/cashier/pos.php'));
} else {
    header('Location: ' . APP_URL . '/login.php');
}
exit;
