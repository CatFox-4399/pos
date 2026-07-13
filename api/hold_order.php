<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';
    if ($action === 'list') {
        $stmt = $pdo->prepare("SELECT * FROM hold_orders WHERE user_id=? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $orders = $stmt->fetchAll();
        echo json_encode(['success' => true, 'orders' => $orders]);
    }
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    if ($action === 'hold') {
        $cart  = $data['cart'] ?? [];
        $label = $data['label'] ?? 'Order ' . date('H:i');
        if (empty($cart)) {
            echo json_encode(['success' => false, 'message' => 'Cart is empty']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO hold_orders (user_id, label, cart_data) VALUES (?,?,?)");
        $stmt->execute([$_SESSION['user_id'], $label, json_encode($cart)]);
        echo json_encode(['success' => true, 'message' => 'Order held']);
    } elseif ($action === 'resume') {
        $id = (int)($data['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM hold_orders WHERE id=? AND user_id=?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $order = $stmt->fetch();
        if ($order) {
            $pdo->prepare("DELETE FROM hold_orders WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true, 'cart' => json_decode($order['cart_data'], true)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Hold order not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method not allowed']);
