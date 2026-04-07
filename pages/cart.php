<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Handle remove item
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    unset($_SESSION['cart'][$remove_id]);
    header("Location: cart.php");
    exit();
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $pid => $qty) {
        $qty = intval($qty);
        if ($qty <= 0) {
            unset($_SESSION['cart'][$pid]);
        } else {
            $_SESSION['cart'][intval($pid)] = $qty;
        }
    }
    header("Location: cart.php");
    exit();
}

// Handle checkout
$success = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    if (empty($_SESSION['cart'])) {
        $error = "Your cart is empty.";
    } else {
        $conn->begin_transaction();
        try {
            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                // Get product price
                $pstmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
                $pstmt->bind_param("i", $product_id);
                $pstmt->execute();
                $prow = $pstmt->get_result()->fetch_assoc();

                if (!$prow) continue;

                $total_price = $prow['price'] * $quantity;
                $customer_id = $_SESSION['user_id'];
                $status      = 'pending';
                $order_date  = date('Y-m-d H:i:s');

                $ostmt = $conn->prepare("INSERT INTO orders (customer_id, product_id, total_price, status, order_date) VALUES (?, ?, ?, ?, ?)");
                $ostmt->bind_param("iidss", $customer_id, $product_id, $total_price, $status, $order_date);
                $ostmt->execute();
            }

            $conn->commit();
            $_SESSION['cart'] = []; // Clear cart
            header("Location: my_orders.php?msg=ordered");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Checkout failed. Please try again.";
        }
    }
}

// Fetch cart product details
$cart_items  = [];
$grand_total = 0;

