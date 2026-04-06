<?php
// Use absolute path for config
require_once __DIR__ . '/../includes/config.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Security Check - Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Fetch Orders with Error Handling
$query = "SELECT o.*, p.name as product_name, p.price as unit_price 
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          WHERE o.customer_id = ? 
          ORDER BY o.order_date DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query Preparation Failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders | NearBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        /* Base Styles */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #0d0d0d; color: white; font-family: 'DM Sans', sans-serif; min-height: 100vh; }
        
        /* Layout Container */
        .page-body { display: flex; min-height: 100vh; }
        
        /* Main Content */
        .main-content { flex: 1; padding: 48px; }
        
        h2 { font-size: 32px; margin-bottom: 8px; }
        .subtitle { color: rgba(255,255,255,0.4); margin-bottom: 40px; font-size: 14px; }

        .order-card { 
            background: #111; 
            border: 1px solid rgba(255,255,255,0.07); 
            border-radius: 14px; 
            padding: 24px; 
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: border-color 0.2s;
        }
        .order-card:hover { border-color: rgba(255,184,30,0.3); }

        .product-name { font-size: 16px; font-weight: 500; margin-bottom: 4px; }
        .order-date { font-size: 12px; color: rgba(255,255,255,0.3); }
        
        .price { font-weight: 500; color: #ffb81e; font-size: 18px; margin-bottom: 4px; }
        .status-badge { 
            font-size: 10px; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            background: rgba(255,184,30,0.1); 
            color: #ffb81e; 
            padding: 4px 8px; 
            border-radius: 4px; 
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="page-body">
    <?php include_once __DIR__ . "/sidebar.php"; ?>

    <div class="main-content">
        <h2>My Orders</h2>
        <p class="subtitle">Track your local purchases and order status</p>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="order-card">
                    <div>
                        <div class="product-name"><?php echo htmlspecialchars($row['product_name']); ?></div>
                        <div class="order-date">Placed on <?php echo date('M d, Y', strtotime($row['order_date'])); ?></div>
                    </div>
                    <div style="text-align: right;">
                        <div class="price">₱<?php echo number_format($row['total_price'], 2); ?></div>
                        <div class="status-badge"><?php echo htmlspecialchars($row['status']); ?></div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 100px 0; color: rgba(255,255,255,0.2);">
                <p>No orders found. Time to go shopping!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>