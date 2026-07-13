<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Audit Log - ' . APP_NAME;

$logs = $pdo->query("SELECT a.*, u.username FROM audit_logs a JOIN users u ON a.user_id=u.id ORDER BY a.created_at DESC LIMIT 500")->fetchAll();
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
<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4"><i class="bi bi-journal-text me-2 text-primary"></i>Audit Log</h4>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <input type="text" id="tableSearch" class="form-control form-control-sm w-auto d-inline-block" placeholder="Search logs...">
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light"><tr><th>#</th><th>User</th><th>Action</th><th>Description</th><th>IP</th><th>Time</th></tr></thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= $log['id'] ?></td>
                    <td><span class="fw-semibold"><?= htmlspecialchars($log['username']) ?></span></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($log['action']) ?></span></td>
                    <td><?= htmlspecialchars($log['description']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($log['ip_address']) ?></td>
                    <td class="text-muted"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?><tr><td colspan="6" class="text-center py-4 text-muted">No audit records</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body></html>
