<?php
require_once __DIR__ . "/../includes/config.php"; 
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

$user_id = $_SESSION['user_id'];

$order_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status='pending'   THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed
    FROM orders WHERE customer_id = ?
");
$order_stats->bind_param("i", $user_id);
$order_stats->execute();
$stats = $order_stats->get_result()->fetch_assoc();

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

$recent_orders = $conn->prepare("
    SELECT o.*, p.name as product_name, u.username as seller_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE o.customer_id = ?
    ORDER BY o.order_date DESC LIMIT 3
");
$recent_orders->bind_param("i", $user_id);
$recent_orders->execute();
$recent_orders = $recent_orders->get_result()->fetch_all(MYSQLI_ASSOC);

$all_products = $conn->query("SELECT p.*, u.username as seller_name FROM products p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
$products = $all_products ? $all_products->fetch_all(MYSQLI_ASSOC) : [];

$user_info = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_info->bind_param("i", $user_id);
$user_info->execute();
$user_info = $user_info->get_result()->fetch_assoc();

$username = htmlspecialchars($_SESSION['username']);
$initials = strtoupper(substr($username, 0, 1));
$product_emojis = ['🥦','🍅','🥚','🌽','🐟','🧄','🍎','🥕','🧅','🍊','🥬','🫑','🍋','🥒','🍆','🥑','🍇','🍓','🥝','🧀'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop | NearBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { box-sizing: border-box; }
        .cart-nav-btn { position: relative; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; text-decoration: none; transition: all 0.2s; }
        .cart-nav-btn:hover { border-color: rgba(255,184,30,0.4); background: rgba(255,184,30,0.08); }
        .cart-nav-btn svg { width: 18px; height: 18px; fill: rgba(255,255,255,0.6); }
        .cart-nav-btn:hover svg { fill: #ffb81e; }
        .cart-badge { position: absolute; top: -6px; right: -6px; background: #ffb81e; color: #0d0d0d; font-size: 10px; font-weight: 500; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

        .hero-banner { background: linear-gradient(135deg, rgba(255,184,30,0.14), rgba(255,107,53,0.07)); border: 1px solid rgba(255,184,30,0.18); border-radius: 20px; padding: 36px 40px; margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
        .hero-tag { font-size: 11px; font-weight: 500; letter-spacing: 1px; text-transform: uppercase; color: #ffb81e; margin-bottom: 10px; }
        .hero-banner h1 { font-family: 'DM Serif Display', serif; font-size: 32px; color: #fff; margin-bottom: 8px; line-height: 1.2; }
        .hero-banner h1 em { font-style: italic; color: #ffb81e; }
        .hero-banner p { font-size: 13px; color: rgba(255,255,255,0.35); margin-bottom: 20px; }
        .hero-cta { display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; background: linear-gradient(135deg, #ffb81e, #ff6b35); color: #0d0d0d; font-size: 13px; font-weight: 500; border-radius: 999px; text-decoration: none; transition: opacity 0.15s; }
        .hero-cta:hover { opacity: 0.88; }
        .hero-cta svg { width: 14px; height: 14px; fill: #0d0d0d; }
        .hero-emojis { font-size: 60px; line-height: 1; letter-spacing: -4px; flex-shrink: 0; }

        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 28px; }
        .stat-card { background: #111; border: 1px solid rgba(255,255,255,0.07); border-radius: 14px; padding: 16px 18px; display: flex; align-items: center; gap: 14px; transition: border-color 0.2s; }
        .stat-card:hover { border-color: rgba(255,184,30,0.2); }
        .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .stat-icon svg { width: 18px; height: 18px; }
        .stat-icon.yellow { background: rgba(255,184,30,0.12); } .stat-icon.yellow svg { fill: #ffb81e; }
        .stat-icon.green  { background: rgba(52,211,153,0.12); } .stat-icon.green  svg { fill: #34d399; }
        .stat-icon.blue   { background: rgba(96,165,250,0.12); } .stat-icon.blue   svg { fill: #60a5fa; }
        .stat-value { font-size: 22px; font-weight: 500; color: #fff; line-height: 1; margin-bottom: 3px; }
        .stat-label { font-size: 11px; color: rgba(255,255,255,0.3); }

        .promo-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 28px; }
        .promo-card { border-radius: 16px; padding: 22px 24px; display: flex; align-items: center; justify-content: space-between; border: 1px solid; }
        .promo-card.green-promo { background: rgba(52,211,153,0.07); border-color: rgba(52,211,153,0.18); }
        .promo-card.blue-promo  { background: rgba(96,165,250,0.07); border-color: rgba(96,165,250,0.18); }
        .promo-label { font-size: 10px; font-weight: 500; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 6px; }
        .green-promo .promo-label { color: #34d399; }
        .blue-promo  .promo-label { color: #60a5fa; }
        .promo-card h3 { font-size: 16px; font-weight: 500; color: #fff; margin-bottom: 4px; }
        .promo-card p  { font-size: 12px; color: rgba(255,255,255,0.35); }
        .promo-emoji { font-size: 40px; }

        .search-wrap { position: relative; margin-bottom: 20px; }
        .search-input { width: 100%; background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 12px 16px 12px 46px; color: #fff; font-size: 14px; font-family: 'DM Sans', sans-serif; outline: none; transition: border-color 0.2s; }
        .search-input:focus { border-color: rgba(255,184,30,0.4); }
        .search-input::placeholder { color: rgba(255,255,255,0.2); }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; fill: rgba(255,255,255,0.2); pointer-events: none; }

        .categories-scroll { display: flex; gap: 10px; margin-bottom: 24px; overflow-x: auto; padding-bottom: 4px; }
        .categories-scroll::-webkit-scrollbar { display: none; }
        .cat-pill { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 999px; font-size: 13px; color: rgba(255,255,255,0.4); border: 1px solid rgba(255,255,255,0.08); cursor: pointer; transition: all 0.15s; background: none; font-family: 'DM Sans', sans-serif; white-space: nowrap; flex-shrink: 0; }
        .cat-pill:hover { color: rgba(255,255,255,0.75); border-color: rgba(255,255,255,0.2); }
        .cat-pill.active { background: rgba(255,184,30,0.12); border-color: rgba(255,184,30,0.35); color: #ffb81e; }

        .dash-grid { display: grid; grid-template-columns: 1fr 290px; gap: 24px; align-items: start; }
        .section-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .section-title { font-size: 15px; font-weight: 500; color: #fff; }
        .section-count { font-size: 11px; padding: 3px 9px; background: rgba(255,255,255,0.06); border-radius: 999px; color: rgba(255,255,255,0.35); margin-left: 8px; }
        .section-link { font-size: 12px; color: rgba(255,184,30,0.7); text-decoration: none; }
        .section-link:hover { color: #ffb81e; }

        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; }
        .product-card { background: #111; border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; overflow: hidden; text-decoration: none; display: block; transition: border-color 0.2s, transform 0.2s; }
        .product-card:hover { border-color: rgba(255,184,30,0.3); transform: translateY(-4px); }
        .card-img { height: 100px; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; font-size: 46px; position: relative; }
        .card-badge { position: absolute; top: 8px; left: 8px; font-size: 10px; font-weight: 500; padding: 2px 8px; border-radius: 999px; background: rgba(255,184,30,0.15); color: #ffb81e; border: 1px solid rgba(255,184,30,0.25); }
        .card-body { padding: 12px 14px; }
        .card-name { font-size: 13px; font-weight: 500; color: #fff; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card-seller { font-size: 11px; color: rgba(255,255,255,0.28); margin-bottom: 10px; }
        .card-footer { display: flex; justify-content: space-between; align-items: center; }
        .price { font-size: 14px; font-weight: 500; color: #ffb81e; }
        .loc { font-size: 10px; color: rgba(255,255,255,0.2); }

        .panel-card { background: #111; border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; padding: 20px 22px; margin-bottom: 16px; }
        .order-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-top: 1px solid rgba(255,255,255,0.04); gap: 8px; }
        .order-row:first-child { border-top: none; }
        .order-product { font-size: 13px; font-weight: 500; color: #fff; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; }
        .order-meta { font-size: 11px; color: rgba(255,255,255,0.3); }
        .status-badge { display: inline-block; font-size: 10px; font-weight: 500; padding: 3px 9px; border-radius: 999px; white-space: nowrap; flex-shrink: 0; }
        .status-pending   { background: rgba(255,184,30,0.1);  color: #ffb81e; border: 1px solid rgba(255,184,30,0.25); }
        .status-completed { background: rgba(52,211,153,0.1);  color: #34d399; border: 1px solid rgba(52,211,153,0.25); }
        .status-cancelled { background: rgba(248,113,113,0.1); color: #f87171; border: 1px solid rgba(248,113,113,0.25); }

        .quick-links { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px; }
        .quick-link { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 14px 10px; background: #111; border: 1px solid rgba(255,255,255,0.07); border-radius: 14px; text-decoration: none; transition: border-color 0.2s, transform 0.15s; }
        .quick-link:hover { border-color: rgba(255,184,30,0.25); transform: translateY(-2px); }
        .quick-link svg { width: 20px; height: 20px; fill: #ffb81e; }
        .quick-link span { font-size: 11px; color: rgba(255,255,255,0.5); font-family: 'DM Sans', sans-serif; }

        .empty { text-align: center; padding: 32px 16px; font-size: 13px; color: rgba(255,255,255,0.2); }
        .no-results { grid-column: 1/-1; text-align: center; padding: 40px; font-size: 13px; color: rgba(255,255,255,0.2); display: none; }

        @media (max-width: 900px) {
            .dash-grid { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: 1fr 1fr; }
            .promo-row { grid-template-columns: 1fr; }
            .hero-emojis { display: none; }
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
        <a href="cart.php" class="cart-nav-btn">
            <svg viewBox="0 0 24 24"><path d="M11 9h2V6h3V4h-3V1h-2v3H8v2h3v3zm-4 9c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zm-8.9-5h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0019.99 4H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63z"/></svg>
            <?php if ($cart_count > 0): ?><span class="cart-badge"><?php echo $cart_count; ?></span><?php endif; ?>
        </a>
        <div class="user-pill">
            <div class="user-avatar"><?php echo $initials; ?></div>
            <span class="username-display"><?php echo $username; ?></span>
        </div>
    </div>
</nav>

<div class="page-body">
    <?php include_once __DIR__ . "/../includes/sidebar.php"; ?>
    <main class="main-content">

        <div class="hero-banner">
            <div>
                <p class="hero-tag">👋 Good day, <?php echo $username; ?></p>
                <h1>Fresh picks,<br><em>right nearby.</em></h1>
                <p>Discover locally sourced produce and goods from sellers in your area</p>
                <a href="cart.php" class="hero-cta">
                    <svg viewBox="0 0 24 24"><path d="M11 9h2V6h3V4h-3V1h-2v3H8v2h3v3zm-4 9c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zm-8.9-5h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0019.99 4H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63z"/></svg>
                    View Cart<?php if ($cart_count > 0) echo " ($cart_count)"; ?>
                </a>
            </div>
            <div class="hero-emojis">🥦🍅🥚</div>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon yellow"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg></div>
                <div><div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div><div class="stat-label">Total Orders</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><svg viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg></div>
                <div><div class="stat-value"><?php echo $stats['completed'] ?? 0; ?></div><div class="stat-label">Completed</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><svg viewBox="0 0 24 24"><path d="M11 9h2V6h3V4h-3V1h-2v3H8v2h3v3zm-4 9c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zm-8.9-5h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0019.99 4H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63z"/></svg></div>
                <div><div class="stat-value"><?php echo $cart_count; ?></div><div class="stat-label">Cart Items</div></div>
            </div>
        </div>

        <div class="promo-row">
            <div class="promo-card green-promo">
                <div><p class="promo-label">Fresh Today</p><h3>Farm to Table</h3><p>Locally sourced, harvested fresh</p></div>
                <div class="promo-emoji">🌽</div>
            </div>
            <div class="promo-card blue-promo">
                <div><p class="promo-label">Community</p><h3>Order Nearby</h3><p>From sellers in your area</p></div>
                <div class="promo-emoji">🛵</div>
            </div>
        </div>

        <div class="dash-grid">
            <div>
                <div class="search-wrap">
                    <svg class="search-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    <input type="text" class="search-input" placeholder="Search products near you..." id="searchInput" oninput="filterProducts()">
                </div>

                <div class="categories-scroll">
                    <button class="cat-pill active" onclick="setCategory(this)">🛒 All</button>
                    <button class="cat-pill" onclick="setCategory(this)">🥦 Vegetables</button>
                    <button class="cat-pill" onclick="setCategory(this)">🍎 Fruits</button>
                    <button class="cat-pill" onclick="setCategory(this)">🐟 Seafood</button>
                    <button class="cat-pill" onclick="setCategory(this)">🧀 Dairy</button>
                    <button class="cat-pill" onclick="setCategory(this)">🥩 Meat</button>
                    <button class="cat-pill" onclick="setCategory(this)">🛍️ Others</button>
                </div>

                <div class="section-head">
                    <div>
                        <span class="section-title">Available Near You</span>
                        <span class="section-count" id="productCount"><?php echo count($products); ?> items</span>
                    </div>
                </div>

                <div class="product-grid" id="productGrid">
                    <?php if (!empty($products)):
                        foreach ($products as $i => $p):
                            $emoji = $product_emojis[$i % count($product_emojis)];
                    ?>
                        <a href="product.php?id=<?php echo $p['id']; ?>" class="product-card" data-name="<?php echo strtolower(htmlspecialchars($p['name'])); ?>">
                            <div class="card-img"><?php echo $emoji; ?><span class="card-badge">Local</span></div>
                            <div class="card-body">
                                <div class="card-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                <div class="card-seller">by <?php echo htmlspecialchars($p['seller_name']); ?></div>
                                <div class="card-footer">
                                    <span class="price">₱<?php echo number_format($p['price'], 2); ?></span>
                                    <span class="loc">📍 <?php echo htmlspecialchars($p['location'] ?? '—'); ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach;
                    else:
                        $placeholders = [
                            ['e'=>'🥦','n'=>'Fresh Broccoli','s'=>"Maria's Farm",'p'=>'45.00','l'=>'Quezon City'],
                            ['e'=>'🍅','n'=>'Ripe Tomatoes','s'=>"Juan's Garden",'p'=>'60.00','l'=>'Marikina'],
                            ['e'=>'🥚','n'=>'Native Eggs','s'=>'Lola Rosa','p'=>'120.00','l'=>'Pasig'],
                            ['e'=>'🌽','n'=>'Sweet Corn','s'=>'Farm Fresh PH','p'=>'35.00','l'=>'Caloocan'],
                            ['e'=>'🐟','n'=>'Fresh Tilapia','s'=>'Navotas Fish','p'=>'150.00','l'=>'Navotas'],
                            ['e'=>'🧄','n'=>'Garlic Bulbs','s'=>'Kuya Ben','p'=>'40.00','l'=>'Malabon'],
                            ['e'=>'🍎','n'=>'Red Apples','s'=>'Fruit Corner','p'=>'89.00','l'=>'Mandaluyong'],
                            ['e'=>'🥕','n'=>'Baby Carrots','s'=>'Veggie Stop','p'=>'55.00','l'=>'San Juan'],
                            ['e'=>'🧅','n'=>'White Onions','s'=>'Kuya Ben','p'=>'38.00','l'=>'Malabon'],
                            ['e'=>'🍊','n'=>'Sweet Oranges','s'=>'Fruit Corner','p'=>'75.00','l'=>'Mandaluyong'],
                            ['e'=>'🥬','n'=>'Pechay Tagalog','s'=>"Maria's Farm",'p'=>'28.00','l'=>'Quezon City'],
                            ['e'=>'🫑','n'=>'Green Capsicum','s'=>'Veggie Stop','p'=>'65.00','l'=>'San Juan'],
                        ];
                        foreach ($placeholders as $p): ?>
                        <div class="product-card" style="cursor:default;">
                            <div class="card-img"><?php echo $p['e']; ?><span class="card-badge">Local</span></div>
                            <div class="card-body">
                                <div class="card-name"><?php echo $p['n']; ?></div>
                                <div class="card-seller">by <?php echo $p['s']; ?></div>
                                <div class="card-footer">
                                    <span class="price">₱<?php echo $p['p']; ?></span>
                                    <span class="loc">📍 <?php echo $p['l']; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                    <div class="no-results" id="noResults">No products found.</div>
                </div>
            </div>

            <div>
                <div class="quick-links">
                    <a href="my_orders.php" class="quick-link">
                        <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                        <span>My Orders</span>
                    </a>
                    <a href="cart.php" class="quick-link">
                        <svg viewBox="0 0 24 24"><path d="M11 9h2V6h3V4h-3V1h-2v3H8v2h3v3zm-4 9c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zm-8.9-5h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0019.99 4H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63z"/></svg>
                        <span>My Cart</span>
                    </a>
                    <a href="profile.php" class="quick-link">
                        <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                        <span>Profile</span>
                    </a>
                    <a href="product.php" class="quick-link">
                        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                        <span>Browse</span>
                    </a>
                </div>

                <div class="panel-card">
                    <div class="section-head">
                        <span class="section-title">Recent Orders</span>
                        <a href="my_orders.php" class="section-link">All →</a>
                    </div>
                    <?php if (!empty($recent_orders)):
                        foreach ($recent_orders as $o):
                            $st = strtolower($o['status']);
                    ?>
                        <div class="order-row">
                            <div style="flex:1;min-width:0;">
                                <div class="order-product"><?php echo htmlspecialchars($o['product_name']); ?></div>
                                <div class="order-meta">₱<?php echo number_format($o['total_price'], 2); ?> · <?php echo date('M d', strtotime($o['order_date'])); ?></div>
                            </div>
                            <span class="status-badge status-<?php echo $st; ?>"><?php echo ucfirst($st); ?></span>
                        </div>
                    <?php endforeach;
                    else: ?>
                        <div class="empty">No orders yet.<br>Start shopping! 🛒</div>
                    <?php endif; ?>
                </div>

                <div class="panel-card">
                    <div class="section-head"><span class="section-title">Your Location</span></div>
                    <?php if (!empty($user_info['location'])): ?>
                        <p style="font-size:13px;color:rgba(255,255,255,0.5);">📍 <?php echo htmlspecialchars($user_info['location']); ?></p>
                    <?php else: ?>
                        <p style="font-size:13px;color:rgba(255,255,255,0.25);">No location set yet.</p>
                    <?php endif; ?>
                    <a href="profile.php" style="font-size:12px;color:rgba(255,184,30,0.7);text-decoration:none;display:block;margin-top:8px;">Update location →</a>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    function filterProducts() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        const cards = document.querySelectorAll('#productGrid .product-card');
        let visible = 0;
        cards.forEach(card => {
            const match = (card.dataset.name || '').includes(q);
            card.style.display = match ? 'block' : 'none';
            if (match) visible++;
        });
        document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
        document.getElementById('productCount').textContent = visible + ' items';
    }
    function setCategory(btn) {
        document.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }
</script>
</body>
</html>