<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db_connect.php';
include 'send_email.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$errors = [];
$success = "";

// Handle form submission
if (isset($_POST['add_schedule'])) {
    $street = trim($_POST['street']);
    $block_number = trim($_POST['block_number']);
    $collection_day = trim($_POST['collection_day']);
    $time = trim($_POST['time']);
    $contact = trim($_POST['contact']);

    if (!$street || !$block_number || !$collection_day || !$time || !$contact) {
        $errors[] = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO schedules (street, block_number, collection_day, time, contact) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $street, $block_number, $collection_day, $time, $contact);

        if ($stmt->execute()) {

            // Send email to residents
            $subject = "🗓️ New Garbage Collection Schedule";
            $message = "<h3>New Schedule Posted</h3>
                        <p>A new garbage collection schedule has been added in your area.</p>
                        <p>Check your ECOPING dashboard for details.</p>";
            sendEmailToResidents($subject, $message, $conn);

            // Redirect to admin dashboard after success
            header("Location: admin_dashboard.php?msg=schedule_added");
            exit();

        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Schedule — ECOPING</title>
<style>
body { font-family: Arial, sans-serif; background: #eef5ef; padding: 0; margin:0; }
.container { max-width:600px; margin:50px auto; padding:20px; background:white; border-radius:12px; box-shadow:0 0 10px rgba(0,0,0,0.1);}
h2 { color:#2e8b57; margin-bottom:20px; }
form { display:flex; flex-direction:column; gap:15px; }
input, select { padding:10px; border-radius:6px; border:1px solid #ccc; }
button { background:#2e8b57; color:white; padding:12px; border:none; border-radius:8px; cursor:pointer; }
button:hover { background:#256f47; }
.error { background:#fdecea; color:#e53935; padding:10px; border-radius:6px; }
.success { background:#e8f5e9; color:#43a047; padding:10px; border-radius:6px; }
</style>
</head>
<body>

<div class="container">
    <h2>🗓️ Add Garbage Collection Schedule</h2>

    <?php if(!empty($errors)): ?>
        <div class="error">
            <?php foreach($errors as $e) echo "<p>$e</p>"; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="street" placeholder="Street" required>
        <input type="text" name="block_number" placeholder="Block Number" required>
        <select name="collection_day" required>
            <option value="">-- Select Collection Day --</option>
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
            <option value="Saturday">Saturday</option>
            <option value="Sunday">Sunday</option>
        </select>
        <input type="time" name="time" required>
        <input type="text" name="contact" placeholder="Contact Number" required>
        <button type="submit" name="add_schedule">Add Schedule</button>
    </form>
</div>

</body>
</html>
