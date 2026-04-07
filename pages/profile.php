<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SESSION['role'] === 'seller') {
    include __DIR__ . '/seller_profile.php';
} else {
    include __DIR__ . '/buyer_profile.php';
}