<?php
require_once __DIR__ . "/../includes/config.php"; 
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

$user_id = $_SESSION['user_id'];

$total_listings = $conn->prepare("SELECT COUNT(*) as c FROM products WHERE user_id = ?");
$total_listings->bind_param("i", $user_id);
$total_listings->execute();
$total_listings = $total_listings->get_result()->fetch_assoc()['c'];

$order_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN o.status='pending'   THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN o.status='completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN o.status='cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN o.status='completed' THEN o.total_price ELSE 0 END) as revenue
    FROM orders o JOIN products p ON o.product_id = p.id WHERE p.user_id = ?
");
$order_stats->bind_param("i", $user_id);
$order_stats->execute();
$stats = $order_stats->get_result()->fetch_assoc();

$recent_orders = $conn->prepare("
    SELECT o.*, p.name as product_name, u.username as buyer_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON o.customer_id = u.id
    WHERE p.user_id = ?
    ORDER BY o.order_date DESC LIMIT 5
");
$recent_orders->bind_param("i", $user_id);
$recent_orders->execute();
$recent_orders = $recent_orders->get_result()->fetch_all(MYSQLI_ASSOC);

$my_products = $conn->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$my_products->bind_param("i", $user_id);
$my_products->execute();
$my_products = $my_products->get_result()->fetch_all(MYSQLI_ASSOC);

$user_info = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_info->bind_param("i", $user_id);
$user_info->execute();
$user_info = $user_info->get_result()->fetch_assoc();

$username  = htmlspecialchars($_SESSION['username']);
$initials  = strtoupper(substr($username, 0, 1));
$shop_name = !empty($user_info['shop_name']) ? htmlspecialchars($user_info['shop_name']) : $username . "'s Shop";
$revenue   = number_format($stats['revenue'] ?? 0, 2);
$emojis    = ['🥦','🍅','🥚','🌽','🐟','🧄','🍎','🥕','🧅','🍊','🥬','🫑'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard | NearBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { box-sizing: border-box; }

        /* Hero */
        .seller-hero { display: flex; align-items: center; justify-content: space-between; gap: 24px; margin-bottom: 28px; background: linear-gradient(135deg, rgba(255,184,30,0.13), rgba(255,107,53,0.07)); border: 1px solid rgba(255,184,30,0.18); border-radius: 20px; padding: 32px 36px; }
        .hero-tag { font-size: 11px; font-weight: 500; letter-spacing: 1px; text-transform: uppercase; color: #ffb81e; margin-bottom: 10px; }
        .seller-hero h1 { font-family: 'DM Serif Display', serif; font-size: 30px; color: #fff; margin-bottom: 6px; line-height: 1.2; }
        .seller-hero h1 em { font-style: italic; color: #ffb81e; }
        .seller-hero p { font-size: 13px; color: rgba(255,255,255,0.35); margin-bottom: 20px; }
        .hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-primary { display: inline-flex; align-items: center; gap: 8px; padding: 9px 20px; background: linear-gradient(135deg, #ffb81e, #ff6b35); color: #0d0d0d; font-size: 13px; font-weight: 500; border-radius: 999px; text-decoration: none; transition: opacity 0.15s; }
        .btn-primary:hover { opacity: 0.88; }
        .btn-primary svg { width: 14px; height: 14px; fill: #0d0d0d; }
        .btn-secondary { display: inline-flex; align-items: center; gap: 8px; padding: 9px 20px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); color: rgba(255,255,255,0.7); font-size: 13px; font-weight: 500; border-radius: 999px; text-decoration: none; transition: all 0.15s; }
        .btn-secondary:hover { border-color: rgba(255,184,30,0.35); color: #ffb81e; }
        .btn-secondary svg { width: 14px; height: 14px; fill: currentColor; }
        .hero-shop-badge { display: flex; flex-direction: column; align-items: flex-end; flex-shrink: 0; gap: 8px; }
        .shop-icon { width: 72px; height: 72px; border-radius: 18px; background: rgba(255,184,30,0.1); border: 1px solid rgba(255,184,30,0.25); display: flex; align-items: center; justify-content: center; font-size: 32px; }
        .shop-name-tag { font-size: 12px; color: rgba(255,255,255,0.4); text-align: right; }

        /* Revenue highlight */
        .revenue-strip { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr; gap: 12px; margin-bottom: 28px; }
        .rev-card { background: #111; border: 1px solid rgba(255,255,255,0.07); border-radius: 14px; padding: 18px 16px; transition: border-color 0.2s; }
        .rev-card:hover { border-color: rgba(255,184,30,0.2); }
        .rev-card.highlight { background: linear-gradient(135deg, rgba(255,184,30,0.1), rgba(255,107,53,0.06)); border-color: rgba(255,184,30,0.25); }
        .rev-value { font-size: 22px; font-weight: 500; color: #fff; line-height: 1; margin-bottom: 6px; }
        .rev-value.yellow { color: #ffb81e; }
        .rev-value.green  { color: #34d399; }
        .rev-value.red    { color: #f87171; }
        .rev-label { font-size: 11px; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 0.5px; }

        /* Main grid */
        .dash-grid { display: grid; grid-template-columns: 1fr 300px; gap: 24px; align-items: start; }

        /* Cards */
        .panel-card { background: #111; border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; padding: 22px 24px; margin-bottom: 20px; }
        .section-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .section-title { font-size: 14px; font-weight: 500; color: #fff; }
        .section-link { font-size: 12px; color: rgba(255,184,30,0.7); text-decoration: none; }
        .section-link:hover { color: #ffb81e; }

        /* Orders table */
        .orders-table { width: 100%; border-collapse: collapse; }
        .orders-table th { font-size: 11px; color: rgba(255,255,255,0.25); text-transform: uppercase; letter-spacing: 0.5px; padding: 0 0 12px; text-align: left; font-weight: 400; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .orders-table td { font-size: 13px; color: rgba(255,255,255,0.65); padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: middle; }
        .orders-table tr:last-child td { border-bottom: none; }
        .buyer-pill { display: inline-flex; align-items: center; gap: 8px; }
        .buyer-avatar { width: 28px; height: 28px; border-radius: 50%; background: rgba(255,184,30,0.1); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 500; color: #ffb81e; flex-shrink: 0; }
        .buyer-name { color: #fff; font-weight: 500; }
        .status-badge { display: inline-block; font-size: 10px; font-weight: 500; padding: 3px 10px; border-radius: 999px; }
        .status-pending   { background: rgba(255,184,30,0.1);  color: #ffb81e; border: 1px solid rgba(255,184,30,0.25); }
        .status-completed { background: rgba(52,211,153,0.1);  color: #34d399; border: 1px solid rgba(52,211,153,0.25); }
        .status-cancelled { background: rgba(248,113,113,0.1); color: #f87171; border: 1px solid rgba(248,113,113,0.25); }

        /* Pending alert */
        .pending-alert { display: flex; align-items: center; gap: 12px; background: rgba(255,184,30,0.07); border: 1px solid rgba(255,184,30,0.2); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; }
        .pending-alert svg { width: 18px; height: 18px; fill: #ffb81e; flex-shrink: 0; }
        .pending-alert p { font-size: 13px; color: rgba(255,255,255,0.7); }
        .pending-alert a { color: #ffb81e; text-decoration: none; font-weight: 500; }

        /* Product list */
        .product-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
        .product-row:last-child { border-bottom: none; }
        .product-emoji { width: 38px; height: 38px; border-radius: 10px; background: rgba(255,184,30,0.07); display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .product-info { flex: 1; min-width: 0; }
        .product-name { font-size: 13px; font-weight: 500; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
        .product-loc { font-size: 11px; color: rgba(255,255,255,0.3); }
        .product-price { font-size: 14px; font-weight: 500; color: #ffb81e; flex-shrink: 0; }

        /* Quick links */
        .quick-links { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px; }
        .quick-link { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 14px 10px; background: #111; border: 1px solid rgba(255,255,255,0.07); border-radius: 14px; text-decoration: none; transition: border-color 0.2s, transform 0.15s; }
        .quick-link:hover { border-color: rgba(255,184,30,0.25); transform: translateY(-2px); }
        .quick-link svg { width: 20px; height: 20px; fill: #ffb81e; }
        .quick-link span { font-size: 11px; color: rgba(255,255,255,0.5); }

        .empty { text-align: center; padding: 28px; font-size: 13px; color: rgba(255,255,255,0.2); }

        @media (max-width: 900px) {
            .revenue-strip { grid-template-columns: repeat(3, 1fr); }
            .dash-grid { grid-template-columns: 1fr; }
            .hero-shop-badge { display: none; }
        }
    </style>
</head>
<body>
<nav class="top-navbar">
    <a class="nav-brand" href="dashboard.php">
        <div class="brand-icon"><svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></div>
        <span class="brand-name">NearBuy</span>
    </a>
    <div class="nav-right">
        <div class="user-pill">
            <div class="user-avatar"><?php echo $initials; ?></div>
            <span class="username-display"><?php echo $username; ?></span>
        </div>
    </div>
</nav>

<div class="page-body">
    <?php include_once __DIR__ . "/../includes/sidebar.php"; ?>
    <main class="main-content">

        <!-- Hero -->
        <div class="seller-hero">
            <div>
                <p class="hero-tag">Seller Dashboard</p>
                <h1>Welcome back,<br><em><?php echo $username; ?></em></h1>
                <p><?php echo $shop_name; ?> · Here's how your shop is doing</p>
                <div class="hero-actions">
                    <a href="add_product.php" class="btn-primary">
                        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                        Add Product
                    </a>
                    <a href="orders.php" class="btn-secondary">
                        <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                        View Orders
                    </a>
                    <a href="my_listings.php" class="btn-secondary">
                        <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
                        My Listings
                    </a>
                </div>
            </div>
            <div class="hero-shop-badge">
                <div class="shop-icon">🏪</div>
                <span class="shop-name-tag"><?php echo $shop_name; ?></span>
            </div>
        </div>

        <!-- Stats strip -->
        <div class="revenue-strip">
            <div class="rev-card highlight">
                <div class="rev-value yellow">₱<?php echo $revenue; ?></div>
                <div class="rev-label">Total Revenue</div>
            </div>
            <div class="rev-card">
                <div class="rev-value"><?php echo $total_listings; ?></div>
                <div class="rev-label">Listings</div>
            </div>
            <div class="rev-card">
                <div class="rev-value"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="rev-label">Total Orders</div>
            </div>
            <div class="rev-card">
                <div class="rev-value green"><?php echo $stats['completed'] ?? 0; ?></div>
                <div class="rev-label">Completed</div>
            </div>
            <div class="rev-card">
                <div class="rev-value yellow"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="rev-label">Pending</div>
            </div>
        </div>

        <!-- Pending alert -->
        <?php if (($stats['pending'] ?? 0) > 0): ?>
        <div class="pending-alert">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            <p>You have <strong><?php echo $stats['pending']; ?> pending order<?php echo $stats['pending'] > 1 ? 's' : ''; ?></strong> waiting for your action. <a href="orders.php">Review now →</a></p>
        </div>
        <?php endif; ?>

        <div class="dash-grid">
            <!-- Left: recent orders -->
            <div>
                <div class="panel-card">
                    <div class="section-head">
                        <span class="section-title">Recent Orders</span>
                        <a href="orders.php" class="section-link">View all →</a>
                    </div>
                    <?php if (!empty($recent_orders)): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Buyer</th>
                                <th>Product</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $o):
                                $st = strtolower($o['status']);
                                $bi = strtoupper(substr($o['buyer_name'], 0, 1));
                            ?>
                            <tr>
                                <td>
                                    <div class="buyer-pill">
                                        <div class="buyer-avatar"><?php echo $bi; ?></div>
                                        <span class="buyer-name"><?php echo htmlspecialchars($o['buyer_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($o['product_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($o['order_date'])); ?></td>
                                <td style="color:#ffb81e;font-weight:500;">₱<?php echo number_format($o['total_price'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo $st; ?>"><?php echo ucfirst($st); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty">No orders yet. Share your listings to get started!</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right panel -->
            <div>
                <div class="quick-links">
                    <a href="add_product.php" class="quick-link">
                        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                        <span>Add Product</span>
                    </a>
                    <a href="orders.php" class="quick-link">
                        <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                        <span>Orders</span>
                    </a>
                    <a href="my_listings.php" class="quick-link">
                        <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
                        <span>Listings</span>
                    </a>
                    <a href="profile.php" class="quick-link">
                        <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                        <span>Profile</span>
                    </a>
                </div>

                <div class="panel-card">
                    <div class="section-head">
                        <span class="section-title">My Listings</span>
                        <a href="my_listings.php" class="section-link">See all →</a>
                    </div>
                    <?php if (!empty($my_products)):
                        foreach ($my_products as $i => $p):
                            $em = $emojis[$i % count($emojis)];
                    ?>
                        <div class="product-row">
                            <div class="product-emoji"><?php echo $em; ?></div>
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                <div class="product-loc">📍 <?php echo htmlspecialchars($p['location'] ?? '—'); ?></div>
                            </div>
                            <div class="product-price">₱<?php echo number_format($p['price'], 2); ?></div>
                        </div>
                    <?php endforeach;
                    else: ?>
                        <div class="empty">No listings yet. <a href="add_product.php" style="color:#ffb81e;">Add one!</a></div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($user_info['location'])): ?>
                <div class="panel-card" style="padding:16px 22px;">
                    <p style="font-size:11px;color:rgba(255,255,255,0.3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Shop Location</p>
                    <p style="font-size:13px;color:rgba(255,255,255,0.5);">📍 <?php echo htmlspecialchars($user_info['location']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>
</body>
</html>