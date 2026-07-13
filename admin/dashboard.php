<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Dashboard - ' . APP_NAME;

// Stats
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE role='cashier'");
$stats['cashiers'] = $stmt->fetch()['cnt'];
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM products WHERE status='active'");
$stats['products'] = $stmt->fetch()['cnt'];
$stmt = $pdo->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as total FROM transactions WHERE DATE(created_at)=CURDATE() AND status='completed'");
$today = $stmt->fetch();
$stats['today_txn'] = $today['cnt'];
$stats['today_total'] = $today['total'];
$stmt = $pdo->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as total FROM transactions WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE()) AND status='completed'");
$month = $stmt->fetch();
$stats['month_txn'] = $month['cnt'];
$stats['month_total'] = $month['total'];

// Recent Transactions
$recent = $pdo->query("SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id=u.id ORDER BY t.created_at DESC LIMIT 10")->fetchAll();

// Top Products
$topProducts = $pdo->query("SELECT p.product_name, SUM(ti.qty) as qty_sold, SUM(ti.total) as revenue FROM transaction_items ti JOIN products p ON ti.product_id=p.id GROUP BY p.id ORDER BY qty_sold DESC LIMIT 5")->fetchAll();
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
    <div class="d-flex align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard</h4>
        <span class="ms-auto text-muted small"><i class="bi bi-calendar3 me-1"></i><?= date('l, d F Y') ?></span>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card stat-card text-white h-100" style="background:linear-gradient(135deg,#0d6efd,#0a58ca)">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="small opacity-75">Today's Sales</div>
                            <div class="fs-4 fw-bold">RM <?= number_format($stats['today_total'], 2) ?></div>
                            <div class="small opacity-75"><?= $stats['today_txn'] ?> transactions</div>
                        </div>
                        <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card text-white h-100" style="background:linear-gradient(135deg,#198754,#146c43)">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="small opacity-75">Monthly Sales</div>
                            <div class="fs-4 fw-bold">RM <?= number_format($stats['month_total'], 2) ?></div>
                            <div class="small opacity-75"><?= $stats['month_txn'] ?> transactions</div>
                        </div>
                        <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card text-white h-100" style="background:linear-gradient(135deg,#fd7e14,#e85d04)">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="small opacity-75">Active Products</div>
                            <div class="fs-4 fw-bold"><?= $stats['products'] ?></div>
                            <div class="small opacity-75">In inventory</div>
                        </div>
                        <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card text-white h-100" style="background:linear-gradient(135deg,#6f42c1,#59359a)">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="small opacity-75">Cashiers</div>
                            <div class="fs-4 fw-bold"><?= $stats['cashiers'] ?></div>
                            <div class="small opacity-75">Active staff</div>
                        </div>
                        <div class="stat-icon"><i class="bi bi-people"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Recent Transactions -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3 d-flex align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-primary"></i>Recent Transactions</h6>
                    <a href="<?= APP_URL ?>/admin/reports.php" class="ms-auto btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light"><tr>
                                <th>Txn No.</th><th>Cashier</th><th>Method</th><th>Total</th><th>Date</th><th>Status</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($recent as $txn): ?>
                            <tr>
                                <td><code class="small"><?= htmlspecialchars($txn['transaction_no']) ?></code></td>
                                <td><?= htmlspecialchars($txn['username']) ?></td>
                                <td><span class="badge bg-info text-dark"><?= strtoupper(str_replace('_', ' ', $txn['payment_method'])) ?></span></td>
                                <td class="fw-semibold">RM <?= number_format($txn['total'], 2) ?></td>
                                <td class="text-muted small"><?= date('d/m/y H:i', strtotime($txn['created_at'])) ?></td>
                                <td><span class="badge <?= $txn['status']==='completed' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($txn['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No transactions yet</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Top Selling Products</h6>
                </div>
                <div class="card-body">
                    <?php foreach ($topProducts as $i => $p): ?>
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-primary me-2"><?= $i+1 ?></span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small"><?= htmlspecialchars($p['product_name']) ?></div>
                            <div class="text-muted" style="font-size:0.75rem"><?= $p['qty_sold'] ?> units sold</div>
                        </div>
                        <span class="text-success fw-bold small">RM <?= number_format($p['revenue'], 2) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($topProducts)): ?>
                    <p class="text-muted text-center py-3">No sales data yet</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-lightning me-2 text-warning"></i>Quick Actions</h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="<?= APP_URL ?>/cashier/pos.php" class="btn btn-primary btn-sm"><i class="bi bi-cash-register me-2"></i>Open POS</a>
                    <a href="<?= APP_URL ?>/admin/products.php?action=add" class="btn btn-outline-success btn-sm"><i class="bi bi-plus-circle me-2"></i>Add Product</a>
                    <a href="<?= APP_URL ?>/admin/users.php?action=add" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-plus me-2"></i>Add Cashier</a>
                    <a href="<?= APP_URL ?>/admin/reports.php" class="btn btn-outline-info btn-sm"><i class="bi bi-bar-chart me-2"></i>View Reports</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body></html>
