<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'buyer';
$query   = isset($_GET['q']) ? trim($_GET['q']) : '';
$search  = "%$query%";

$products = [];
$people   = [];

if ($query !== '') {
    if ($role === 'buyer') {
        // Search products by product name OR seller name
        $stmt = $conn->prepare("
            SELECT p.*, u.username as seller_name 
            FROM products p 
            LEFT JOIN users u ON p.user_id = u.id 
            WHERE p.name LIKE ? OR u.username LIKE ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("ss", $search, $search);
        $stmt->execute();
        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Search sellers by name
        $stmt2 = $conn->prepare("
            SELECT DISTINCT u.id, u.username,
                COUNT(p.id) as listing_count
            FROM users u
            LEFT JOIN products p ON p.user_id = u.id
            WHERE u.username LIKE ? AND u.id != ?
            GROUP BY u.id
            HAVING listing_count > 0
        ");
        $stmt2->bind_param("si", $search, $user_id);
        $stmt2->execute();
        $people = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

    } elseif ($role === 'seller') {
        // Search buyers who ordered from this seller's products
        $stmt = $conn->prepare("
            SELECT DISTINCT u.id, u.username,
                COUNT(o.id) as order_count,
                SUM(o.total_price) as total_spent,
                MAX(o.order_date) as last_order
            FROM orders o
            LEFT JOIN users u ON o.customer_id = u.id
            LEFT JOIN products p ON o.product_id = p.id
            WHERE u.username LIKE ? AND p.user_id = ?
            GROUP BY u.id
            ORDER BY last_order DESC
        ");
        $stmt->bind_param("si", $search, $user_id);
        $stmt->execute();
        $people = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Also search products of this seller by name
        $stmt3 = $conn->prepare("
            SELECT p.*, u.username as seller_name 
            FROM products p 
            LEFT JOIN users u ON p.user_id = u.id 
            WHERE p.user_id = ? AND p.name LIKE ?
            ORDER BY p.created_at DESC
        ");
        $stmt3->bind_param("is", $user_id, $search);
        $stmt3->execute();
        $products = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$total_results = count($products) + count($people);
$emojis = ['🥦','🍅','🥚','🌽','🐟','🧄','🍎','🥕','🧅','🍊','🥬','🫑'];
$username = htmlspecialchars($_SESSION['username']);
$initials = strtoupper(substr($username, 0, 1));
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search<?php echo $query ? ' — ' . htmlspecialchars($query) : ''; ?> | NearBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { box-sizing: border-box; }

        /* NAVBAR SEARCH */
        .navbar-search { flex: 1; max-width: 380px; margin: 0 20px; }
        .navbar-search-wrap { position: relative; }
        .navbar-search-wrap svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; fill: rgba(255,255,255,0.25); pointer-events: none; }
        .navbar-search-input { width: 100%; padding: 9px 14px 9px 36px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.09); border-radius: 10px; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color 0.2s, background 0.2s; }
        .navbar-search-input:focus { border-color: rgba(255,184,30,0.45); background: rgba(255,184,30,0.04); }
        .navbar-search-input::placeholder { color: rgba(255,255,255,0.22); }

        /* CART */
        .cart-nav-btn { position: relative; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; text-decoration: none; transition: all 0.2s; }
        .cart-nav-btn:hover { border-color: rgba(255,184,30,0.4); background: rgba(255,184,30,0.08); }
        .cart-nav-btn svg { width: 18px; height: 18px; fill: rgba(255,255,255,0.6); }
        .cart-badge { position: absolute; top: -6px; right: -6px; background: #ffb81e; color: #0d0d0d; font-size: 10px; font-weight: 500; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

        /* PAGE */
        .main-content { flex: 1; min-width: 0; padding: 48px; }

        .search-hero { margin-bottom: 32px; }
        .search-hero h1 { font-family: 'DM Serif Display', serif; font-size: 30px; color: #fff; letter-spacing: -0.5px; margin-bottom: 18px; }
        .search-hero h1 em { font-style: italic; color: #ffb81e; }

        .search-form { display: flex; gap: 10px; max-width: 580px; }
        .search-input-wrap { flex: 1; position: relative; }
        .search-input-wrap svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; fill: rgba(255,255,255,0.25); pointer-events: none; }
        .search-input { width: 100%; padding: 12px 14px 12px 44px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 14px; outline: none; transition: border-color 0.2s; }
        .search-input:focus { border-color: rgba(255,184,30,0.5); background: rgba(255,184,30,0.04); }
        .search-input::placeholder { color: rgba(255,255,255,0.25); }
        .btn-search { padding: 12px 22px; background: linear-gradient(135deg, #ffb81e, #ff6b35); border: none; border-radius: 12px; color: #0d0d0d; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500; cursor: pointer; transition: opacity 0.15s; white-space: nowrap; }
        .btn-search:hover { opacity: 0.88; }

        /* RESULTS META */
        .results-meta { font-size: 13px; color: rgba(255,255,255,0.3); margin-bottom: 28px; }
        .results-meta strong { color: rgba(255,255,255,0.7); }

        /* SECTION LABEL */
        .result-label { font-size: 11px; font-weight: 500; letter-spacing: 1px; text-transform: uppercase; color: #ffb81e; margin-bottom: 14px; display: flex; align-items: center; gap: 10px; }
        .result-label::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.06); }
        .result-section { margin-bottom: 36px; }

        /* PRODUCT GRID */
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 14px; }
        .product-card { background: #111; border: 1px solid rgba(255,255,255,0.07); border-radius: 14px; overflow: hidden; text-decoration: none; display: block; transition: border-color 0.2s, transform 0.2s; }
        .product-card:hover { border-color: rgba(255,184,30,0.25); transform: translateY(-3px); }
        .card-img { height: 100px; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; font-size: 42px; position: relative; }
        .card-badge { position: absolute; top: 8px; left: 8px; font-size: 10px; font-weight: 500; padding: 2px 8px; border-radius: 999px; background: rgba(255,184,30,0.15); color: #ffb81e; border: 1px solid rgba(255,184,30,0.25); }
        .card-body { padding: 12px 14px; }
        .card-name { font-size: 13px; font-weight: 500; color: #fff; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card-seller { font-size: 11px; color: rgba(255,255,255,0.3); margin-bottom: 8px; }
        .card-seller em { color: #ffb81e; font-style: normal; }
        .card-price { font-size: 14px; font-weight: 500; color: #ffb81e; }

        /* PEOPLE LIST */
        .people-list { display: flex; flex-direction: column; gap: 10px; }
        .person-card { background: #111; border: 1px solid rgba(255,255,255,0.07); border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; transition: border-color 0.2s; }
        .person-card:hover { border-color: rgba(255,184,30,0.2); }
        .person-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #ffb81e, #ff6b35); display: flex; align-items: center; justify-content: center; font-size: 17px; font-weight: 500; color: #0d0d0d; flex-shrink: 0; }
        .person-name { font-size: 15px; font-weight: 500; color: #fff; margin-bottom: 4px; }
        .person-meta { font-size: 12px; color: rgba(255,255,255,0.3); }
        .person-right { margin-left: auto; flex-shrink: 0; }
        .person-badge { font-size: 11px; font-weight: 500; padding: 4px 12px; border-radius: 999px; }
        .badge-seller { background: rgba(255,184,30,0.1); color: #ffb81e; border: 1px solid rgba(255,184,30,0.2); }
        .badge-buyer  { background: rgba(52,211,153,0.1); color: #34d399; border: 1px solid rgba(52,211,153,0.2); }

        /* EMPTY / PLACEHOLDER */
        .search-empty { text-align: center; padding: 80px 20px; color: rgba(255,255,255,0.15); }
        .search-empty .icon { font-size: 52px; margin-bottom: 14px; }
        .search-empty p { font-size: 14px; }
        .no-results { text-align: center; padding: 60px 20px; color: rgba(255,255,255,0.2); font-size: 14px; }
        .no-results .icon { font-size: 44px; margin-bottom: 12px; }
    </style>
</head>
<body>

<nav class="top-navbar">
    <a class="nav-brand" href="dashboard.php">
        <div class="brand-icon"><svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></div>
        <span class="brand-name">NearBuy</span>
    </a>

    <!-- NAVBAR SEARCH BAR -->
    <form method="GET" action="search.php" class="navbar-search">
        <div class="navbar-search-wrap">
            <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            <input type="text" name="q" class="navbar-search-input"
                   placeholder="<?php echo $role === 'seller' ? 'Search buyers...' : 'Search sellers or products...'; ?>"
                   value="<?php echo htmlspecialchars($query); ?>">
        </div>
    </form>

    <div class="nav-right">
        <?php if ($role === 'buyer'): ?>
        <a href="cart.php" class="cart-nav-btn">
            <svg viewBox="0 0 24 24"><path d="M11 9h2V6h3V4h-3V1h-2v3H8v2h3v3zm-4 9c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zm-8.9-5h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0019.99 4H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63z"/></svg>
            <?php if ($cart_count > 0): ?><span class="cart-badge"><?php echo $cart_count; ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
        <div class="user-pill">
            <div class="user-avatar"><?php echo $initials; ?></div>
            <span class="username-display"><?php echo $username; ?></span>
        </div>
    </div>
</nav>

<div class="page-body">
    <?php include_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main-content">

        <div class="search-hero">
            <h1>Search <em>NearBuy</em></h1>
            <form method="GET" action="search.php" class="search-form">
                <div class="search-input-wrap">
                    <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    <input type="text" name="q" class="search-input"
                           placeholder="<?php echo $role === 'seller' ? 'Search buyers by name...' : 'Search by seller name or product name...'; ?>"
                           value="<?php echo htmlspecialchars($query); ?>" autofocus>
                </div>
                <button type="submit" class="btn-search">Search</button>
            </form>
        </div>

        <?php if ($query === ''): ?>
            <div class="search-empty">
                <div class="icon">🔍</div>
                <p><?php echo $role === 'seller' ? 'Type a buyer name to search your customers' : 'Type a seller or product name to find what\'s near you'; ?></p>
            </div>

        <?php elseif ($total_results === 0): ?>
            <div class="no-results">
                <div class="icon">😕</div>
                <p>No results found for "<strong style="color:rgba(255,255,255,0.5);"><?php echo htmlspecialchars($query); ?></strong>"</p>
            </div>

        <?php else: ?>
            <p class="results-meta">
                Found <strong><?php echo $total_results; ?> result<?php echo $total_results !== 1 ? 's' : ''; ?></strong>
                for "<strong><?php echo htmlspecialchars($query); ?></strong>"
            </p>

            <!-- PEOPLE RESULTS -->
            <?php if (!empty($people)): ?>
            <div class="result-section">
                <p class="result-label">
                    <?php echo $role === 'seller' ? '🧑 Buyers' : '🏪 Sellers'; ?>
                </p>
                <div class="people-list">
                    <?php foreach ($people as $person): ?>
                        <div class="person-card">
                            <div class="person-avatar"><?php echo strtoupper(substr($person['username'], 0, 1)); ?></div>
                            <div>
                                <p class="person-name"><?php echo htmlspecialchars($person['username']); ?></p>
                                <p class="person-meta">
                                    <?php if ($role === 'seller'): ?>
                                        <?php echo $person['order_count']; ?> order<?php echo $person['order_count'] != 1 ? 's' : ''; ?>
                                        · ₱<?php echo number_format($person['total_spent'], 2); ?> total spent
                                        · Last order <?php echo date('M d, Y', strtotime($person['last_order'])); ?>
                                    <?php else: ?>
                                        <?php echo $person['listing_count']; ?> listing<?php echo $person['listing_count'] != 1 ? 's' : ''; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="person-right">
                                <span class="person-badge <?php echo $role === 'seller' ? 'badge-buyer' : 'badge-seller'; ?>">
                                    <?php echo $role === 'seller' ? 'Buyer' : 'Seller'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- PRODUCT RESULTS -->
            <?php if (!empty($products)): ?>
            <div class="result-section">
                <p class="result-label">
                    <?php echo $role === 'seller' ? '📦 Your Products' : '🛍️ Products'; ?>
                </p>
                <div class="product-grid">
                    <?php foreach ($products as $i => $p):
                        $emoji = $emojis[$i % count($emojis)];
                        $link  = $role === 'buyer' ? "product.php?id={$p['id']}" : "edit_products.php?id={$p['id']}";
                    ?>
                        <a href="<?php echo $link; ?>" class="product-card">
                            <div class="card-img">
                                <?php echo $emoji; ?>
                                <span class="card-badge">Local</span>
                            </div>
                            <div class="card-body">
                                <div class="card-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                <div class="card-seller">by <em><?php echo htmlspecialchars($p['seller_name']); ?></em></div>
                                <div class="card-price">₱<?php echo number_format($p['price'], 2); ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        <?php endif; ?>

    </main>
</div>

</body>
</html>