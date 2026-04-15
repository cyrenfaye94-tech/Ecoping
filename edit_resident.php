<?php
session_start();
include 'db_connect.php';
include 'functions.php';

requireAdmin();

$success = '';
$errors = [];
$resident = null;

// Get resident ID
if (!isset($_GET['id'])) {
    redirectWithMessage("manage_residents.php", "Resident not found", "error");
}

$id = intval($_GET['id']);

// Fetch resident
$stmt = $conn->prepare("SELECT * FROM residents WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirectWithMessage("manage_residents.php", "Resident not found", "error");
}

$resident = $result->fetch_assoc();

// Handle UPDATE
if (isset($_POST['update_resident'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid security token";
    } else {
        $form_data = [
            'name' => sanitize($_POST['name']),
            'email' => sanitize($_POST['email']),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'barangay' => sanitize($_POST['barangay']),
            'purok' => sanitize($_POST['purok'])
        ];
        
        $validation_errors = validate($form_data, [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email',
            'barangay' => 'required|min:2|max:50',
            'purok' => 'required|min:1|max:20'
        ]);
        
        if (!empty($validation_errors)) {
            $errors = array_values($validation_errors);
        }
        
        if (!empty($form_data['phone']) && !validatePhone($form_data['phone'])) {
            $errors[] = "Invalid phone number format";
        }
        
        // Check email uniqueness
        if (empty($errors)) {
            $check = $conn->prepare("SELECT id FROM residents WHERE email = ? AND id != ? AND deleted_at IS NULL");
            $check->bind_param("si", $form_data['email'], $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $errors[] = "Email already used by another resident";
            }
        }
        
        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE residents SET name=?, email=?, phone=?, barangay=?, purok=? WHERE id=?");
            $stmt->bind_param("sssssi", 
                $form_data['name'], 
                $form_data['email'], 
                $form_data['phone'], 
                $form_data['barangay'], 
                $form_data['purok'],
                $id
            );
            
            if ($stmt->execute()) {
                logActivity("Updated Resident", "ID: $id, Name: {$form_data['name']}");
                redirectWithMessage("manage_residents.php", "Resident updated successfully!", "success");
            } else {
                $errors[] = "Database error: " . $conn->error;
            }
        } else {
            $resident = array_merge($resident, $form_data);
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Resident — ECOPING</title>
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

.form-group label .required {
    color: #c44133;
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

.btn-delete {
    background: linear-gradient(135deg, #c44133 0%, #a83529 100%);
    color: white;
    padding: 16px 32px;
    font-size: 16px;
    width: 100%;
    margin-top: 12px;
    font-weight: 700;
    box-shadow: 0 6px 20px rgba(196, 65, 51, 0.2);
}

.btn-delete:hover {
    box-shadow: 0 10px 30px rgba(196, 65, 51, 0.3);
}

.alert {
    padding: 16px 22px;
    border-radius: 12px;
    margin-bottom: 28px;
    font-size: 15px;
    font-weight: 500;
}

.alert-error {
    background: #fef5f4;
    color: #c44133;
    border-left: 4px solid #c44133;
}

.alert ul {
    margin: 8px 0 0 20px;
}

.info-box {
    background: #e3f2fd;
    padding: 16px;
    border-radius: 10px;
    border-left: 4px solid #1976d2;
    margin-bottom: 24px;
    font-size: 14px;
    color: #1565c0;
}

@media (max-width: 768px) {
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
            <h1>✏️ Edit Resident</h1>
            <p>Update resident information</p>
        </div>
        <a href="manage_residents.php" class="btn btn-back">← Back to Residents</a>
    </div>
</header>

<div class="container">
    <div class="card">
        <h2>📝 Update Resident Details</h2>
        <p class="card-subtitle">Resident ID: #<?= $resident['id'] ?></p>

        <div class="info-box">
            ℹ️ <strong>Registered:</strong> <?= formatDateTime($resident['created_at'] ?? date('Y-m-d H:i:s')) ?>
            <?php if($resident['updated_at']): ?>
                • <strong>Last Updated:</strong> <?= formatDateTime($resident['updated_at']) ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>⚠️ Please fix the following errors:</strong>
                <ul>
                    <?php foreach($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" id="editForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" required 
                           value="<?= htmlspecialchars($resident['name']) ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required 
                           value="<?= htmlspecialchars($resident['email']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="09XXXXXXXXX"
                           value="<?= htmlspecialchars($resident['phone'] ?? '') ?>"
                           pattern="09[0-9]{9}">
                </div>

                <div class="form-group">
                    <label for="barangay">Barangay <span class="required">*</span></label>
                    <input type="text" id="barangay" name="barangay" required 
                           value="<?= htmlspecialchars($resident['barangay']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="purok">Purok <span class="required">*</span></label>
                <input type="text" id="purok" name="purok" required 
                       value="<?= htmlspecialchars($resident['purok']) ?>">
            </div>

            <button type="submit" name="update_resident" class="btn btn-submit" id="submitBtn">
                💾 Update Resident
            </button>

            <button type="button" class="btn btn-delete" 
                    onclick="if(confirm('Are you sure you want to delete this resident?')) window.location.href='manage_residents.php?delete=<?= $resident['id'] ?>&csrf=<?= $csrf_token ?>'">
                🗑️ Delete Resident
            </button>
        </form>
    </div>
</div>

<script>
document.getElementById('editForm').addEventListener('submit', function() {
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '⏳ Updating...';
});
</script>

</body>
</html>