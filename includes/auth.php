<?php
include __DIR__ . "/config.php";

function requireLogin() {
    if (!isset($_SESSION['username'])) {
        header("Location: ../pages/login.php");
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header("Location: ../pages/dashboard.php");
        exit();
    }
}
?>