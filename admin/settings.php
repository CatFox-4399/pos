<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Settings - ' . APP_NAME;

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['store_name','store_address','store_phone','tax_rate','receipt_footer','auto_cash_drawer','cashier_manual_drawer','drawer_cooldown','currency','printer_name'];
    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
    foreach ($keys as $key) {
        $val = $_POST[$key] ?? '';
        $stmt->execute([$key, $val, $val]);
    }
    auditLog($_SESSION['user_id'], 'update_settings', 'System settings updated');
    $message = 'Settings saved successfully.';
}

$settings = [];
$rows = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll();
foreach ($rows as $r) $settings[$r['setting_key']] = $r['setting_value'];

function s($key, $default = '') { global $settings; return htmlspecialchars($settings[$key] ?? $default); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container py-4" style="max-width:800px">
    <h4 class="fw-bold mb-4"><i class="bi bi-sliders me-2 text-primary"></i>System Settings</h4>

    <?php if ($message): ?>
    <div class="alert alert-success alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><i class="bi bi-check-circle me-2"></i><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
        <!-- Store Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-shop me-2"></i>Store Information</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Store Name</label>
                        <input type="text" name="store_name" class="form-control" value="<?= s('store_name','SuperMart POS') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="store_phone" class="form-control" value="<?= s('store_phone') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Address</label>
                        <input type="text" name="store_address" class="form-control" value="<?= s('store_address') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Receipt Footer Message</label>
                        <input type="text" name="receipt_footer" class="form-control" value="<?= s('receipt_footer','Thank you for shopping with us!') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Tax & Currency -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white"><h6 class="mb-0"><i class="bi bi-percent me-2"></i>Tax & Currency</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" step="0.01" min="0" max="100" class="form-control" value="<?= s('tax_rate','6') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Currency Symbol</label>
                        <input type="text" name="currency" class="form-control" value="<?= s('currency','RM') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Cash Drawer -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-warning text-dark"><h6 class="mb-0"><i class="bi bi-safe me-2"></i>Cash Drawer Control</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="auto_cash_drawer" id="autoDrawer" value="1" <?= ($settings['auto_cash_drawer']??'1')==='1'?'checked':'' ?>>
                            <label class="form-check-label fw-semibold" for="autoDrawer">Auto-open drawer after cash payment</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="cashier_manual_drawer" id="cashierDrawer" value="1" <?= ($settings['cashier_manual_drawer']??'0')==='1'?'checked':'' ?>>
                            <label class="form-check-label fw-semibold" for="cashierDrawer">Allow cashier manual drawer open</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Drawer Cooldown (seconds)</label>
                        <input type="number" name="drawer_cooldown" min="0" class="form-control" value="<?= s('drawer_cooldown','30') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Printer -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-secondary text-white"><h6 class="mb-0"><i class="bi bi-printer me-2"></i>Printer</h6></div>
            <div class="card-body">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Printer Name / Model</label>
                    <input type="text" name="printer_name" class="form-control" value="<?= s('printer_name','EPSON TM-T82') ?>">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save me-2"></i>Save Settings</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body></html>
