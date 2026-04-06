<?php
// Fix: Use __DIR__ to ensure the database config is found correctly
require_once __DIR__ . "/../includes/config.php"; 
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

$sql = "SELECT * FROM products ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NearBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="../assests/css/style.css">
</head>
<body>

<nav>
    <a class="nav-brand" href="#">
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

    <div class="main-content">
        <div class="hero">
            <p class="hero-label">Good day, <?php echo htmlspecialchars($_SESSION["username"]); ?></p>
            <h1>What are you looking<br>for <em>nearby</em>?</h1>
            <p>Fresh finds from sellers in your area</p>
        </div>

        <div class="tabs">
            <div class="tab active">All</div>
            <div class="tab">Grocery</div>
            <div class="tab">Electronics</div>
            <div class="tab">Clothing</div>
            <div class="tab">Home</div>
        </div>

        <div class="section">
            <div class="section-header">
                <p class="section-title">Local listings</p>
                <?php if ($result) echo "<span class='section-count'>" . $result->num_rows . " items</span>"; ?>
            </div>
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="product-grid">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="product-card">
                            <div class="card-img-placeholder">
                                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                            </div>
                            <div class="card-body">
                                <span class="card-tag">Local</span>
                                <p class="card-name"><?php echo htmlspecialchars($row['name']); ?></p>
                                <p class="card-desc"><?php echo htmlspecialchars($row['description']); ?></p>
                                <div class="card-footer">
                                    <span class="price">₱<?php echo number_format($row['price'], 2); ?></span>
                                    <span class="location">
                                        <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                                        <?php echo htmlspecialchars($row['location']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">No products listed yet.</div>
            <?php endif; ?>
        </div>

        <div class="divider"></div>

        <?php
        $groceries = [
            ["name" => "Farm Eggs",   "unit" => "per dozen",  "price" => "90.00",  "category" => "Dairy & Eggs", "photo" => "https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?w=400&q=80"],
            ["name" => "White Rice",  "unit" => "per kilo",   "price" => "52.00",  "category" => "Grains",       "photo" => "https://images.unsplash.com/photo-1586201375761-83865001e31c?w=400&q=80"],
            ["name" => "Broccoli",    "unit" => "per bundle", "price" => "45.00",  "category" => "Vegetables",   "photo" => "https://images.unsplash.com/photo-1459411621453-7b03977f4bfc?w=400&q=80"],
            ["name" => "Red Onion",   "unit" => "per kilo",   "price" => "70.00",  "category" => "Vegetables",   "photo" => "https://images.unsplash.com/photo-1618512496248-a07fe83aa8cb?w=400&q=80"],
            ["name" => "Chicken",     "unit" => "per kilo",   "price" => "185.00", "category" => "Meat",         "photo" => "https://images.unsplash.com/photo-1604503468506-a8da13d11bbc?w=400&q=80"],
            ["name" => "Fresh Milk",  "unit" => "per liter",  "price" => "68.00",  "category" => "Dairy & Eggs", "photo" => "https://images.unsplash.com/photo-1563636619-e9143da7973b?w=400&q=80"],
            ["name" => "Whole Bread", "unit" => "per loaf",   "price" => "55.00",  "category" => "Bakery",       "photo" => "https://images.unsplash.com/photo-1509440159596-0249088772ff?w=400&q=80"],
            ["name" => "Garlic",      "unit" => "per head",   "price" => "12.00",  "category" => "Vegetables",   "photo" => "https://images.unsplash.com/photo-1540148426945-6cf22a6b2383?w=400&q=80"],
            ["name" => "Tomatoes",    "unit" => "per kilo",   "price" => "60.00",  "category" => "Vegetables",   "photo" => "https://images.unsplash.com/photo-1546470427-e26264be0b11?w=400&q=80"],
            ["name" => "Bangus",      "unit" => "per kilo",   "price" => "160.00", "category" => "Seafood",      "photo" => "https://images.unsplash.com/photo-1534482421-64566f976cfa?w=400&q=80"],
            ["name" => "Carrots",     "unit" => "per kilo",   "price" => "50.00",  "category" => "Vegetables",   "photo" => "https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?w=400&q=80"],
            ["name" => "Calamansi",   "unit" => "per kilo",   "price" => "40.00",  "category" => "Fruits",       "photo" => "https://images.unsplash.com/photo-1587486913049-53fc88980cfc?w=400&q=80"],
        ];
        ?>

        <div class="section">
            <div class="section-header">
                <p class="section-title">Grocery essentials</p>
                <span class="section-count"><?php echo count($groceries); ?> items</span>
            </div>
            <div class="grocery-grid">
                <?php foreach ($groceries as $item): ?>
                    <div class="grocery-card">
                        <div class="grocery-photo-wrap">
                            <img class="grocery-photo" src="<?php echo $item['photo']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                        </div>
                        <div class="grocery-body">
                            <span class="grocery-tag"><?php echo htmlspecialchars($item['category']); ?></span>
                            <p class="grocery-name"><?php echo htmlspecialchars($item['name']); ?></p>
                            <p class="grocery-unit"><?php echo htmlspecialchars($item['unit']); ?></p>
                            <div class="grocery-footer">
                                <span class="grocery-price">₱<?php echo $item['price']; ?></span>
                                <span class="add-btn">+</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>