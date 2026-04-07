<?php
// Role check handled by profile.php router

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $location   = trim($_POST['location'] ?? '');
    $bio        = trim($_POST['bio'] ?? '');
    $shop_name  = trim($_POST['shop_name'] ?? '');
    $new_pass   = $_POST['new_password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    if (empty($email)) {
        $error = 'Email cannot be empty.';
    } elseif (!empty($new_pass) && $new_pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        if (!empty($new_pass)) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, location=?, bio=?, shop_name=?, password=? WHERE id=?");
            $stmt->bind_param("ssssssi", $full_name, $email, $location, $bio, $shop_name, $hashed, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, location=?, bio=?, shop_name=? WHERE id=?");
            $stmt->bind_param("sssssi", $full_name, $email, $location, $bio, $shop_name, $user_id);
        }

        if ($stmt->execute()) {
            $success = 'Profile updated successfully!';
        } else {
            $error = 'Something went wrong. Please try again.';
        }
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch seller stats
$stmt2 = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE user_id = ?");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$product_count = $stmt2->get_result()->fetch_assoc()['total'];

$stmt3 = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN o.status='completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN o.status='pending'   THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN o.status='cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM orders o JOIN products p ON o.product_id = p.id
    WHERE p.user_id = ?
");
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$stats = $stmt3->get_result()->fetch_assoc();

$initials = strtoupper(substr($user['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | NearBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .hero { margin-bottom: 32px; }
        .hero-label { font-size: 12px; font-weight: 500; letter-spacing: 1px; text-transform: uppercase; color: #ffb81e; margin-bottom: 10px; }
        .hero h1 { font-family: 'DM Serif Display', serif; font-size: 36px; line-height: 1.15; letter-spacing: -0.5px; color: #fff; margin-bottom: 8px; }
        .hero h1 em { font-style: italic; color: #ffb81e; }
        .hero > p { font-size: 14px; color: rgba(255,255,255,0.35); }

        .profile-grid { display: grid; grid-template-columns: 280px 1fr; gap: 24px; align-items: start; }

        /* Left card */
        .profile-card { background: #111; border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; padding: 28px 24px; text-align: center; }
        .avatar-circle { width: 80px; height: 80px; border-radius: 50%; background: rgba(255,184,30,0.12); border: 2px solid rgba(255,184,30,0.3); display: flex; align-items: center; justify-content: center; font-size: 30px; font-weight: 500; color: #ffb81e; margin: 0 auto 16px; }
        .profile-username { font-size: 18px; font-weight: 500; color: #fff; margin-bottom: 4px; }
        .profile-role { font-size: 12px; color: #ffb81e; background: rgba(255,184,30,0.1); border: 1px solid rgba(255,184,30,0.25); padding: 3px 12px; border-radius: 999px; display: inline-block; margin-bottom: 16px; }
        .profile-meta { font-size: 13px; color: rgba(255,255,255,0.3); margin-bottom: 6px; }
        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.06); margin: 20px 0; }

        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 4px; }
        .stat-box { background: #161616; border: 1px solid rgba(255,255,255,0.06); border-radius: 10px; padding: 12px; text-align: center; }
        .stat-num { font-size: 22px; font-weight: 500; color: #fff; line-height: 1; margin-bottom: 4px; }
        .stat-num.yellow { color: #ffb81e; }
        .stat-num.green  { color: #34d399; }
        .stat-num.red    { color: #f87171; }
        .stat-lbl { font-size: 11px; color: rgba(255,255,255,0.3); }

        /* Right form */
        .form-card { background: #111; border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; padding: 28px 28px; }
        .form-section-title { font-size: 12px; font-weight: 500; letter-spacing: 1px; text-transform: uppercase; color: rgba(255,255,255,0.3); margin-bottom: 18px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        .form-group label { font-size: 12px; color: rgba(255,255,255,0.4); }
        .form-group input,
        .form-group textarea {
            background: #161616;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 10px 14px;
            color: #fff;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            outline: none;
            transition: border-color 0.15s;
            width: 100%;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group textarea:focus { border-color: rgba(255,184,30,0.4); }
        .form-group textarea { resize: vertical; min-height: 90px; }

        .btn-save {
            background: rgba(255,184,30,0.12);
            border: 1px solid rgba(255,184,30,0.35);
            color: #ffb81e;
            padding: 10px 28px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-save:hover { background: rgba(255,184,30,0.22); }

        .alert { padding: 12px 18px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; }
        .alert-success { background: rgba(52,211,153,0.08); border: 1px solid rgba(52,211,153,0.2); color: #34d399; }
        .alert-error   { background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.2); color: #f87171; }

        @media (max-width: 768px) {
            .profile-grid { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
        }
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
            <div class="user-avatar"><?php echo $initials; ?></div>
            <span class="username-display"><?php echo htmlspecialchars($user['username']); ?></span>
        </div>
    </div>
</nav>

<div class="page-body">
    <?php include_once __DIR__ . "/../includes/sidebar.php"; ?>
    <main class="main-content">

        <header class="hero">
            <p class="hero-label">Account</p>
            <h1>My <em>Profile</em></h1>
            <p>Manage your seller account details</p>
        </header>

        <div class="profile-grid">

            <!-- Left: avatar + stats -->
            <div>
                <div class="profile-card">
                    <div class="avatar-circle"><?php echo $initials; ?></div>
                    <p class="profile-username"><?php echo htmlspecialchars($user['username']); ?></p>
                    <span class="profile-role">Seller</span>
                    <?php if (!empty($user['shop_name'])): ?>
                        <p class="profile-meta">🏪 <?php echo htmlspecialchars($user['shop_name']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($user['location'])): ?>
                        <p class="profile-meta">📍 <?php echo htmlspecialchars($user['location']); ?></p>
                    <?php endif; ?>
                    <p class="profile-meta">Joined <?php echo date('M Y', strtotime($user['created_at'])); ?></p>

                    <hr class="divider">
                    <p style="font-size:11px;color:rgba(255,255,255,0.3);text-align:left;margin-bottom:12px;text-transform:uppercase;letter-spacing:1px;">Shop Stats</p>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="stat-num"><?php echo $product_count; ?></div>
                            <div class="stat-lbl">Products</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-num"><?php echo $stats['total'] ?? 0; ?></div>
                            <div class="stat-lbl">Orders</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-num green"><?php echo $stats['completed'] ?? 0; ?></div>
                            <div class="stat-lbl">Completed</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-num yellow"><?php echo $stats['pending'] ?? 0; ?></div>
                            <div class="stat-lbl">Pending</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: edit form -->
            <div class="form-card">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <p class="form-section-title">Basic Info</p>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" placeholder="e.g. Juan dela Cruz">
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="opacity:0.4;cursor:not-allowed;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="your@email.com">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Shop Name</label>
                            <input type="text" name="shop_name" value="<?php echo htmlspecialchars($user['shop_name'] ?? ''); ?>" placeholder="e.g. Josh's Store">
                        </div>
                        <div class="form-group">
                            <label>Location / Area</label>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" placeholder="e.g. Quezon City">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Bio / Shop Description</label>
                        <textarea name="bio" placeholder="Tell buyers a little about your shop..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <hr class="divider">
                    <p class="form-section-title">Change Password</p>
                    <div class="form-row">
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" placeholder="Leave blank to keep current">
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" placeholder="Repeat new password">
                        </div>
                    </div>

                    <button type="submit" class="btn-save">Save Changes</button>
                </form>
            </div>

        </div>
    </main>
</div>

</body>
</html>