<?php
session_start();
include __DIR__ . "/../includes/config.php"; // correct relative path

// Redirect already logged-in users based on their role
if (isset($_SESSION["user_id"])) {
    if ($_SESSION["role"] === "store_owner") {
        header("Location: my_listings.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // We now select the 'role' column along with others
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {
            session_regenerate_id(true); // secure session
            
            // Store role in session so other pages know who is logged in
            $_SESSION["username"] = $user["username"];
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["role"] = $user["role"]; 

            // Role-based Redirection Logic
            if ($user["role"] === "store_owner") {
                header("Location: my_listings.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $message = "Invalid password";
        }
    } else {
        $message = "User not found";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>NearBuy Login</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
/* ... Your CSS remains completely untouched and identical ... */
*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'DM Sans', sans-serif;
    background: #0d0d0d;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    overflow: hidden;
}

body::before {
    content: '';
    position: fixed;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(255, 200, 80, 0.12) 0%, transparent 70%);
    top: -120px;
    right: -100px;
    border-radius: 50%;
    pointer-events: none;
}

body::after {
    content: '';
    position: fixed;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(255, 100, 60, 0.09) 0%, transparent 70%);
    bottom: -80px;
    left: -80px;
    border-radius: 50%;
    pointer-events: none;
}

.wrapper {
    display: flex;
    width: 780px;
    height: 480px;
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.07);
    box-shadow: 0 40px 120px rgba(0,0,0,0.6);
    animation: rise 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes rise {
    from { opacity: 0; transform: translateY(28px); }
    to   { opacity: 1; transform: translateY(0); }
}

.panel-left {
    flex: 1;
    background: linear-gradient(160deg, #1a1200 0%, #0d0d0d 100%);
    border-right: 1px solid rgba(255,255,255,0.06);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 44px 40px;
    position: relative;
    overflow: hidden;
}

.panel-left::after {
    content: '';
    position: absolute;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 185, 30, 0.13) 0%, transparent 65%);
    bottom: -60px;
    right: -60px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 10px;
}

.brand-icon {
    width: 34px;
    height: 34px;
    background: linear-gradient(135deg, #ffb81e, #ff6b35);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.brand-icon svg {
    width: 18px;
    height: 18px;
    fill: white;
}

.brand-name {
    font-family: 'DM Serif Display', serif;
    font-size: 20px;
    color: #ffffff;
    letter-spacing: -0.3px;
}

.panel-copy {
    z-index: 1;
}

.panel-copy h1 {
    font-family: 'DM Serif Display', serif;
    font-size: 34px;
    line-height: 1.18;
    color: #ffffff;
    margin-bottom: 14px;
    letter-spacing: -0.5px;
}

.panel-copy h1 em {
    font-style: italic;
    color: #ffb81e;
}

.panel-copy p {
    font-size: 14px;
    color: rgba(255,255,255,0.38);
    line-height: 1.65;
    font-weight: 300;
    max-width: 200px;
}

.panel-dots {
    display: flex;
    gap: 6px;
    z-index: 1;
}

.dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: rgba(255,255,255,0.15);
}

.dot.active {
    background: #ffb81e;
    width: 22px;
    border-radius: 3px;
}

.box {
    width: 340px;
    background: #111111;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 44px 40px;
}

.box h2 {
    font-family: 'DM Serif Display', serif;
    font-size: 24px;
    color: #ffffff;
    margin-bottom: 6px;
    letter-spacing: -0.3px;
}

.box .subtitle {
    font-size: 13px;
    color: rgba(255,255,255,0.35);
    margin-bottom: 30px;
    font-weight: 300;
}

.error {
    font-size: 12px;
    color: #ff6b6b;
    background: rgba(255,107,107,0.08);
    border: 1px solid rgba(255,107,107,0.2);
    border-radius: 8px;
    padding: 9px 12px;
    margin-bottom: 16px;
}

.field {
    margin-bottom: 14px;
}

.field label {
    display: block;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.35);
    margin-bottom: 7px;
}

input {
    width: 100%;
    padding: 11px 14px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.09);
    border-radius: 10px;
    color: #ffffff;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s, background 0.2s;
    margin: 0;
}

input::placeholder {
    color: rgba(255,255,255,0.2);
}

input:focus {
    border-color: rgba(255,184,30,0.5);
    background: rgba(255,184,30,0.04);
}

button {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #ffb81e, #ff6b35);
    border: none;
    border-radius: 10px;
    color: #0d0d0d;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    margin-top: 8px;
    letter-spacing: 0.2px;
    transition: opacity 0.18s, transform 0.15s;
}

button:hover {
    opacity: 0.88;
    transform: translateY(-1px);
}

button:active {
    transform: translateY(0);
    opacity: 1;
}

.register-link {
    margin-top: 20px;
    font-size: 13px;
    color: rgba(255,255,255,0.3);
    text-align: center;
}

.register-link a {
    color: #ffb81e;
    text-decoration: none;
    font-weight: 500;
}

.register-link a:hover {
    text-decoration: underline;
}
</style>
</head>

<body>

<div class="wrapper">
    <div class="panel-left">
        <div class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
            </div>
            <span class="brand-name">NearBuy</span>
        </div>

        <div class="panel-copy">
            <h1>Shop what's <em>near</em> you.</h1>
            <p>Discover local deals and products from sellers in your neighborhood.</p>
        </div>

        <div class="panel-dots">
            <div class="dot active"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    </div>

    <div class="box">
        <h2>Welcome back</h2>
        <p class="subtitle">Sign in to your account</p>

        <?php if ($message != "") echo "<p class='error'>$message</p>"; ?>

        <form method="POST">
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" placeholder="you@example.com" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit">Sign in</button>
        </form>

        <p class="register-link">
            No account? <a href="register.php">Register</a>
        </p>
    </div>
</div>

</body>
</html>