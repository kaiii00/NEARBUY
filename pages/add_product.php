<?php
include __DIR__ . "/../includes/auth.php";
requireLogin();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name        = trim($_POST["name"]);
    $description = trim($_POST["description"]);
    $price       = floatval($_POST["price"]);
    $location    = trim($_POST["location"]);
    $user_id     = $_SESSION["user_id"];

    // Ensure your database column is named 'user_id' to match this query
    $stmt = $conn->prepare("INSERT INTO products (user_id, name, description, price, location) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issds", $user_id, $name, $description, $price, $location);
    
    if($stmt->execute()) {
        header("Location: my_listings.php?msg=added");
        exit();
    } else {
        $error = "Error: Could not save product.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product - NearBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* Reusing your Dashboard/Listings styles for consistency */
        body { font-family: 'DM Sans', sans-serif; background: #0d0d0d; color: #fff; margin: 0; padding: 40px; }
        .container { max-width: 500px; margin: 0 auto; background: #161616; padding: 40px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.08); }
        h1 { font-family: 'DM Serif Display', serif; font-size: 28px; margin-bottom: 8px; }
        p.subtitle { color: rgba(255,255,255,0.4); font-size: 14px; margin-bottom: 30px; }
        
        .field { margin-bottom: 20px; }
        label { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #ffb81e; margin-bottom: 8px; }
        input, textarea { 
            width: 100%; padding: 12px; background: rgba(255,255,255,0.05); 
            border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; 
            color: #fff; font-family: inherit; outline: none; box-sizing: border-box;
        }
        input:focus, textarea:focus { border-color: #ffb81e; }
        
        .btn-group { display: flex; gap: 12px; margin-top: 30px; }
        .btn-save { 
            flex: 2; padding: 14px; border: none; border-radius: 12px;
            background: linear-gradient(135deg, #ffb81e, #ff6b35);
            color: #0d0d0d; font-weight: 600; cursor: pointer;
        }
        .btn-cancel { 
            flex: 1; padding: 14px; border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 12px; background: transparent; color: #fff; 
            text-align: center; text-decoration: none; font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>List New Product</h1>
    <p class="subtitle">Enter the details of the item you want to sell.</p>

    <?php if(isset($error)): ?>
        <p style="color: #ff6b6b; font-size: 13px;"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="field">
            <label>Product Name</label>
            <input type="text" name="name" placeholder="e.g. Vintage Camera" required>
        </div>

        <div class="field">
            <label>Price (₱)</label>
            <input type="number" name="price" step="0.01" placeholder="0.00" required>
        </div>

        <div class="field">
            <label>Location</label>
            <input type="text" name="location" placeholder="e.g. Manila, PH" required>
        </div>

        <div class="field">
            <label>Description</label>
            <textarea name="description" rows="4" placeholder="Tell buyers about your item..."></textarea>
        </div>

        <div class="btn-group">
            <a href="my_listings.php" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-save">Post Listing</button>
        </div>
    </form>
</div>

</body>
</html>