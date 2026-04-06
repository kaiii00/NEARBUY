<?php
// Prevent double-starting sessions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "127.0.0.1"; // '127.0.0.1' is more stable than 'localhost' on some XAMPP setups
$port = 3307; 
$user = "root";
$pass = "";
$db   = "nearbuy_db";

// Create connection with the explicit port
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>