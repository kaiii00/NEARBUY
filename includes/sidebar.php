<?php 
$current_page = basename($_SERVER['PHP_SELF']); 
$role = $_SESSION['role'] ?? 'buyer';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-toggle" id="sidebarToggle">
        <svg class="toggle-icon" viewBox="0 0 24 24">
            <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
        </svg>
    </div>

    <nav class="nav-links">
        <a href="dashboard.php" class="nav-item <?php echo in_array($current_page, ['dashboard.php','buyer_dashboard.php','seller_dashboard.php']) ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
            <span class="nav-label">Dashboard</span>
            <span class="nav-tooltip">Dashboard</span>
        </a>

        <?php if ($role === 'buyer'): ?>
            <a href="my_orders.php" class="nav-item <?php echo ($current_page == 'my_orders.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
                <span class="nav-label">My Orders</span>
                <span class="nav-tooltip">My Orders</span>
            </a>

            <a href="cart.php" class="nav-item <?php echo ($current_page == 'cart.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24"><path d="M11 9h2V6h3V4h-3V1h-2v3H8v2h3v3zm-4 9c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zm-8.9-5h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0019.99 4H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63z"/></svg>
                <span class="nav-label">My Cart</span>
                <span class="nav-tooltip">My Cart</span>
            </a>

        <?php elseif ($role === 'seller'): ?>
            <a href="my_listings.php" class="nav-item <?php echo in_array($current_page, ['my_listings.php','add_product.php']) ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24"><path d="M20 6h-2.18c.07-.44.18-.88.18-1.34C18 2.99 16.54 1.5 14.83 1.5c-.89 0-1.7.38-2.28.96L12 3.03l-.55-.57A3.17 3.17 0 009.17 1.5C7.46 1.5 6 2.99 6 4.66c0 .46.11.9.18 1.34H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z"/></svg>
                <span class="nav-label">My Listings</span>
                <span class="nav-tooltip">My Listings</span>
            </a>

            <a href="add_product.php" class="nav-item <?php echo ($current_page == 'add_product.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                <span class="nav-label">Add Product</span>
                <span class="nav-tooltip">Add Product</span>
            </a>

            <a href="orders.php" class="nav-item <?php echo ($current_page == 'orders.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
                <span class="nav-label">Orders</span>
                <span class="nav-tooltip">Orders</span>
            </a>
        <?php endif; ?>

        <!-- Shared: Search -->
        <a href="search.php" class="nav-item <?php echo ($current_page == 'search.php') ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            <span class="nav-label">Search</span>
            <span class="nav-tooltip">Search</span>
        </a>

        <!-- Shared: Messages, Profile -->
        <a href="messages.php" class="nav-item <?php echo ($current_page == 'messages.php') ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
            <span class="nav-label">Messages</span>
            <span class="nav-tooltip">Messages</span>
        </a>

        <a href="profile.php" class="nav-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            <span class="nav-label">Profile</span>
            <span class="nav-tooltip">Profile</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a href="logout.php" class="nav-item logout">
            <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
            <span class="nav-label">Logout</span>
            <span class="nav-tooltip">Logout</span>
        </a>
    </div>
</aside>

<script>
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (localStorage.getItem('sidebar_collapsed') === 'true') {
        sidebar.classList.add('collapsed');
    }

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
    });
</script>