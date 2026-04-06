<?php
require_once dirname(__DIR__) . '/includes/config.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch orders safely
$query = "SELECT o.*, p.name as product_name, p.price as unit_price 
          FROM orders o 
          LEFT JOIN products p ON o.product_id = p.id 
          WHERE o.customer_id = ? 
          ORDER BY o.order_date DESC";

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Query failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | NearBuy</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

    <!-- ✅ External CSS -->
    <link rel="stylesheet" href="../assests/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav>
    <a class="nav-brand" href="dashboard.php">
        <div class="brand-icon">
            <svg viewBox="0 0 24 24">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
            </svg>
        </div>
        <span class="brand-name">NearBuy</span>
    </a>

    <div class="nav-right">
        <div class="user-pill">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION["username"], 0, 1)); ?>
            </div>
            <span class="username-display">
                <?php echo htmlspecialchars($_SESSION["username"]); ?>
            </span>
        </div>
    </div>
</nav>

<div class="page-body">

    <!-- SIDEBAR -->
    <?php include_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- HEADER -->
        <div class="hero">
            <p class="hero-label">Your Activity</p>
            <h1>My <em>Orders</em></h1>
            <p>Track all your purchases here</p>
        </div>

        <!-- ORDERS SECTION -->
        <div class="section">
            <div class="section-header">
                <p class="section-title">Order History</p>
                <?php if ($result): ?>
                    <span class="section-count"><?php echo $result->num_rows; ?> orders</span>
                <?php endif; ?>
            </div>

            <?php if ($result && $result->num_rows > 0): ?>
                
                <div style="display:flex; flex-direction:column; gap:12px;">

                    <?php while($row = $result->fetch_assoc()): ?>

                        <?php
                        $status = strtolower($row['status']);
                        if (!in_array($status, ['pending','completed','cancelled'])) {
                            $status = 'pending';
                        }
                        ?>

                        <div style="
                            background:#111;
                            padding:20px;
                            border-radius:12px;
                            border:1px solid rgba(255,255,255,0.07);
                            display:flex;
                            justify-content:space-between;
                            align-items:center;
                        ">

                            <!-- LEFT SIDE -->
                            <div>
                                <p style="font-weight:500;">
                                    <?php echo htmlspecialchars($row['product_name'] ?? 'Product not available'); ?>
                                </p>
                                <p style="font-size:12px; color:rgba(255,255,255,0.4);">
                                    Order #<?php echo $row['id']; ?> • 
                                    <?php echo date('M d, Y', strtotime($row['order_date'])); ?>
                                </p>
                            </div>

                            <!-- RIGHT SIDE -->
                            <div style="text-align:right;">
                                <p style="color:#ffb81e; font-weight:600;">
                                    ₱<?php echo number_format($row['total_price'], 2); ?>
                                </p>

                                <span style="
                                    font-size:10px;
                                    padding:5px 10px;
                                    border-radius:6px;
                                    background: <?php echo $status == 'completed' ? 'rgba(52,211,153,0.1)' : ($status == 'cancelled' ? 'rgba(248,113,113,0.1)' : 'rgba(255,184,30,0.1)'); ?>;
                                    color: <?php echo $status == 'completed' ? '#34d399' : ($status == 'cancelled' ? '#f87171' : '#ffb81e'); ?>;
                                ">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </div>

                        </div>

                    <?php endwhile; ?>

                </div>

            <?php else: ?>
                <p style="color: rgba(255,255,255,0.3);">No orders found.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>