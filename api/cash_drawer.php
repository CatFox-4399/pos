<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data   = json_decode(file_get_contents('php://input'), true);
$method = $data['method'] ?? 'MANUAL';
$txnId  = $data['transaction_id'] ?? null;

// Check cooldown
$cooldown = (int)(getSetting('drawer_cooldown') ?? 30);
$stmt = $pdo->prepare("SELECT created_at FROM cash_drawer_logs WHERE user_id=? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$last = $stmt->fetch();
if ($last && (time() - strtotime($last['created_at'])) < $cooldown) {
    $remaining = $cooldown - (time() - strtotime($last['created_at']));
    echo json_encode(['success' => false, 'message' => "Cooldown active. Wait {$remaining}s."]);
    exit;
}

// Permission check for manual open by cashier
if ($method === 'MANUAL' && !isAdmin()) {
    $allowed = getSetting('cashier_manual_drawer') ?? '0';
    if ($allowed !== '1') {
        echo json_encode(['success' => false, 'message' => 'Manual drawer open requires admin permission']);
        exit;
    }
}

// Log the drawer open
$stmt = $pdo->prepare("INSERT INTO cash_drawer_logs (user_id, transaction_id, action_type, method) VALUES (?,?,'open',?)");
$stmt->execute([$_SESSION['user_id'], $txnId, $method]);

auditLog($_SESSION['user_id'], 'cash_drawer_open', "Drawer opened via $method" . ($txnId ? " for TXN #$txnId" : ""));

// ESC/POS cash drawer command (sent to printer - implementation depends on printer setup)
// Standard ESC/POS: ESC p m t1 t2
// This would be sent to the printer port in a real implementation
// Here we log and return success for the frontend to handle

echo json_encode([
    'success' => true,
    'message' => 'Cash drawer opened',
    'escpos_command' => base64_encode("\x1B\x70\x00\x19\xFA") // ESC p 0 25 250
]);
