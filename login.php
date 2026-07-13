<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Login - ' . APP_NAME;

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? APP_URL . '/admin/dashboard.php' : APP_URL . '/cashier/pos.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password.';
    } else {
        $user = login($username, $password);
        if ($user) {
            header('Location: ' . ($user['role'] === 'admin' ? APP_URL . '/admin/dashboard.php' : APP_URL . '/cashier/pos.php'));
            exit;
        } else {
            $error = 'Invalid username or password, or account is inactive.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card fade-in">
        <div class="text-center mb-4">
            <div class="login-logo"><i class="bi bi-shop-window"></i></div>
            <h3 class="fw-bold mt-2"><?= htmlspecialchars(getSetting('store_name') ?? APP_NAME) ?></h3>
            <p class="text-muted">Point of Sale System</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-pos"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control form-control-lg" placeholder="Enter username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="passwordField" class="form-control form-control-lg" placeholder="Enter password" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()"><i class="bi bi-eye" id="eyeIcon"></i></button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 btn-pos-action">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </button>
        </form>

        <div class="text-center mt-3 text-muted small">
            <i class="bi bi-shield-lock me-1"></i>Secure Session Login
        </div>
        <hr>
        <div class="text-center text-muted small">
            Default admin: <code>admin</code> / <code>admin123</code>
        </div>
    </div>
</div>
<script>
function togglePassword() {
    const f = document.getElementById('passwordField');
    const i = document.getElementById('eyeIcon');
    if (f.type === 'password') { f.type = 'text'; i.className = 'bi bi-eye-slash'; }
    else { f.type = 'password'; i.className = 'bi bi-eye'; }
}
</script>
</body>
</html>
