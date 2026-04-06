<?php
include __DIR__ . '/../includes/config.php'; // fixed path

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    // 1. Capture the role from the form
    $role = $_POST["role"]; 

    // 2. Added 'role' to columns and a 4th '?' placeholder
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    // 3. Added a 4th 's' to bind the role string
    $stmt->bind_param("ssss", $username, $email, $password, $role);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit();
    } else {
        $message = "Error creating account";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Register</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
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
    left: -100px;
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
    right: -80px;
    border-radius: 50%;
    pointer-events: none;
}

.wrapper {
    display: flex;
    width: 780px;
    height: 520px;
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

/* Left panel — form */
.box {
    width: 360px;
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
    margin-bottom: 28px;
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
    margin-bottom: 12px;
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

input, select {
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

/* Specific styling for the select dropdown to match your dark theme */
select option {
    background: #111111;
    color: white;
}

input::placeholder {
    color: rgba(255,255,255,0.2);
}

input:focus, select:focus {
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

.login-link {
    margin-top: 20px;
    font-size: 13px;
    color: rgba(255,255,255,0.3);
    text-align: center;
}

.login-link a {
    color: #ffb81e;
    text-decoration: none;
    font-weight: 500;
}

.login-link a:hover {
    text-decoration: underline;
}

/* Right decorative panel */
.panel-right {
    flex: 1;
    background: linear-gradient(160deg, #1a1200 0%, #0d0d0d 100%);
    border-left: 1px solid rgba(255,255,255,0.06);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 44px 40px;
    position: relative;
    overflow: hidden;
}

.panel-right::after {
    content: '';
    position: absolute;
    width: 280px;
    height: 280px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 185, 30, 0.13) 0%, transparent 65%);
    top: -60px;
    left: -60px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 1;
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
    font-size: 32px;
    line-height: 1.2;
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

.perks {
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.perk {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: rgba(255,255,255,0.4);
    font-weight: 300;
}

.perk-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #ffb81e;
    flex-shrink: 0;
}
</style>
</head>

<body>

<div class="wrapper">

    <div class="box">
        <h2>Create account</h2>
        <p class="subtitle">Join NearBuy and start shopping local</p>

        <?php if ($message != "") echo "<p class='error'>$message</p>"; ?>

        <form method="POST">
            <div class="field">
                <label>I am a</label>
                <select name="role" required>
                    <option value="customer">Resident (Buyer)</option>
                    <option value="store_owner">Grocery Owner (Seller)</option>
                </select>
            </div>

            <div class="field">
                <label>Username</label>
                <input type="text" name="username" placeholder="e.g. juan_dela_cruz" required>
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" placeholder="you@example.com" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit">Register</button>
        </form>

        <p class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </p>
    </div>

    <div class="panel-right">
        <div class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
            </div>
            <span class="brand-name">NearBuy</span>
        </div>

        <div class="panel-copy">
            <h1>Your <em>community</em>, one tap away.</h1>
            <p>Connect with local sellers and find great deals right in your area.</p>
        </div>

        <div class="perks">
            <div class="perk"><span class="perk-dot"></span>Browse sellers near you</div>
            <div class="perk"><span class="perk-dot"></span>Free to join, always</div>
            <div class="perk"><span class="perk-dot"></span>Support local businesses</div>
        </div>
    </div>

</div>

</body>
</html>