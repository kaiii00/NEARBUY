<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

// Get product ID
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch product
$stmt = $conn->prepare("SELECT p.*, u.username as seller_name FROM products p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: dashboard.php");
    exit();
}

// Prevent buying your own product
$is_own_product = $product['user_id'] == $_SESSION['user_id'];

// Handle Add to Cart
$success = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if ($is_own_product) {
        $error = "You cannot add your own product to cart.";
    } else {
        $qty = max(1, intval($_POST['quantity']));

        // Cart stored in session as array of [product_id => quantity]
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $qty;
        } else {
            $_SESSION['cart'][$product_id] = $qty;
        }

        $success = "Added to cart!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - NearBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .main-content { flex: 1; min-width: 0; padding: 48px; }

        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 13px; color: rgba(255,255,255,0.35);
            text-decoration: none; margin-bottom: 32px;
            transition: color 0.15s;
        }
        .back-link:hover { color: #fff; }
        .back-link svg { width: 16px; height: 16px; fill: rgba(255,255,255,0.35); transition: fill 0.15s; }
        .back-link:hover svg { fill: #fff; }

        .product-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            align-items: start;
        }

        /* IMAGE */
        .product-image {
            background: #111;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 18px;
            aspect-ratio: 1;
            display: flex; align-items: center; justify-content: center;
            font-size: 100px;
        }

        /* INFO */
        .product-info { display: flex; flex-direction: column; gap: 20px; }

        .product-tag {
            display: inline-block;
            font-size: 11px; font-weight: 500;
            letter-spacing: 0.8px; text-transform: uppercase;
            color: #ffb81e; background: rgba(255,184,30,0.1);
            border-radius: 6px; padding: 4px 10px;
            width: fit-content;
        }

        .product-name {
            font-family: 'DM Serif Display', serif;
            font-size: 32px; color: #fff; line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .product-price {
            font-size: 30px; font-weight: 500; color: #ffb81e;
        }

        .product-desc {
            font-size: 14px; color: rgba(255,255,255,0.45);
            line-height: 1.7;
            padding: 16px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
        }

        .product-meta { display: flex; flex-direction: column; gap: 10px; }
        .meta-row {
            display: flex; align-items: center; gap: 10px;
            font-size: 13px; color: rgba(255,255,255,0.35);
        }
        .meta-row svg { width: 15px; height: 15px; fill: rgba(255,255,255,0.25); flex-shrink: 0; }
        .meta-row span { color: rgba(255,255,255,0.6); }

        /* CART FORM */
        .cart-form { display: flex; flex-direction: column; gap: 14px; }

        .qty-row { display: flex; align-items: center; gap: 12px; }
        .qty-label { font-size: 12px; font-weight: 500; letter-spacing: 0.8px; text-transform: uppercase; color: rgba(255,255,255,0.3); }
        .qty-control {
            display: flex; align-items: center; gap: 0;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 10px; overflow: hidden;
        }
        .qty-btn {
            width: 38px; height: 38px;
            background: none; border: none;
            color: rgba(255,255,255,0.5); font-size: 18px;
            cursor: pointer; font-family: 'DM Sans', sans-serif;
            transition: background 0.15s, color 0.15s;
            display: flex; align-items: center; justify-content: center;
        }
        .qty-btn:hover { background: rgba(255,255,255,0.07); color: #fff; }
        .qty-input {
            width: 48px; height: 38px;
            background: none; border: none;
            color: #fff; font-size: 15px; font-weight: 500;
            text-align: center; font-family: 'DM Sans', sans-serif;
            outline: none;
        }

        .btn-cart {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px 24px;
            background: linear-gradient(135deg, #ffb81e, #ff6b35);
            border: none; border-radius: 12px;
            color: #0d0d0d; font-family: 'DM Sans', sans-serif;
            font-size: 15px; font-weight: 500; cursor: pointer;
            transition: opacity 0.18s, transform 0.15s;
        }
        .btn-cart:hover { opacity: 0.88; transform: translateY(-1px); }
        .btn-cart svg { width: 17px; height: 17px; fill: #0d0d0d; }
        .btn-cart:disabled { opacity: 0.35; cursor: not-allowed; transform: none; }

        .btn-view-cart {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px 24px;
            background: transparent;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            color: rgba(255,255,255,0.6); font-family: 'DM Sans', sans-serif;
            font-size: 14px; text-decoration: none;
            transition: border-color 0.2s, color 0.2s;
        }
        .btn-view-cart:hover { border-color: rgba(255,255,255,0.3); color: #fff; }

        .alert { padding: 12px 16px; border-radius: 10px; font-size: 13px; }
        .alert-success { background: rgba(52,211,153,0.1); border: 1px solid rgba(52,211,153,0.25); color: #34d399; }
        .alert-error   { background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.2); color: #f87171; }

        .own-product-notice {
            padding: 14px 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            font-size: 13px; color: rgba(255,255,255,0.3);
            text-align: center;
        }

        @media (max-width: 768px) {
            .product-layout { grid-template-columns: 1fr; }
        }
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
    <?php include_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main-content">

        <a href="dashboard.php" class="back-link">
            <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Back to listings
        </a>

        <div class="product-layout">

            <!-- IMAGE -->
            <div class="product-image">🛍️</div>

            <!-- INFO -->
            <div class="product-info">
                <span class="product-tag">Local Product</span>
                <h1 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>

                <p class="product-desc"><?php echo htmlspecialchars($product['description']); ?></p>

                <div class="product-meta">
                    <div class="meta-row">
                        <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                        <span><?php echo htmlspecialchars($product['location']); ?></span>
                    </div>
                    <div class="meta-row">
                        <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        Sold by <span><?php echo htmlspecialchars($product['seller_name']); ?></span>
                    </div>
                    <div class="meta-row">
                        <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm4.24 16L12 15.45 7.77 18l1.12-4.81-3.73-3.23 4.92-.42L12 5l1.92 4.53 4.92.42-3.73 3.23L16.23 18z"/></svg>
                        Listed on <span><?php echo date('M d, Y', strtotime($product['created_at'])); ?></span>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?> <a href="cart.php" style="color:#34d399; font-weight:500;">View Cart →</a></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($is_own_product): ?>
                    <div class="own-product-notice">This is your own listing — you can't buy it.</div>
                <?php else: ?>
                    <form method="POST" class="cart-form">
                        <div class="qty-row">
                            <span class="qty-label">Qty</span>
                            <div class="qty-control">
                                <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                                <input type="number" name="quantity" id="qty" class="qty-input" value="1" min="1" max="99">
                                <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                            </div>
                        </div>
                        <button type="submit" name="add_to_cart" class="btn-cart">
                            <svg viewBox="0 0 24 24"><path d="M11 9h2V6h3V4h-3V1h-2v3H8v2h3v3zm-4 9c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zm-8.9-5h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0019.99 4H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63z"/></svg>
                            Add to Cart
                        </button>
                        <a href="cart.php" class="btn-view-cart">View Cart</a>
                    </form>
                <?php endif; ?>

            </div>
        </div>

    </main>
</div>

<script>
    function changeQty(delta) {
        const input = document.getElementById('qty');
        const newVal = Math.max(1, Math.min(99, parseInt(input.value) + delta));
        input.value = newVal;
    }
</script>

</body>
</html>