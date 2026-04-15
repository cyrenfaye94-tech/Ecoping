<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$success = '';
$error = '';

if (isset($_POST['add_announcement'])) {
    $content = trim($_POST['content']);
    $date_posted = date('Y-m-d');

    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO announcements (content, date_posted) VALUES (?, ?)");
        $stmt->bind_param("ss", $content, $date_posted);
        
        if ($stmt->execute()) {
            include 'send_email.php';
            
            $emailSubject = "📢 New Community Announcement - ECOPING";
            $emailMessage = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%); color: white; padding: 20px; border-radius: 10px 10px 0 0;'>
                        <h2 style='margin: 0;'>📢 New Community Announcement</h2>
                    </div>
                    <div style='background: #f9fbfa; padding: 30px; border: 1px solid #e0e8e3; border-radius: 0 0 10px 10px;'>
                        <p style='color: #6b7c73; margin-bottom: 10px;'><strong>Date Posted:</strong> $date_posted</p>
                        <div style='background: white; padding: 20px; border-left: 4px solid #1a5f3f; border-radius: 5px;'>
                            <p style='color: #2c3e35; font-size: 16px; line-height: 1.6; margin: 0;'>$content</p>
                        </div>
                        <hr style='border: none; border-top: 1px solid #e0e8e3; margin: 20px 0;'>
                        <p style='color: #6b7c73; font-size: 12px; margin: 0;'>This is an automated message from ECOPING - Community Waste Management System</p>
                    </div>
                </div>
            ";
            
            $result = sendEmailToResidents($emailSubject, $emailMessage, $conn);
            
            if ($result['success']) {
                $success = "Announcement posted successfully! " . $result['message'];
            } else {
                $success = "Announcement posted but some emails failed: " . $result['message'];
            }
        } else {
            $error = "Error adding announcement: " . $conn->error;
        }
    } else {
        $error = "Please enter announcement content.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Announcement — ECOPING</title>
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
    max-width: 800px;
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

.form-group textarea {
    width: 100%;
    padding: 16px;
    border: 2px solid #e0e8e3;
    border-radius: 12px;
    font-size: 15px;
    font-family: inherit;
    transition: all 0.3s ease;
    background: #f9fbfa;
    resize: vertical;
    min-height: 160px;
}

.form-group textarea:focus {
    outline: none;
    border-color: #1a5f3f;
    background: white;
    box-shadow: 0 0 0 4px rgba(26, 95, 63, 0.06);
}

.char-count {
    text-align: right;
    color: #6b7c73;
    font-size: 13px;
    margin-top: 6px;
    font-weight: 500;
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
}
</style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="header-title">
            <h1>📢 Add New Announcement</h1>
            <p>Post important updates to the community</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-back">← Back to Dashboard</a>
    </div>
</header>

<div class="container">
    <div class="card">
        <h2>📝 Create Announcement</h2>
        <p class="card-subtitle">Share important information with all residents in the community</p>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" onsubmit="return confirm('Are you sure you want to post this announcement?');">
            <div class="form-group">
                <label for="content">Announcement Message *</label>
                <textarea 
                    id="content" 
                    name="content" 
                    placeholder="Enter your announcement here..." 
                    required
                    maxlength="1000"
                    oninput="updateCharCount(this)"
                ></textarea>
                <div class="char-count">
                    <span id="charCount">0</span> / 1000 characters
                </div>
            </div>

            <button type="submit" name="add_announcement" class="btn btn-submit">
                📤 Post Announcement
            </button>
        </form>
    </div>
</div>

<script>
function updateCharCount(textarea) {
    const count = textarea.value.length;
    const counter = document.getElementById('charCount');
    counter.textContent = count;
    
    if (count > 900) {
        counter.style.color = '#c44133';
    } else if (count > 700) {
        counter.style.color = '#d97917';
    } else {
        counter.style.color = '#6b7c73';
    }
}
</script>

</body>
</html>