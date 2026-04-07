<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

// ── SEND MESSAGE ──────────────────────────────────────────────
if ($action === 'send') {
    $order_id    = (int)($_POST['order_id'] ?? 0);
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $message     = trim($_POST['message'] ?? '');

    if (!$order_id || !$receiver_id || $message === '') {
        echo json_encode(['success' => false, 'error' => 'Missing fields.']);
        exit;
    }

    // Verify the current user is actually part of this order
    $check = $conn->prepare("
        SELECT o.id FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.id = ?
          AND (o.customer_id = ? OR p.user_id = ?)
        LIMIT 1
    ");
    $check->bind_param("iii", $order_id, $user_id, $user_id);
    $check->execute();
    if (!$check->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO messages (order_id, sender_id, receiver_id, message)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiis", $order_id, $user_id, $receiver_id, $message);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message_id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'DB error.']);
    }
    exit;
}

// ── FETCH MESSAGES FOR ONE CONVERSATION ───────────────────────
if ($action === 'fetch') {
    $order_id    = (int)($_GET['order_id'] ?? 0);
    $since_id    = (int)($_GET['since_id'] ?? 0);   // for polling — only get new ones

    if (!$order_id) {
        echo json_encode(['success' => false, 'error' => 'Missing order_id.']);
        exit;
    }

    // Mark messages to this user as read
    $markRead = $conn->prepare("
        UPDATE messages SET is_read = 1
        WHERE order_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $markRead->bind_param("ii", $order_id, $user_id);
    $markRead->execute();

    $stmt = $conn->prepare("
        SELECT m.id, m.sender_id, m.message, m.created_at,
               u.username as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.order_id = ? AND m.id > ?
          AND (m.sender_id = ? OR m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("iiii", $order_id, $since_id, $user_id, $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'messages' => $rows]);
    exit;
}

// ── UNREAD COUNT (for badge) ───────────────────────────────────
if ($action === 'unread_count') {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as cnt FROM messages
        WHERE receiver_id = ? AND is_read = 0
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cnt = $stmt->get_result()->fetch_assoc()['cnt'];
    echo json_encode(['success' => true, 'count' => $cnt]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action.']);