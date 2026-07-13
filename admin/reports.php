<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Reports - ' . APP_NAME;

$type = $_GET['type'] ?? 'daily';
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

// Daily Sales
$daily = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as txn_count, SUM(total) as revenue, SUM(discount) as discounts, SUM(tax) as taxes FROM transactions WHERE status='completed' GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 30")->fetchAll();

// Payment Method Summary
$paymentSummary = $pdo->query("SELECT payment_method, COUNT(*) as cnt, SUM(total) as total FROM transactions WHERE status='completed' AND DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY payment_method")->fetchAll();

// Cashier Performance
$cashierPerf = $pdo->query("SELECT u.username, COUNT(t.id) as txn_count, COALESCE(SUM(t.total),0) as revenue FROM users u LEFT JOIN transactions t ON u.id=t.user_id AND t.status='completed' AND DATE(t.created_at) BETWEEN '$dateFrom' AND '$dateTo' WHERE u.role='cashier' GROUP BY u.id ORDER BY revenue DESC")->fetchAll();

// Top Products
$topProducts = $pdo->query("SELECT p.product_name, p.category, SUM(ti.qty) as qty_sold, SUM(ti.total) as revenue FROM transaction_items ti JOIN products p ON ti.product_id=p.id JOIN transactions t ON ti.transaction_id=t.id WHERE t.status='completed' AND DATE(t.created_at) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY p.id ORDER BY qty_sold DESC LIMIT 20")->fetchAll();

// Period Summary
$stmt = $pdo->prepare("SELECT COUNT(*) as txns, COALESCE(SUM(total),0) as revenue, COALESCE(SUM(discount),0) as discounts, COALESCE(SUM(tax),0) as taxes FROM transactions WHERE status='completed' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$dateFrom, $dateTo]);
$summary = $stmt->fetch();
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
        <h4 class="fw-bold mb-0"><i class="bi bi-bar-chart me-2 text-primary"></i>Reports</h4>
        <button class="ms-auto btn btn-outline-secondary btn-sm no-print" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">From Date</label>
                    <input type="date" name="from" class="form-control" value="<?= $dateFrom ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">To Date</label>
                    <input type="date" name="to" class="form-control" value="<?= $dateTo ?>">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100"><i class="bi bi-filter me-1"></i>Filter</button>
                </div>
                <div class="col-md-4 text-muted small">
                    Period: <?= date('d M Y', strtotime($dateFrom)) ?> – <?= date('d M Y', strtotime($dateTo)) ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-2 fw-bold text-primary">RM <?= number_format($summary['revenue'],2) ?></div>
                <div class="text-muted small">Total Revenue</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-2 fw-bold text-success"><?= $summary['txns'] ?></div>
                <div class="text-muted small">Transactions</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-2 fw-bold text-warning">RM <?= number_format($summary['discounts'],2) ?></div>
                <div class="text-muted small">Total Discounts</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-2 fw-bold text-info">RM <?= number_format($summary['taxes'],2) ?></div>
                <div class="text-muted small">Tax Collected</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Daily Sales -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3"><h6 class="fw-bold mb-0"><i class="bi bi-calendar-day me-2 text-primary"></i>Daily Sales (Last 30 Days)</h6></div>
                <div class="table-responsive" style="max-height:300px;overflow-y:auto;">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light sticky-top"><tr><th>Date</th><th>Txns</th><th>Revenue</th><th>Discount</th></tr></thead>
                        <tbody>
                        <?php foreach ($daily as $d): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($d['date'])) ?></td>
                            <td><?= $d['txn_count'] ?></td>
                            <td class="fw-semibold text-success">RM <?= number_format($d['revenue'],2) ?></td>
                            <td class="text-muted">RM <?= number_format($d['discounts'],2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($daily)): ?><tr><td colspan="4" class="text-center text-muted py-3">No data</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3"><h6 class="fw-bold mb-0"><i class="bi bi-credit-card me-2 text-info"></i>Payment Methods</h6></div>
                <div class="card-body">
                <?php
                $totalRev = array_sum(array_column($paymentSummary, 'total')) ?: 1;
                foreach ($paymentSummary as $pm):
                    $pct = round($pm['total'] / $totalRev * 100);
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-semibold small"><?= strtoupper(str_replace('_',' ',$pm['payment_method'])) ?></span>
                        <span class="text-muted small"><?= $pm['cnt'] ?> txns · RM <?= number_format($pm['total'],2) ?></span>
                    </div>
                    <div class="progress" style="height:8px"><div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($paymentSummary)): ?><p class="text-muted text-center py-3">No data for period</p><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cashier Performance -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3"><h6 class="fw-bold mb-0"><i class="bi bi-person-badge me-2 text-success"></i>Cashier Performance</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Cashier</th><th>Transactions</th><th>Revenue</th></tr></thead>
                        <tbody>
                        <?php foreach ($cashierPerf as $c): ?>
                        <tr>
                            <td><i class="bi bi-person-circle me-1 text-muted"></i><?= htmlspecialchars($c['username']) ?></td>
                            <td><?= $c['txn_count'] ?></td>
                            <td class="fw-semibold text-success">RM <?= number_format($c['revenue'],2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3"><h6 class="fw-bold mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Top Selling Products</h6></div>
                <div class="table-responsive" style="max-height:300px;overflow-y:auto;">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light sticky-top"><tr><th>#</th><th>Product</th><th>Qty</th><th>Revenue</th></tr></thead>
                        <tbody>
                        <?php foreach ($topProducts as $i => $p): ?>
                        <tr>
                            <td><span class="badge bg-primary"><?= $i+1 ?></span></td>
                            <td><?= htmlspecialchars($p['product_name']) ?><br><span class="text-muted small"><?= $p['category'] ?></span></td>
                            <td><?= $p['qty_sold'] ?></td>
                            <td class="text-success fw-semibold">RM <?= number_format($p['revenue'],2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($topProducts)): ?><tr><td colspan="4" class="text-center text-muted py-3">No data</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body></html>
