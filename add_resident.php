<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$success = '';
$error = '';
$clear_form = false;

if (isset($_POST['add_resident'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $barangay = trim($_POST['barangay']);
    $purok = trim($_POST['purok']);

    if (!empty($name) && !empty($email) && !empty($barangay) && !empty($purok)) {
        // Check if email already exists (excluding soft-deleted) ⭐ FIXED HERE
        $check = $conn->prepare("SELECT id FROM residents WHERE email=? AND deleted_at IS NULL");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            $stmt = $conn->prepare("INSERT INTO residents (name, email, barangay, purok) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $barangay, $purok);
            
            if ($stmt->execute()) {
                $success = "Resident added successfully!";
                $clear_form = true;
            } else {
                $error = "Error adding resident: " . $conn->error;
            }
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Resident — ECOPING</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: linear-gradient(135deg, #f5f8f6 0%, #e8f0ec 100%);
    min-height: 100vh;
    padding: 20px;
}

header {
    background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%);
    color: white;
    padding: 28px 40px;
    border-radius: 18px;
    box-shadow: 0 4px 24px rgba(26, 95, 63, 0.12);
    margin-bottom: 32px;
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.header-title h1 {
    font-size: 24px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
}

.header-title p {
    font-size: 14px;
    opacity: 0.85;
    margin-top: 5px;
    font-weight: 500;
}

.btn {
    padding: 11px 22px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
}

.btn-back {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 2px solid rgba(255,255,255,0.25);
}

.btn-back:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-2px);
}

.container {
    max-width: 900px;
    margin: 0 auto;
}

.card {
    background: white;
    border-radius: 18px;
    padding: 40px;
    box-shadow: 0 4px 24px rgba(26, 95, 63, 0.06);
}

.card h2 {
    color: #1a5f3f;
    font-size: 23px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
}

.card-subtitle {
    color: #6b7c73;
    font-size: 14px;
    margin-bottom: 32px;
    font-weight: 500;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 22px;
    margin-bottom: 22px;
}

.form-group {
    margin-bottom: 28px;
}

.form-group label {
    display: block;
    color: #2c3e35;
    font-weight: 700;
    margin-bottom: 10px;
    font-size: 15px;
}

.form-group input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e0e8e3;
    border-radius: 12px;
    font-size: 15px;
    font-family: inherit;
    transition: all 0.3s ease;
    background: #f9fbfa;
}

.form-group input:focus {
    outline: none;
    border-color: #1a5f3f;
    background: white;
    box-shadow: 0 0 0 4px rgba(26, 95, 63, 0.06);
}

.btn-submit {
    background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%);
    color: white;
    padding: 16px 32px;
    font-size: 16px;
    width: 100%;
    font-weight: 700;
    box-shadow: 0 6px 20px rgba(26, 95, 63, 0.2);
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(26, 95, 63, 0.3);
}

.alert {
    padding: 16px 22px;
    border-radius: 12px;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 15px;
    font-weight: 500;
}

.alert-success {
    background: #f0f7f3;
    color: #2e7d52;
    border-left: 4px solid #2d8659;
}

.alert-error {
    background: #fef5f4;
    color: #c44133;
    border-left: 4px solid #c44133;
}

.success-actions {
    margin-top: 12px;
    display: flex;
    gap: 10px;
}

.success-actions a {
    padding: 8px 16px;
    background: #2d8659;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.3s ease;
}

.success-actions a:hover {
    background: #236b47;
}

@media (max-width: 768px) {
    header, .container {
        padding: 20px;
    }
    
    .card {
        padding: 28px;
    }
    
    .header-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="header-title">
            <h1>👤 Add New Resident</h1>
            <p>Register a new resident to the ECOPING system</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-back">← Back to Dashboard</a>
    </div>
</header>

<div class="container">
    <div class="card">
        <h2>📝 Resident Registration Form</h2>
        <p class="card-subtitle">Fill in the details to add a new resident to the system</p>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <div>
                    ✅ <?= htmlspecialchars($success) ?>
                    <div class="success-actions">
                        <a href="add_resident.php">➕ Add Another</a>
                        <a href="manage_residents.php">📋 View All</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" placeholder="Enter full name" required 
                           value="<?= $clear_form ? '' : (isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" placeholder="Enter email address" required 
                           value="<?= $clear_form ? '' : (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="barangay">Street *</label>
                    <input type="text" id="barangay" name="barangay" placeholder="Enter Street" required 
                           value="<?= $clear_form ? '' : (isset($_POST['barangay']) ? htmlspecialchars($_POST['barangay']) : '') ?>">
                </div>

                <div class="form-group">
                    <label for="purok">Block Number *</label>
                    <input type="text" id="purok" name="purok" placeholder="Enter Block Number" required 
                           value="<?= $clear_form ? '' : (isset($_POST['purok']) ? htmlspecialchars($_POST['purok']) : '') ?>">
                </div>
            </div>

            <button type="submit" name="add_resident" class="btn btn-submit">
                ✅ Add Resident
            </button>
        </form>
    </div>
</div>

<script>
// Auto-dismiss success after 8 seconds
setTimeout(() => {
    const success = document.querySelector('.alert-success');
    if (success) {
        success.style.transition = 'opacity 0.5s';
        success.style.opacity = '0';
        setTimeout(() => success.remove(), 500);
    }
}, 8000);
</script>

</body>
</html>