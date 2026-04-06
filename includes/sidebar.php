<?php
// Get the current filename (e.g., 'dashboard.php' or 'my_orders.php')
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-toggle" id="sidebarToggle">
        <svg class="toggle-icon" viewBox="0 0 24 24">
            <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
        </svg>
    </div>

    <nav class="nav-links">
        <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
            <span class="nav-label">Dashboard</span>
        </a>

        <a href="my_orders.php" class="nav-item <?php echo ($current_page == 'my_orders.php') ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
            <span class="nav-label">My Orders</span>
        </a>

        <a href="my_listings.php" class="nav-item <?php echo ($current_page == 'my_listings.php' || $current_page == 'add_product.php') ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M20 6h-2.18c.07-.44.18-.88.18-1.34C18 2.99 16.54 1.5 14.83 1.5c-.89 0-1.7.38-2.28.96L12 3.03l-.55-.57A3.17 3.17 0 009.17 1.5C7.46 1.5 6 2.99 6 4.66c0 .46.11.9.18 1.34H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z"/></svg>
            <span class="nav-label">My Listings</span>
        </a>

        <a href="messages.php" class="nav-item <?php echo ($current_page == 'messages.php') ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
            <span class="nav-label">Messages</span>
        </a>

        <a href="profile.php" class="nav-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            <span class="nav-label">Profile</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a href="logout.php" class="nav-item logout">
            <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
            <span class="nav-label">Logout</span>
        </a>
    </div>
</aside>

<script>
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');

    // Persistence for the sidebar state
    if (localStorage.getItem('sidebar_collapsed') === 'true') {
        sidebar.classList.add('collapsed');
    }

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
    });
</script>