if (!empty($_SESSION['cart'])) {
    $ids         = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $products    = $conn->query("SELECT * FROM products WHERE id IN ($ids)");

    while ($row = $products->fetch_assoc()) {
        $qty           = $_SESSION['cart'][$row['id']];
        $subtotal      = $row['price'] * $qty;
        $grand_total  += $subtotal;
        $cart_items[]  = array_merge($row, ['qty' => $qty, 'subtotal' => $subtotal]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - NearBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .main-content { flex: 1; min-width: 0; padding: 48px; }

        .page-header { margin-bottom: 32px; }
        .page-label {
            font-size: 12px; font-weight: 500;
            letter-spacing: 1px; text-transform: uppercase;
            color: #ffb81e; margin-bottom: 8px;
        }
        .page-header h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 32px; letter-spacing: -0.5px; color: #fff;
        }
        .page-header h1 em { font-style: italic; color: #ffb81e; }

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 24px;
            align-items: start;
        }

        /* CART ITEMS */
        .cart-items { display: flex; flex-direction: column; gap: 12px; }

        .cart-card {
            background: #111;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px;
            padding: 18px 20px;
            display: flex; align-items: center; gap: 16px;
            transition: border-color 0.2s;
        }
        .cart-card:hover { border-color: rgba(255,184,30,0.2); }

        .cart-item-icon {
            width: 52px; height: 52px; border-radius: 12px;
            background: rgba(255,184,30,0.08);
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; flex-shrink: 0;
        }

        .cart-item-info { flex: 1; min-width: 0; }
        .cart-item-name { font-size: 15px; font-weight: 500; color: #fff; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cart-item-location { font-size: 12px; color: rgba(255,255,255,0.3); }

        .cart-item-qty {
            display: flex; align-items: center; gap: 0;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 9px; overflow: hidden;
        }
        .qty-btn {
            width: 32px; height: 32px;
            background: none; border: none;
            color: rgba(255,255,255,0.4); font-size: 16px;
            cursor: pointer; transition: background 0.15s, color 0.15s;
            display: flex; align-items: center; justify-content: center;
        }
        .qty-btn:hover { background: rgba(255,255,255,0.07); color: #fff; }
        .qty-input {
            width: 40px; height: 32px;
            background: none; border: none;
            color: #fff; font-size: 13px; font-weight: 500;
            text-align: center; font-family: 'DM Sans', sans-serif;
            outline: none;
        }

        .cart-item-price {
            font-size: 15px; font-weight: 500; color: #ffb81e;
            min-width: 80px; text-align: right;
        }

        .btn-remove {
            background: none; border: none;
            color: rgba(248,113,113,0.4); cursor: pointer;
            padding: 6px; border-radius: 7px;
            transition: background 0.15s, color 0.15s;
            display: flex; align-items: center;
        }
        .btn-remove:hover { background: rgba(248,113,113,0.1); color: #f87171; }
        .btn-remove svg { width: 16px; height: 16px; fill: currentColor; }

        .update-row { margin-top: 8px; }
        .btn-update {
            padding: 8px 16px; border-radius: 8px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5); font-family: 'DM Sans', sans-serif;
            font-size: 12px; cursor: pointer;
            transition: border-color 0.2s, color 0.2s;
        }
        .btn-update:hover { border-color: rgba(255,255,255,0.3); color: #fff; }

        /* SUMMARY */
        .cart-summary {
            background: #111;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px;
            padding: 24px;
            position: sticky; top: 80px;
        }
        .summary-title {
            font-family: 'DM Serif Display', serif;
            font-size: 20px; color: #fff; margin-bottom: 20px;
        }
        .summary-row {
            display: flex; justify-content: space-between;
            font-size: 13px; color: rgba(255,255,255,0.4);
            margin-bottom: 12px;
        }
        .summary-row.total {
            font-size: 18px; font-weight: 500;
            color: #fff; margin-top: 16px; padding-top: 16px;
            border-top: 1px solid rgba(255,255,255,0.07);
        }
        .summary-row.total span:last-child { color: #ffb81e; }

        .btn-checkout {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #ffb81e, #ff6b35);
            border: none; border-radius: 12px;
            color: #0d0d0d; font-family: 'DM Sans', sans-serif;
            font-size: 15px; font-weight: 500; cursor: pointer;
            margin-top: 20px; transition: opacity 0.18s;
        }
        .btn-checkout:hover { opacity: 0.88; }

        .btn-continue {
            display: block; text-align: center;
            margin-top: 12px; font-size: 13px;
            color: rgba(255,255,255,0.3); text-decoration: none;
            transition: color 0.15s;
        }
        .btn-continue:hover { color: rgba(255,255,255,0.7); }

        /* EMPTY */
        .empty-cart {
            text-align: center; padding: 80px 20px;
            color: rgba(255,255,255,0.2);
        }
        .empty-cart .empty-icon { font-size: 56px; margin-bottom: 16px; }
        .empty-cart p { font-size: 14px; margin-bottom: 24px; }
        .btn-shop {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 22px;
            background: linear-gradient(135deg, #ffb81e, #ff6b35);
            border-radius: 10px; color: #0d0d0d;
            font-size: 13px; font-weight: 500; text-decoration: none;
            transition: opacity 0.15s;
        }
        .btn-shop:hover { opacity: 0.85; }

        .alert { padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; }
        .alert-error { background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.2); color: #f87171; }

        @media (max-width: 768px) {
            .cart-layout { grid-template-columns: 1fr; }
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

        <div class="page-header">
            <p class="page-label">Buyer</p>
            <h1>My <em>Cart</em></h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($cart_items)): ?>

            <form method="POST">
                <div class="cart-layout">

                    <!-- ITEMS -->
                    <div>
                        <div class="cart-items">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-card">
                                    <div class="cart-item-icon">🛍️</div>
                                    <div class="cart-item-info">
                                        <p class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></p>
                                        <p class="cart-item-location">📍 <?php echo htmlspecialchars($item['location']); ?></p>
                                    </div>
                                    <div class="cart-item-qty">
                                        <button type="button" class="qty-btn" onclick="adjustQty(this, -1)">−</button>
                                        <input type="number" name="quantities[<?php echo $item['id']; ?>]"
                                               class="qty-input" value="<?php echo $item['qty']; ?>" min="1" max="99">
                                        <button type="button" class="qty-btn" onclick="adjustQty(this, 1)">+</button>
                                    </div>
                                    <div class="cart-item-price">
                                        ₱<?php echo number_format($item['subtotal'], 2); ?>
                                    </div>
                                    <a href="cart.php?remove=<?php echo $item['id']; ?>" class="btn-remove"
                                       onclick="return confirm('Remove this item?')">
                                        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="update-row">
                            <button type="submit" name="update_cart" class="btn-update">Update Cart</button>
                        </div>
                    </div>

                    <!-- SUMMARY -->
                    <div class="cart-summary">
                        <p class="summary-title">Order Summary</p>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="summary-row">
                                <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['qty']; ?></span>
                                <span>₱<?php echo number_format($item['subtotal'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>₱<?php echo number_format($grand_total, 2); ?></span>
                        </div>
                        <button type="submit" name="checkout" class="btn-checkout">Place Order</button>
                        <a href="dashboard.php" class="btn-continue">← Continue Shopping</a>
                    </div>

                </div>
            </form>

        <?php else: ?>
            <div class="empty-cart">
                <div class="empty-icon">🛒</div>
                <p>Your cart is empty.</p>
                <a href="dashboard.php" class="btn-shop">Browse Products</a>
            </div>
        <?php endif; ?>

    </main>
</div>

<script>
    function adjustQty(btn, delta) {
        const input = btn.parentElement.querySelector('.qty-input');
        input.value = Math.max(1, Math.min(99, parseInt(input.value) + delta));
    }
</script>

</body>
</html>