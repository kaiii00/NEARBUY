<?php
include __DIR__ . "/../includes/auth.php";
requireLogin();

$user_id = $_SESSION["user_id"];
$success = isset($_GET['msg']) && $_GET['msg'] == 'deleted' ? "Product deleted successfully." : "";
$error = "";

// Handle delete
if (isset($_GET["delete"])) {
    $del_id = intval($_GET["delete"]);
    
    // Safety check: ensure user owns the product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $del_id, $user_id);
    
    if ($stmt->execute()) {
        // Redirect back to the same page without the ?delete in the URL
        header("Location: my_listings.php?msg=deleted");
        exit();
    } else {
        $error = "Failed to delete product.";
    }
}

// ... rest of your fetch code ...
// Fetch this user's products
$stmt = $conn->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings - NearBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #0d0d0d;
            min-height: 100vh;
            color: #fff;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(255,184,30,0.07) 0%, transparent 70%);
            top: -150px; right: -150px;
            border-radius: 50%;
            pointer-events: none; z-index: 0;
        }
        body::after {
            content: '';
            position: fixed;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(255,100,60,0.06) 0%, transparent 70%);
            bottom: -100px; left: -100px;
            border-radius: 50%;
            pointer-events: none; z-index: 0;
        }

        /* NAV */
        nav {
            position: sticky; top: 0; z-index: 200;
            background: rgba(13,13,13,0.88);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,0.07);
            padding: 0 24px; height: 64px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .nav-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .brand-icon {
            width: 30px; height: 30px;
            background: linear-gradient(135deg, #ffb81e, #ff6b35);
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
        }
        .brand-icon svg { width: 16px; height: 16px; fill: white; }
        .brand-name { font-family: 'DM Serif Display', serif; font-size: 18px; color: #fff; letter-spacing: -0.3px; }
        .nav-right { display: flex; align-items: center; gap: 16px; }
        .user-pill {
            display: flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 999px;
            padding: 6px 14px 6px 6px;
        }
        .user-avatar {
            width: 28px; height: 28px; border-radius: 50%;
            background: linear-gradient(135deg, #ffb81e, #ff6b35);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 500; color: #0d0d0d;
        }
        .username-display { font-size: 13px; color: rgba(255,255,255,0.8); }

        /* PAGE BODY */
        .page-body { display: flex; min-height: calc(100vh - 64px); position: relative; z-index: 1; }

        /* SIDEBAR */
        .sidebar {
            width: 220px;
            background: #0f0f0f;
            border-right: 1px solid rgba(255,255,255,0.07);
            display: flex; flex-direction: column;
            position: sticky; top: 64px;
            height: calc(100vh - 64px);
            flex-shrink: 0;
            transition: width 0.25s cubic-bezier(0.4,0,0.2,1);
            overflow: hidden; z-index: 100;
        }
        .sidebar.collapsed { width: 56px; }

        .sidebar-toggle {
            height: 48px;
            display: flex; align-items: center; justify-content: flex-end;
            padding: 0 16px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            cursor: pointer; flex-shrink: 0;
        }
        .sidebar.collapsed .sidebar-toggle { justify-content: center; padding: 0; }
        .toggle-icon { width: 18px; height: 18px; fill: rgba(255,255,255,0.3); transition: transform 0.25s, fill 0.2s; flex-shrink: 0; }
        .sidebar-toggle:hover .toggle-icon { fill: rgba(255,255,255,0.7); }
        .sidebar.collapsed .toggle-icon { transform: rotate(180deg); }

        .nav-links { display: flex; flex-direction: column; gap: 2px; padding: 12px 8px; flex: 1; overflow: hidden; }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px; border-radius: 9px; cursor: pointer;
            text-decoration: none; white-space: nowrap;
            transition: background 0.15s; overflow: hidden; position: relative;
        }
        .nav-item:hover { background: rgba(255,255,255,0.05); }
        .nav-item.active { background: rgba(255,184,30,0.1); }
        .nav-item svg { width: 17px; height: 17px; fill: rgba(255,255,255,0.3); flex-shrink: 0; transition: fill 0.15s; }
        .nav-item.active svg { fill: #ffb81e; }
        .nav-item:hover svg { fill: rgba(255,255,255,0.65); }
        .nav-label { font-size: 13px; color: rgba(255,255,255,0.45); transition: color 0.15s, opacity 0.2s; overflow: hidden; }
        .nav-item.active .nav-label { color: #ffb81e; }
        .nav-item:hover .nav-label { color: rgba(255,255,255,0.85); }
        .sidebar.collapsed .nav-label { opacity: 0; width: 0; pointer-events: none; }
        .sidebar.collapsed .nav-item { justify-content: center; }
        .nav-tooltip {
            display: none; position: absolute;
            left: calc(100% + 10px); top: 50%; transform: translateY(-50%);
            background: #1e1e1e; border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.85); font-size: 12px;
            padding: 5px 10px; border-radius: 6px; white-space: nowrap; z-index: 999;
        }
        .sidebar.collapsed .nav-item:hover .nav-tooltip { display: block; }
        .sidebar-bottom { border-top: 1px solid rgba(255,255,255,0.06); padding: 8px; }
        .nav-item.logout svg { fill: rgba(255,90,70,0.45); }
        .nav-item.logout .nav-label { color: rgba(255,90,70,0.6); }
        .nav-item.logout:hover { background: rgba(255,90,70,0.07); }
        .nav-item.logout:hover svg { fill: rgba(255,90,70,0.8); }
        .nav-item.logout:hover .nav-label { color: rgba(255,90,70,0.9); }

        /* MAIN */
        .main-content { flex: 1; min-width: 0; padding: 48px; animation: rise 0.6s cubic-bezier(0.22,1,0.36,1) both; }
        @keyframes rise { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }

        .page-header {
            display: flex; justify-content: space-between; align-items: flex-end;
            margin-bottom: 32px;
        }
        .page-header-left .page-label {
            font-size: 12px; font-weight: 500; letter-spacing: 1px;
            text-transform: uppercase; color: #ffb81e; margin-bottom: 8px;
        }
        .page-header-left h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 32px; letter-spacing: -0.5px; color: #fff;
        }

        .btn-primary {
            display: flex; align-items: center; gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #ffb81e, #ff6b35);
            border: none; border-radius: 10px;
            color: #0d0d0d; font-family: 'DM Sans', sans-serif;
            font-size: 13px; font-weight: 500;
            text-decoration: none; cursor: pointer;
            transition: opacity 0.18s, transform 0.15s;
        }
        .btn-primary:hover { opacity: 0.88; transform: translateY(-1px); }
        .btn-primary svg { width: 15px; height: 15px; fill: #0d0d0d; }

        /* ALERTS */
        .alert {
            padding: 12px 16px; border-radius: 10px;
            font-size: 13px; margin-bottom: 24px;
        }
        .alert-success { background: rgba(29,158,117,0.1); border: 1px solid rgba(29,158,117,0.25); color: #1D9E75; }
        .alert-error   { background: rgba(255,107,107,0.08); border: 1px solid rgba(255,107,107,0.2); color: #ff6b6b; }

        /* TABLE */
        .table-wrap {
            background: #111111;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px; overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            padding: 14px 18px;
            font-size: 11px; font-weight: 500;
            letter-spacing: 0.8px; text-transform: uppercase;
            color: rgba(255,255,255,0.28);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            text-align: left;
        }
        tbody tr { border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: rgba(255,255,255,0.02); }
        tbody td { padding: 14px 18px; font-size: 13px; color: rgba(255,255,255,0.75); vertical-align: middle; }

        .td-name { font-weight: 500; color: #fff; }
        .td-desc { color: rgba(255,255,255,0.35); font-size: 12px; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .td-price { color: #ffb81e; font-weight: 500; }
        .td-location { font-size: 12px; color: rgba(255,255,255,0.35); }

        .action-btns { display: flex; gap: 8px; }
        .btn-edit, .btn-delete {
            padding: 6px 14px; border-radius: 7px;
            font-size: 12px; font-weight: 500;
            cursor: pointer; text-decoration: none;
            border: none; font-family: 'DM Sans', sans-serif;
            transition: opacity 0.15s, transform 0.15s;
        }
        .btn-edit {
            background: rgba(255,184,30,0.1);
            border: 1px solid rgba(255,184,30,0.25);
            color: #ffb81e;
        }
        .btn-edit:hover { background: rgba(255,184,30,0.2); }
        .btn-delete {
            background: rgba(255,90,70,0.08);
            border: 1px solid rgba(255,90,70,0.2);
            color: rgba(255,90,70,0.8);
        }
        .btn-delete:hover { background: rgba(255,90,70,0.15); }

        .empty-state { text-align: center; padding: 60px 20px; color: rgba(255,255,255,0.2); font-size: 14px; }

        /* EDIT MODAL */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 500;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(4px);
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: #161616;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px; padding: 36px;
            width: 100%; max-width: 460px;
            animation: rise 0.3s cubic-bezier(0.22,1,0.36,1) both;
        }
        .modal h2 { font-family: 'DM Serif Display', serif; font-size: 22px; color: #fff; margin-bottom: 6px; }
        .modal .subtitle { font-size: 13px; color: rgba(255,255,255,0.3); margin-bottom: 24px; }

        .field { margin-bottom: 14px; }
        .field label { display: block; font-size: 11px; font-weight: 500; letter-spacing: 0.8px; text-transform: uppercase; color: rgba(255,255,255,0.35); margin-bottom: 7px; }
        .field input, .field textarea {
            width: 100%; padding: 11px 14px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 10px; color: #fff;
            font-family: 'DM Sans', sans-serif; font-size: 14px;
            outline: none; resize: vertical;
            transition: border-color 0.2s, background 0.2s;
        }
        .field input:focus, .field textarea:focus {
            border-color: rgba(255,184,30,0.5);
            background: rgba(255,184,30,0.04);
        }
        .field textarea { min-height: 80px; }

        .modal-actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn-cancel {
            flex: 1; padding: 11px;
            background: transparent;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px; color: rgba(255,255,255,0.4);
            font-family: 'DM Sans', sans-serif; font-size: 14px;
            cursor: pointer; transition: border-color 0.2s, color 0.2s;
        }
        .btn-cancel:hover { border-color: rgba(255,255,255,0.3); color: #fff; }
        .btn-save {
            flex: 1; padding: 11px;
            background: linear-gradient(135deg, #ffb81e, #ff6b35);
            border: none; border-radius: 10px;
            color: #0d0d0d; font-family: 'DM Sans', sans-serif;
            font-size: 14px; font-weight: 500; cursor: pointer;
            transition: opacity 0.18s;
        }
        .btn-save:hover { opacity: 0.88; }
    </style>
</head>
<body>

<nav>
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

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-toggle" id="sidebarToggle">
            <svg class="toggle-icon" viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                <span class="nav-label">Dashboard</span>
                <span class="nav-tooltip">Dashboard</span>
            </a>
            <a href="my_orders.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
                <span class="nav-label">My Orders</span>
                <span class="nav-tooltip">My Orders</span>
            </a>
            <a href="my_listings.php" class="nav-item active">
                <svg viewBox="0 0 24 24"><path d="M20 6h-2.18c.07-.44.18-.88.18-1.34C18 2.99 16.54 1.5 14.83 1.5c-.89 0-1.7.38-2.28.96L12 3.03l-.55-.57A3.17 3.17 0 009.17 1.5C7.46 1.5 6 2.99 6 4.66c0 .46.11.9.18 1.34H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z"/></svg>
                <span class="nav-label">My Listings</span>
                <span class="nav-tooltip">My Listings</span>
            </a>
            <a href="messages.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                <span class="nav-label">Messages</span>
                <span class="nav-tooltip">Messages</span>
            </a>
            <a href="profile.php" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                <span class="nav-label">Profile</span>
                <span class="nav-tooltip">Profile</span>
            </a>
            <a href="#" class="nav-item">
                <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94s-.02-.64-.07-.94l2.03-1.58a.49.49 0 00.12-.61l-1.92-3.32a.49.49 0 00-.59-.22l-2.39.96a6.97 6.97 0 00-1.62-.94l-.36-2.54A.484.484 0 0014 3h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96a.47.47 0 00-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 00-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.37 1.04.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.57 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6a3.6 3.6 0 110-7.2 3.6 3.6 0 010 7.2z"/></svg>
                <span class="nav-label">Settings</span>
                <span class="nav-tooltip">Settings</span>
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

    <!-- MAIN -->
    <div class="main-content">

        <div class="page-header">
            <div class="page-header-left">
                <p class="page-label">Seller</p>
                <h1>My Listings</h1>
            </div>
            <a href="add_product.php" class="btn-primary">
                <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Add Product
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Location</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="td-name"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="td-desc"><?php echo htmlspecialchars($row['description']); ?></td>
                                <td class="td-price">₱<?php echo number_format($row['price'], 2); ?></td>
                                <td class="td-location"><?php echo htmlspecialchars($row['location']); ?></td>
                                <td style="font-size:12px; color:rgba(255,255,255,0.3);">
                                    <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-edit"
                                            onclick="openEdit(
                                                <?php echo $row['id']; ?>,
                                                '<?php echo addslashes($row['name']); ?>',
                                                '<?php echo addslashes($row['description']); ?>',
                                                '<?php echo $row['price']; ?>',
                                                '<?php echo addslashes($row['location']); ?>'
                                            )">Edit</button>
                                        <a href="?delete=<?php echo $row['id']; ?>"
                                           class="btn-delete"
                                           onclick="return confirm('Delete this product?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>You haven't listed any products yet.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <h2>Edit Product</h2>
        <p class="subtitle">Update your listing details</p>
        <form method="POST" action="edit_product.php">
            <input type="hidden" name="id" id="edit_id">
            <div class="field">
                <label>Product Name</label>
                <input type="text" name="name" id="edit_name" required>
            </div>
            <div class="field">
                <label>Description</label>
                <textarea name="description" id="edit_description"></textarea>
            </div>
            <div class="field">
                <label>Price (₱)</label>
                <input type="number" step="0.01" name="price" id="edit_price" required>
            </div>
            <div class="field">
                <label>Location</label>
                <input type="text" name="location" id="edit_location">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEdit()">Cancel</button>
                <button type="submit" class="btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Sidebar collapse
    const sidebar = document.getElementById('sidebar');
    if (localStorage.getItem('sidebar_collapsed') === 'true') sidebar.classList.add('collapsed');
    document.getElementById('sidebarToggle').addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
    });

    // Edit modal
    function openEdit(id, name, description, price, location) {
        document.getElementById('edit_id').value         = id;
        document.getElementById('edit_name').value       = name;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_price').value      = price;
        document.getElementById('edit_location').value   = location;
        document.getElementById('editModal').classList.add('open');
    }
    function closeEdit() {
        document.getElementById('editModal').classList.remove('open');
    }
    // Close modal on overlay click
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEdit();
    });
</script>

</body>
</html>