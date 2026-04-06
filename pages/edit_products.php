<?php
include __DIR__ . "/../includes/auth.php";
requireLogin();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id          = intval($_POST["id"]);
    $name        = trim($_POST["name"]);
    $description = trim($_POST["description"]);
    $price       = floatval($_POST["price"]);
    $location    = trim($_POST["location"]);
    $user_id     = $_SESSION["user_id"];

    // Only allow editing own products
    $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, location=? WHERE id=? AND user_id=?");
    $stmt->bind_param("ssdsis", $name, $description, $price, $location, $id, $user_id);
    $stmt->execute();
}

header("Location: my_listings.php");
exit();