<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$success = '';
$error = '';

// Get all streets/blocks from schedules (not residents)
$schedules_list = $conn->query("SELECT DISTINCT street, block_number FROM schedules ORDER BY street, block_number");

if (!$schedules_list) {
    die("Database error: " . $conn->error);
}

if (isset($_POST['send_truck_alert'])) {
    // ✅ FIXED: match form names
    $street = trim($_POST['street'] ?? '');
    $block_number = trim($_POST['block_number'] ?? '');
    $eta = trim($_POST['eta'] ?? '');

    if (!empty($street) && !empty($block_number)) {

        include 'send_email.php';

        $emailSubject = "🚛 Garbage Truck is On The Way! - ECOPING";
        $emailMessage = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #d97917 0%, #c44133 100%); color: white; padding: 20px; border-radius: 10px 10px 0 0;'>
                    <h2 style='margin: 0;'>🚛 Garbage Truck Alert!</h2>
                </div>
                <div style='background: #fef9f5; padding: 30px; border: 1px solid #fce8dc; border-radius: 0 0 10px 10px;'>
                    <div style='background: #fff3e0; border-left: 4px solid #d97917; padding: 20px; border-radius: 5px; margin-bottom: 20px;'>
                        <h3 style='color: #c44133; margin: 0 0 10px 0;'>⚠️ URGENT NOTICE</h3>
                        <p style='color: #2c3e35; font-size: 18px; margin: 0; font-weight: bold;'>The garbage collection truck is now on its way to your area!</p>
                    </div>
                    <div style='background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e0e8e3;'>
                        <p><strong>📍 Street:</strong> $street</p>
                        <p><strong>🏘️ Block:</strong> $block_number</p>
                        " . (!empty($eta) ? "<p><strong>🕐 ETA:</strong> <span style='color:#d97917;font-weight:bold;'>$eta</span></p>" : "") . "
                    </div>
                    <div style='background: #fff9e6; padding: 15px; border-radius: 5px; border-left: 4px solid #d97917;'>
                        <p><strong>📝 Action Required:</strong> Please prepare your garbage now!</p>
                    </div>
                    <hr>
                    <p style='font-size:12px;'>ECOPING System</p>
                </div>
            </div>
        ";

        $result = sendEmailToResidents($emailSubject, $emailMessage, $conn, $street, $block_number);

        if ($result['success']) {
            $success = "✅ " . $result['message'];
        } else {
            $error = "❌ " . $result['message'];
        }

    } else {
        $error = "Please select street and block.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🚛 Send Truck Alert — ECOPING</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

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

.header-title h1 { font-size: 24px; margin: 0; font-weight: 700; }
.header-title p  { font-size: 14px; opacity: 0.85; margin-top: 5px; font-weight: 500; }

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

.container { max-width: 900px; margin: 0 auto; }

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
    font-weight: 700; 
}
.card-subtitle { 
    color: #6b7c73; 
    font-size: 14px; 
    margin-bottom: 32px; 
    font-weight: 500; 
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

.form-row { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 22px; 
    margin-bottom: 22px; 
}

.form-group { margin-bottom: 28px; }
.form-group label { 
    display: block; 
    color: #2c3e35; 
    font-weight: 700; 
    margin-bottom: 10px; 
    font-size: 15px; 
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e0e8e3;
    border-radius: 12px;
    font-size: 15px;
    font-family: inherit;
    transition: all 0.3s ease;
    background: #f9fbfa;
}
.form-group input:focus,
.form-group select:focus {
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
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(26, 95, 63, 0.2);
    cursor: pointer;
}
.btn-submit:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 10px 30px rgba(26, 95, 63, 0.3); 
}
.btn-submit:disabled { 
    opacity: 0.6; 
    cursor: not-allowed; 
    transform: none; 
}

@media (max-width: 768px) {
    header { padding: 22px; }
    .header-content { flex-direction: column; align-items: flex-start; }
    .card { padding: 28px; }
    .form-row { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="header-title">
            <h1>🚛 Send Truck Alert</h1>
            <p>Notify residents about incoming garbage truck</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-back">← Back to Dashboard</a>
    </div>
</header>

<div class="container">
    <div class="card">
        <h2>📤 Send Alert to Street/Block</h2>
        <p class="card-subtitle">Select target area and optionally add ETA</p>

        <?php if ($success): ?>
            <div class="alert alert-success" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-left-color: #28a745;">
                🚛 <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="truckForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="street">Street *</label>
                    <select name="street" id="street" required onchange="filterBlocks()">
                        <option value="">Select Street</option>
                        <?php
                        $streets = [];
                        $schedules_list->data_seek(0);
                        while ($row = $schedules_list->fetch_assoc()) {
                            if (!in_array($row['street'], $streets)) {
                                $streets[] = $row['street'];
                                echo "<option value='{$row['street']}'>{$row['street']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="block">Block Number *</label>
                    <select name="block_number" id="block" required>
                        <option value="">Select Block</option>
                        <?php
                        $schedules_list->data_seek(0);
                        while ($row = $schedules_list->fetch_assoc()) {
                            echo "<option value='{$row['block_number']}' data-street='{$row['street']}'>
                                    {$row['block_number']}
                                  </option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="eta">ETA (optional)</label>
                <input type="text" id="eta" name="eta" placeholder="e.g., 30 minutes, 2:30 PM">
            </div>

            <button type="submit" name="send_truck_alert" class="btn btn-submit" id="submitBtn">
                🚛 Send Truck Alert
            </button>
        </form>
    </div>
</div>

<script>
function filterBlocks() {
    const street = document.getElementById("street").value;
    const options = document.getElementById("block").options;
    let hasMatch = false;

    for (let i = 0; i < options.length; i++) {
        let opt = options[i];
        if (opt.value === "") {
            opt.style.display = "block";
            continue;
        }

        if (opt.dataset.street === street) {
            opt.style.display = "block";
            hasMatch = true;
        } else {
            opt.style.display = "none";
        }
    }

    // Clear block selection if no matches
    if (!hasMatch && document.getElementById("block").value) {
        document.getElementById("block").value = "";
    }
}

// Form submission loading state
document.getElementById('truckForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '⏳ Sending Alert...';
});

// Auto-dismiss success after 5 seconds
setTimeout(() => {
    const msg = document.querySelector('.alert-success');
    if (msg) {
        msg.style.transition = 'opacity 0.5s';
        msg.style.opacity = '0';
        setTimeout(() => msg.remove(), 500);
    }
}, 5000);
</script>

</body>
</html>
