<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$success  = '';
$error    = '';
$schedule = null;

// Need an ID to edit
if (!isset($_GET['id'])) {
    header("Location: manage_schedules.php");
    exit();
}

$id     = intval($_GET['id']);
$result = $conn->query("SELECT * FROM schedules WHERE id = $id");

if ($result && $result->num_rows > 0) {
    $schedule = $result->fetch_assoc();
} else {
    header("Location: manage_schedules.php");
    exit();
}

// Handle form update
if (isset($_POST['update_schedule'])) {
    $street         = trim($_POST['street']);
    $block_number   = trim($_POST['block_number']);
    $collection_day = trim($_POST['collection_day']);
    $time           = trim($_POST['time']);
    $contact        = trim($_POST['contact']);

    if (!empty($street) && !empty($block_number) && !empty($collection_day) && !empty($time)) {
        $stmt = $conn->prepare("UPDATE schedules SET street=?, block_number=?, collection_day=?, time=?, contact=? WHERE id=?");
        $stmt->bind_param("sssssi", $street, $block_number, $collection_day, $time, $contact, $id);

        if ($stmt->execute()) {
            $success = "✅ Schedule updated successfully!";
            // Refresh data to show updated values
            $result   = $conn->query("SELECT * FROM schedules WHERE id = $id");
            $schedule = $result->fetch_assoc();
        } else {
            $error = "Error updating schedule: " . $conn->error;
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Handle delete from this page
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt   = $conn->prepare("DELETE FROM schedules WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    header("Location: manage_schedules.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Schedule — ECOPING</title>
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
    max-width: 800px;
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
.btn-back { background: rgba(255,255,255,0.15); color: white; border: 2px solid rgba(255,255,255,0.25); }
.btn-back:hover { background: rgba(255,255,255,0.25); transform: translateY(-2px); }

.container { max-width: 800px; margin: 0 auto; }

.card {
    background: white;
    border-radius: 18px;
    padding: 40px;
    box-shadow: 0 4px 24px rgba(26, 95, 63, 0.06);
}

.card h2 { color: #1a5f3f; font-size: 23px; margin-bottom: 10px; font-weight: 700; }
.card-subtitle { color: #6b7c73; font-size: 14px; margin-bottom: 32px; font-weight: 500; }

/* Shows the current time in a nice info box */
.current-time {
    background: #f0f7f3;
    border-left: 4px solid #2d8659;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 28px;
    font-size: 14px;
    color: #2e7d52;
    font-weight: 500;
}

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-bottom: 22px; }

.form-group { margin-bottom: 28px; }
.form-group label { display: block; color: #2c3e35; font-weight: 700; margin-bottom: 10px; font-size: 15px; }

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
}
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(26, 95, 63, 0.3); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

.btn-delete {
    background: linear-gradient(135deg, #c44133 0%, #a83529 100%);
    color: white;
    padding: 16px 32px;
    font-size: 16px;
    width: 100%;
    margin-top: 12px;
    font-weight: 700;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(196, 65, 51, 0.2);
}
.btn-delete:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(196, 65, 51, 0.3); }

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
.alert-success { background: #f0f7f3; color: #2e7d52; border-left: 4px solid #2d8659; }
.alert-error   { background: #fef5f4; color: #c44133; border-left: 4px solid #c44133; }

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
            <h1>✏️ Edit Schedule</h1>
            <p>Update garbage collection schedule details</p>
        </div>
        <a href="manage_schedules.php" class="btn btn-back">← Back to Schedules</a>
    </div>
</header>

<div class="container">
    <div class="card">
        <h2>📝 Update Schedule Information</h2>
        <p class="card-subtitle">Modify the collection schedule for this street and block</p>

        <!-- Show current time in human-readable format -->
        <?php if ($schedule): ?>
            <div class="current-time">
                🕐 Current scheduled time: <strong><?= date("g:i A", strtotime($schedule['time'])) ?></strong>
                &nbsp;—&nbsp;
                <?= htmlspecialchars($schedule['collection_day']) ?>,
                <?= htmlspecialchars($schedule['street']) ?>, Block <?= htmlspecialchars($schedule['block_number']) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($schedule): ?>
        <form method="POST" id="editForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="street">Street *</label>
                    <input type="text" id="street" name="street" value="<?= htmlspecialchars($schedule['street']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="block_number">Block Number *</label>
                    <input type="text" id="block_number" name="block_number" value="<?= htmlspecialchars($schedule['block_number']) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="collection_day">Collection Day *</label>
                    <select id="collection_day" name="collection_day" required>
                        <option value="">Select Day</option>
                        <?php
                        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                        foreach ($days as $day) {
                            $selected = ($schedule['collection_day'] == $day) ? 'selected' : '';
                            echo "<option value=\"$day\" $selected>$day</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="time">Time *</label>
                    <input type="time" id="time" name="time" value="<?= htmlspecialchars($schedule['time']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="tel" id="contact" name="contact" placeholder="e.g., 09123456789" value="<?= htmlspecialchars($schedule['contact'] ?? '') ?>">
            </div>

            <button type="submit" name="update_schedule" class="btn btn-submit" id="submitBtn">
                💾 Update Schedule
            </button>

            <button type="button" class="btn btn-delete"
                onclick="if (confirm('Are you sure you want to delete this schedule?')) window.location.href='edit_schedules.php?delete=<?= $schedule['id'] ?>'">
                🗑️ Delete Schedule
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Loading state on submit
document.getElementById('editForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '⏳ Updating...';
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