<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

$barcode = $_GET['barcode'] ?? '';
$query   = $_GET['q'] ?? '';

if ($barcode) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE (barcode=? OR sku=?) AND status='active' LIMIT 1");
    $stmt->execute([$barcode, $barcode]);
    $product = $stmt->fetch();
    if ($product) {
        echo json_encode(['success' => true, 'product' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
} elseif ($query) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE (product_name LIKE ? OR barcode LIKE ? OR sku LIKE ?) AND status='active' LIMIT 20");
    $stmt->execute(["%$query%", "%$query%", "%$query%"]);
    $products = $stmt->fetchAll();
    echo json_encode(['success' => true, 'products' => $products]);
} else {
    echo json_encode(['success' => false, 'message' => 'No query']);
}
