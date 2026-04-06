<?php
include 'includes/config.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connection successful!";
}
?>