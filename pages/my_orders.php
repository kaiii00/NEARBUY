<?php
require_once dirname(__DIR__) . '/includes/config.php'; 
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();
requireRole('buyer');
$user_id = $_SESSION['user_id'];

// Fetch orders
$query = "SELECT o.*, p.name as product_name, p.price as unit_price 
          FROM orders o 
          LEFT JOIN products p ON o.product_id = p.id 
          WHERE o.customer_id = ? 
          ORDER BY o.order_date DESC";

$stmt = $conn->prepare($query);
if (!$stmt) die("Query failed: " . $conn->error);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Count by status
$total     = count($orders);
$pending   = count(array_filter($orders, fn($o) => strtolower($o['status']) == 'pending'));
$completed = count(array_filter($orders, fn($o) => strtolower($o['status']) == 'completed'));
$cancelled = count(array_filter($orders, fn($o) => strtolower($o['status']) == 'cancelled'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | NearBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* HERO */
        .hero { margin-bottom: 32px; }
        .hero-label {
            font-size: 12px; font-weight: 500;
            letter-spacing: 1px; text-transform: uppercase;
            color: #ffb81e; margin-bottom: 10px;
        }
        .hero h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 36px; line-height: 1.15;
            letter-spacing: -0.5px; color: #fff;
            margin-bottom: 8px;
        }
        .hero h1 em { font-style: italic; color: #ffb81e; }
        .hero > p { font-size: 14px; color: rgba(255,255,255,0.35); }

        /* STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: #111;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px;
            padding: 18px 20px;
            display: flex; flex-direction: column; gap: 6px;
            transition: border-color 0.2s;
        }
        .stat-card:hover { border-color: rgba(255,184,30,0.2); }
        .stat-value { font-size: 28px; font-weight: 500; color: #fff; line-height: 1; }
        .stat-label { font-size: 12px; color: rgba(255,255,255,0.3); }
        .stat-card.yellow .stat-value { color: #ffb81e; }
        .stat-card.green  .stat-value { color: #34d399; }
        .stat-card.red    .stat-value { color: #f87171; }

        /* FILTER TABS */
        .filter-tabs { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
        .filter-tab {
            padding: 7px 16px; border-radius: 999px;
            font-size: 13px; color: rgba(255,255,255,0.4);
            border: 1px solid rgba(255,255,255,0.08);
            cursor: pointer; transition: all 0.15s;
            background: none; font-family: 'DM Sans', sans-serif;
            user-select: none;
        }
        .filter-tab:hover { color: rgba(255,255,255,0.75); border-color: rgba(255,255,255,0.2); }
        .filter-tab.active {
            background: rgba(255,184,30,0.12);
            border-color: rgba(255,184,30,0.35);
            color: #ffb81e;
        }

        /* ORDER CARDS */
        .orders-list { display: flex; flex-direction: column; gap: 12px; }

        .order-card {
            background: #111;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px;
            padding: 20px 24px;
            display: flex; justify-content: space-between; align-items: center;
            gap: 20px;
            transition: border-color 0.2s, transform 0.2s;
        }
        .order-card:hover {
            border-color: rgba(255,184,30,0.2);
            transform: translateX(4px);
        }
        .order-card.hidden { display: none; }

        .order-left { display: flex; align-items: center; gap: 16px; }
        .order-icon {
            width: 46px; height: 46px; border-radius: 12px;
            background: rgba(255,184,30,0.08);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .order-icon svg { width: 20px; height: 20px; fill: #ffb81e; }

        .order-name { font-size: 15px; font-weight: 500; color: #fff; margin-bottom: 4px; }
        .order-meta { font-size: 12px; color: rgba(255,255,255,0.3); }
        .order-meta span { margin-right: 10px; }

        .order-right { text-align: right; flex-shrink: 0; }
        .order-price { font-size: 18px; font-weight: 500; color: #ffb81e; margin-bottom: 8px; }

        .status-badge {
            display: inline-block;
            font-size: 11px; font-weight: 500;
            padding: 4px 12px; border-radius: 999px;
        }
        .status-pending   { background: rgba(255,184,30,0.1);  color: #ffb81e; border: 1px solid rgba(255,184,30,0.25); }
        .status-completed { background: rgba(52,211,153,0.1);  color: #34d399; border: 1px solid rgba(52,211,153,0.25); }
        .status-cancelled { background: rgba(248,113,113,0.1); color: #f87171; border: 1px solid rgba(248,113,113,0.25); }

        /* EMPTY STATE */
        .empty-state {
            text-align: center; padding: 72px 20px;
            color: rgba(255,255,255,0.2); font-size: 14px;
        }
        .empty-state .empty-icon { font-size: 48px; margin-bottom: 16px; }
        .empty-state p { margin-bottom: 20px; }
        .btn-shop {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 22px;
            background: linear-gradient(135deg, #ffb81e, #ff6b35);
            border-radius: 10px; color: #0d0d0d;
            font-size: 13px; font-weight: 500;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .btn-shop:hover { opacity: 0.85; }
    </style>
</head>
<body>

<nav class="top-navbar">
    <a class="nav-brand" href="dashboard.php">
        <div class="brand-icon">
            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
        </div>
        <span class="brand-name">NearBuy</span>
    </a>
    <div class="nav-right">
        <div class="user-pill">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION["username"], 0, 1)); ?></div>
            <span class="username-display"><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
        </div>
    </div>
</nav>

<div class="page-body">
    <?php include_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <main class="main-content">

        <!-- HERO -->
        <header class="hero">
            <p class="hero-label">Buyer Activity</p>
            <h1>My <em>Orders</em></h1>
            <p>Track all your purchases in one place</p>
        </header>

        <?php if ($total > 0): ?>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-value"><?php echo $total; ?></span>
                    <span class="stat-label">Total Orders</span>
                </div>
                <div class="stat-card yellow">
                    <span class="stat-value"><?php echo $pending; ?></span>
                    <span class="stat-label">Pending</span>
                </div>
                <div class="stat-card green">
                    <span class="stat-value"><?php echo $completed; ?></span>
                    <span class="stat-label">Completed</span>
                </div>
                <div class="stat-card red">
                    <span class="stat-value"><?php echo $cancelled; ?></span>
                    <span class="stat-label">Cancelled</span>
                </div>
            </div>

            <!-- FILTER TABS -->
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterOrders('all', this)">All</button>
                <button class="filter-tab" onclick="filterOrders('pending', this)">Pending</button>
                <button class="filter-tab" onclick="filterOrders('completed', this)">Completed</button>
                <button class="filter-tab" onclick="filterOrders('cancelled', this)">Cancelled</button>
            </div>

            <!-- ORDER LIST -->
            <div class="orders-list">
                <?php foreach ($orders as $row):
                    $status = strtolower($row['status']);
                    if (!in_array($status, ['pending','completed','cancelled'])) $status = 'pending';
                ?>
                    <div class="order-card" data-status="<?php echo $status; ?>">
                        <div class="order-left">
                            <div class="order-icon">
                                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
                            </div>
                            <div>
                                <p class="order-name"><?php echo htmlspecialchars($row['product_name'] ?? 'Product unavailable'); ?></p>
                                <div class="order-meta">
                                    <span>Order #<?php echo $row['id']; ?></span>
                                    <span><?php echo date('M d, Y', strtotime($row['order_date'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="order-right">
                            <p class="order-price">₱<?php echo number_format($row['total_price'], 2); ?></p>
                            <span class="status-badge status-<?php echo $status; ?>">
                                <?php echo ucfirst($status); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <!-- EMPTY STATE -->
            <div class="empty-state">
                <div class="empty-icon">🛍️</div>
                <p>You haven't placed any orders yet.</p>
                <a href="dashboard.php" class="btn-shop">Browse Products</a>
            </div>

        <?php endif; ?>

    </main>
</div>

<script>
    function filterOrders(status, btn) {
        // Update active tab
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');

        // Show/hide cards
        document.querySelectorAll('.order-card').forEach(card => {
            if (status === 'all' || card.dataset.status === status) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    }
</script>

</body>
</html>