<?php
include __DIR__ . "/../includes/auth.php";
requireLogin();

$user_id = $_SESSION["user_id"];
$success = isset($_GET['msg']) && $_GET['msg'] == 'deleted' ? "Product deleted successfully." : "";
$error = "";

// Handle delete
if (isset($_GET["delete"])) {
    $del_id = intval($_GET["delete"]);
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $del_id, $user_id);
    
    if ($stmt->execute()) {
        header("Location: my_listings.php?msg=deleted");
        exit();
    } else {
        $error = "Failed to delete product.";
    }
}

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
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* PAGE-SPECIFIC STYLES (not in shared style.css) */

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

        .alert { padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 24px; }
        .alert-success { background: rgba(29,158,117,0.1); border: 1px solid rgba(29,158,117,0.25); color: #1D9E75; }
        .alert-error   { background: rgba(255,107,107,0.08); border: 1px solid rgba(255,107,107,0.2); color: #ff6b6b; }

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

<nav class="top-navbar">
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

    <!-- SHARED SIDEBAR -->
    <?php include_once __DIR__ . "/../includes/sidebar.php"; ?>

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
    // Edit modal
    function openEdit(id, name, description, price, location) {
        document.getElementById('edit_id').value          = id;
        document.getElementById('edit_name').value        = name;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_price').value       = price;
        document.getElementById('edit_location').value    = location;
        document.getElementById('editModal').classList.add('open');
    }
    function closeEdit() {
        document.getElementById('editModal').classList.remove('open');
    }
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEdit();
    });
</script>

</body>
</html>