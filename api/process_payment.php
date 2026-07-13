<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$cart           = $data['cart'] ?? [];
$paymentMethod  = $data['payment_method'] ?? 'cash';
$subtotal       = (float)($data['subtotal'] ?? 0);
$discount       = (float)($data['discount'] ?? 0);
$tax            = (float)($data['tax'] ?? 0);
$total          = (float)($data['total'] ?? 0);
$cashReceived   = (float)($data['cash_received'] ?? 0);
$changeAmount   = (float)($data['change_amount'] ?? 0);

if (empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

if ($total <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid total']);
    exit;
}

if ($paymentMethod === 'cash' && $cashReceived < $total) {
    echo json_encode(['success' => false, 'message' => 'Insufficient cash received']);
    exit;
}

// Generate unique transaction number
do {
    $txnNo = 'TXN' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("SELECT id FROM transactions WHERE transaction_no=?");
    $stmt->execute([$txnNo]);
} while ($stmt->fetch());

try {
    $pdo->beginTransaction();

    // Insert transaction
    $stmt = $pdo->prepare("INSERT INTO transactions (transaction_no, user_id, subtotal, discount, tax, total, payment_method, cash_received, change_amount, status) VALUES (?,?,?,?,?,?,?,?,?,'completed')");
    $stmt->execute([$txnNo, $_SESSION['user_id'], $subtotal, $discount, $tax, $total, $paymentMethod, $cashReceived, $changeAmount]);
    $txnId = $pdo->lastInsertId();

    // Insert items
    $items = [];
    foreach ($cart as $item) {
        $productId = (int)$item['id'];
        $qty       = (int)$item['qty'];
        $price     = (float)$item['price'];
        $itemDisc  = (float)($item['discount'] ?? 0);
        $itemTotal = ($price * $qty) - $itemDisc;

        $stmt = $pdo->prepare("INSERT INTO transaction_items (transaction_id, product_id, product_name, qty, price, discount, total) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$txnId, $productId, $item['name'], $qty, $price, $itemDisc, $itemTotal]);
        $items[] = ['name' => $item['name'], 'qty' => $qty, 'price' => $price, 'discount' => $itemDisc, 'total' => $itemTotal];
    }

    $pdo->commit();

    auditLog($_SESSION['user_id'], 'transaction', "Transaction $txnNo - RM " . number_format($total, 2));

    echo json_encode([
        'success' => true,
        'transaction' => [
            'id'             => $txnId,
            'transaction_no' => $txnNo,
            'subtotal'       => $subtotal,
            'discount'       => $discount,
            'tax'            => $tax,
            'total'          => $total,
            'payment_method' => $paymentMethod,
            'cash_received'  => $cashReceived,
            'change_amount'  => $changeAmount,
            'created_at'     => date('d/m/Y H:i:s'),
            'items'          => $items,
        ]
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
}
