<?php
session_start();
include 'db_connect.php';
include 'send_email.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$success    = '';
$error      = '';
$clear_form = false;

// ADD SCHEDULE
if (isset($_POST['add_schedule'])) {
    $street         = trim($_POST['street']);
    $block_number   = trim($_POST['block_number']);
    $collection_day = trim($_POST['collection_day']);
    $time           = trim($_POST['time']);
    $contact        = trim($_POST['contact']);

    if (!empty($street) && !empty($block_number) && !empty($collection_day) && !empty($time)) {
        $stmt = $conn->prepare("INSERT INTO schedules (street, block_number, collection_day, time, contact) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $street, $block_number, $collection_day, $time, $contact);

        if ($stmt->execute()) {
            $success = "✅ Schedule added successfully!";
            $clear_form = true;

            // Send email notification to residents
            $emailSubject = "📅 New Garbage Collection Schedule";
            $emailMessage = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%); color: white; padding: 20px; border-radius: 10px 10px 0 0;'>
                        <h2 style='margin: 0;'>📅 New Garbage Collection Schedule</h2>
                    </div>
                    <div style='background: #f9fbfa; padding: 30px; border: 1px solid #e0e8e3; border-radius: 0 0 10px 10px;'>
                        <p>A new garbage collection schedule has been posted for your area!</p>
                        <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #e0e8e3;'>
                            <p><strong>📍 Location:</strong> $street, Block $block_number</p>
                            <p><strong>📅 Day:</strong> $collection_day</p>
                            <p><strong>🕐 Time:</strong> " . date("g:i A", strtotime($time)) . "</p>
                            " . (!empty($contact) ? "<p><strong>📞 Contact:</strong> $contact</p>" : "") . "
                        </div>
                        <p>Please check your ECOPING dashboard for more details.</p>
                        <hr style='border: none; border-top: 1px solid #e0e8e3; margin: 20px 0;'>
                        <p style='font-size:12px; color: #6b7c73;'>ECOPING System</p>
                    </div>
                </div>
            ";
            sendEmailToResidents($emailSubject, $emailMessage, $conn);
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Fill all required fields.";
    }
}

// DELETE SCHEDULE
if (isset($_GET['delete'])) {
    $id   = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_schedules.php");
    exit();
}

// GET SCHEDULES
$schedules = $conn->query("SELECT * FROM schedules ORDER BY id DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Schedules — ECOPING</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #f5f8f6 0%, #e8f0ec 100%); min-height: 100vh; padding: 20px; }
.container { max-width: 1200px; margin: 0 auto; }
.card { background: white; border-radius: 18px; padding: 32px; margin-bottom: 28px; box-shadow: 0 4px 24px rgba(26, 95, 63, 0.06); }
h2 { color: #1a5f3f; font-size: 23px; margin-bottom: 22px; }
.alert-success { background: #f0f7f3; color: #2e7d52; padding: 16px; border-radius: 12px; border-left: 4px solid #2d8659; margin-bottom: 28px; }
.alert-error { background: #fef5f4; color: #c44133; padding: 16px; border-radius: 12px; border-left: 4px solid #c44133; margin-bottom: 28px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-bottom: 22px; }
.form-group label { display: block; color: #2c3e35; font-weight: 700; margin-bottom: 10px; }
.form-group input, .form-group select { width: 100%; padding: 14px; border: 2px solid #e0e8e3; border-radius: 12px; background: #f9fbfa; }
.btn-submit { background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%); color: white; padding: 16px; border: none; border-radius: 12px; font-weight: 700; width: 100%; cursor: pointer; }
.btn-submit:hover { transform: translateY(-2px); }
.table-container { overflow-x: auto; border-radius: 14px; border: 1px solid #e0e8e3; }
table { width: 100%; border-collapse: collapse; }
th { background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%); color: white; padding: 14px; text-align: left; }
td { padding: 14px; border-bottom: 1px solid #e0e8e3; }
.btn-delete { background: #c44133; color: white; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-weight: 700; }
.btn-edit { background: #2d8659; color: white; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-weight: 700; margin-right: 6px; }
tr:hover { background: #f9fbfa; }
.empty { text-align: center; padding: 44px; color: #6b7c73; }
header { background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%); color: white; padding: 28px 40px; border-radius: 18px; box-shadow: 0 4px 24px rgba(26, 95, 63, 0.12); margin-bottom: 32px; max-width: 1200px; margin: 0 auto; }
.header-content { display: flex; justify-content: space-between; align-items: center; }
.header-title h1 { font-size: 24px; margin: 0; }
.btn-back { background: rgba(255,255,255,0.15); border: 2px solid rgba(255,255,255,0.25); padding: 11px 22px; border-radius: 10px; text-decoration: none; color: white; font-weight: 600; }
@media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="header-title">
            <h1>📅 Manage Collection Schedules</h1>
            <p>Add schedules for Street/Block</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-back">← Dashboard</a>
    </div>
</header>

<div class="container">

    <div class="card">
        <h2>➕ Add New Schedule</h2>
        <?php if ($success): ?>
            <div class="alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Street *</label>
                    <input type="text" name="street" placeholder="Enter Street" required>
                </div>
                <div class="form-group">
                    <label>Block Number *</label>
                    <input type="text" name="block_number" placeholder="Enter Block" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Collection Day *</label>
                    <select name="collection_day" required>
                        <option value="">Select Day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Time *</label>
                    <input type="time" name="time" required>
                </div>
            </div>
            <div class="form-group">
                <label>Contact (optional)</label>
                <input type="tel" name="contact" placeholder="09123456789">
            </div>
            <button type="submit" name="add_schedule" class="btn-submit">✅ Add Schedule</button>
        </form>
    </div>

    <div class="card">
        <h2>📋 All Schedules (Newest First)</h2>
        <?php if ($schedules && $schedules->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Street</th>
                            <th>Block</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($s = $schedules->fetch_assoc()): ?>
                            <tr>
                                <td><?= $s['id'] ?></td>
                                <td><?= htmlspecialchars($s['street']) ?></td>
                                <td><?= htmlspecialchars($s['block_number']) ?></td>
                                <td><?= htmlspecialchars($s['collection_day']) ?></td>
                                <td><?= date("g:i A", strtotime($s['time'])) ?></td>
                                <td><?= htmlspecialchars($s['contact'] ?? 'N/A') ?></td>
                                <td>
                                    <a href="edit_schedules.php?id=<?= $s['id'] ?>" class="btn-edit">Edit</a>
                                    <a href="?delete=<?= $s['id'] ?>" class="btn-delete" onclick="return confirm('Delete?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty">📋 No schedules. Add one above!</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
