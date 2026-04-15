<?php
session_start();
include 'db_connect.php';

if (isset($_SESSION['admin'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

       if ($admin && $password === $admin['password']) {
    $_SESSION['admin'] = $admin['username'];   // ← username for display
    $_SESSION['admin_id'] = $admin['id'];       // ← id for security check
    header("Location: admin_dashboard.php");
    exit();
}
        else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login — ECOPING</title>
<style>
:root {
    --green-primary: #1a5f3f;
    --green-secondary: #2d8659;
    --green-accent: #4a9d6f;
    --text-dark: #2c3e35;
    --text-muted: #6b7c73;
    --bg-light: #f5f8f6;
    --bg-lighter: #e8f0ec;
    --border: #e0e8e3;
    --card: #ffffff;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: linear-gradient(135deg, #f5f8f6 0%, #e8f0ec 50%, #d6e8de 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    -webkit-font-smoothing: antialiased;
}

/* Subtle background pattern */
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
        radial-gradient(circle at 20% 20%, rgba(26, 95, 63, 0.06) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(45, 134, 89, 0.06) 0%, transparent 50%);
    pointer-events: none;
}

.login-wrapper {
    width: 100%;
    max-width: 440px;
    position: relative;
    z-index: 1;
}

/* BRAND */
.brand {
    text-align: center;
    margin-bottom: 32px;
    animation: fadeDown 500ms ease both;
}

.ecoping-title {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 800;
    color: var(--green-primary);
    font-size: 36px;
    letter-spacing: 1px;
}

.logo-spin {
    height: 48px;
    width: 48px;
    border-radius: 50%;
    padding: 4px;
    box-shadow: 0 4px 20px rgba(26, 95, 63, 0.2);
    animation: spin 6s linear infinite;
}

.brand-sub {
    color: var(--text-muted);
    font-size: 13px;
    font-weight: 500;
    margin-top: 6px;
    letter-spacing: 0.3px;
}

/* CARD */
.card {
    background: var(--card);
    border-radius: 22px;
    padding: 40px;
    box-shadow:
        0 4px 6px rgba(26, 95, 63, 0.04),
        0 12px 40px rgba(26, 95, 63, 0.10),
        0 1px 0 rgba(255,255,255,0.8) inset;
    border: 1px solid rgba(224, 232, 227, 0.8);
    animation: fadeUp 500ms ease 100ms both;
}

.card-header {
    margin-bottom: 28px;
    text-align: center;
}

.card-header h2 {
    font-size: 22px;
    font-weight: 800;
    color: var(--green-primary);
    margin-bottom: 4px;
}

.card-header p {
    font-size: 14px;
    color: var(--text-muted);
    font-weight: 500;
}

/* FORM */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 8px;
}

.input-wrap {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 16px;
    pointer-events: none;
    opacity: 0.6;
}

.form-group input {
    width: 100%;
    padding: 13px 16px 13px 42px;
    border: 2px solid var(--border);
    border-radius: 12px;
    font-size: 15px;
    font-family: inherit;
    color: var(--text-dark);
    background: #f9fbfa;
    transition: all 0.25s ease;
    outline: none;
}

.form-group input:focus {
    border-color: var(--green-secondary);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(26, 95, 63, 0.08);
}

.form-group input::placeholder {
    color: #b0bdb6;
    font-weight: 400;
}

/* SHOW/HIDE PASSWORD */
.toggle-pass {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
    opacity: 0.5;
    transition: opacity 0.2s;
    padding: 0;
    line-height: 1;
}
.toggle-pass:hover { opacity: 0.9; }

/* ALERT */
.alert-error {
    background: #fef5f4;
    color: #c44133;
    border: 1px solid rgba(196, 65, 51, 0.2);
    border-left: 4px solid #c44133;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    gap: 8px;
    animation: shake 0.4s ease;
}

/* SUBMIT BUTTON */
.btn-login {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, var(--green-primary) 0%, var(--green-secondary) 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 6px 20px rgba(26, 95, 63, 0.25);
    margin-top: 6px;
    letter-spacing: 0.3px;
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(26, 95, 63, 0.35);
}

.btn-login:active {
    transform: translateY(0);
    box-shadow: 0 4px 12px rgba(26, 95, 63, 0.2);
}

/* BACK LINK */
.back-link {
    display: block;
    text-align: center;
    margin-top: 22px;
    color: var(--text-muted);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: color 0.2s;
    animation: fadeUp 500ms ease 200ms both;
}

.back-link:hover { color: var(--green-primary); }
.back-link span { color: var(--green-secondary); font-weight: 700; }

/* DIVIDER */
.divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 24px 0 20px;
}
.divider::before, .divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
}
.divider span {
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    white-space: nowrap;
}

/* ANIMATIONS */
@keyframes fadeDown {
    from { opacity: 0; transform: translateY(-16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%       { transform: translateX(-6px); }
    40%       { transform: translateX(6px); }
    60%       { transform: translateX(-4px); }
    80%       { transform: translateX(4px); }
}
</style>
</head>
<body>

<div class="login-wrapper">

    <!-- BRAND -->
    <div class="brand">
        <div class="ecoping-title">
            EC
            <img src="recycle.png" alt="recycle icon" class="logo-spin">
            PING
        </div>
        <div class="brand-sub">Dumaguete City — Community Waste Management</div>
    </div>

    <!-- LOGIN CARD -->
    <div class="card">
        <div class="card-header">
            <h2>🔐 Admin Login</h2>
            <p>Enter your credentials to access the dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <span class="input-icon">👤</span>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter your username"
                        required
                        autofocus
                        value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">🔑</span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >
                    <button type="button" class="toggle-pass" id="togglePass" aria-label="Show password">👁</button>
                </div>
            </div>

            <div class="divider"><span>Admin Access Only</span></div>

            <button type="submit" class="btn-login">Login to Dashboard →</button>

        </form>
    </div>

    <a href="index.php" class="back-link">← Back to <span>ECOPING Home</span></a>

</div>

<script>
document.getElementById('togglePass').addEventListener('click', function () {
    const pwd = document.getElementById('password');
    const isHidden = pwd.type === 'password';
    pwd.type = isHidden ? 'text' : 'password';
    this.textContent = isHidden ? '🙈' : '👁';
});
</script>

</body>
</html>