<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireLogin();
requireRole('seller');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$order_id  = intval($_POST['order_id'] ?? 0);
$newStatus = $_POST['status'] ?? '';
$user_id   = $_SESSION['user_id'];

// Only allow these two status values
if (!in_array($newStatus, ['completed', 'cancelled'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

// Security check: make sure this order belongs to a product owned by the logged-in seller
// and that the order is currently pending before updating
$check = $conn->prepare("
    SELECT o.id 
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    WHERE o.id = ? AND p.user_id = ? AND o.status = 'pending'
");
$check->bind_param("ii", $order_id, $user_id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found or already processed.']);
    exit;
}

// Update the order status
$update = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$update->bind_param("si", $newStatus, $order_id);

if ($update->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}