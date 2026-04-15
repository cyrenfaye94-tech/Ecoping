<?php
session_start();
include 'db_connect.php';
if(!isset($_SESSION['admin'])){ header("Location: admin_login.php"); exit(); }

if(isset($_POST['add'])){
    $content = $_POST['content'];
    $stmt = $conn->prepare("INSERT INTO announcements (content, date_posted) VALUES (?, NOW())");
    $stmt->bind_param("s",$content);
    $stmt->execute();
    $stmt->close();
}
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM announcements WHERE id=$id");
}
$announcements = $conn->query("SELECT * FROM announcements ORDER BY date_posted DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Announcements — ECOPING</title>
<style>
body {
    font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: linear-gradient(135deg, #f5f8f6 0%, #e8f0ec 100%);
    margin: 0;
    padding: 0;
}
header {
    background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%);
    color: white;
    padding: 24px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 24px rgba(26, 95, 63, 0.12);
}
header h1 {
    margin: 0;
    font-size: 23px;
    font-weight: 700;
}
header a.btn {
    background: rgba(255,255,255,0.15);
    color: white;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 10px;
    transition: 0.3s ease;
    font-weight: 600;
    border: 2px solid rgba(255,255,255,0.25);
}
header a.btn:hover {
    background: rgba(255,255,255,0.25);
}
.container {
    background: white;
    padding: 32px;
    border-radius: 18px;
    max-width: 950px;
    margin: 32px auto;
    box-shadow: 0 4px 24px rgba(26, 95, 63, 0.06);
}
.container h2 {
    color: #1a5f3f;
    margin-top: 0;
    margin-bottom: 22px;
    font-weight: 700;
}
.card-section {
    margin-bottom: 32px;
}
.card-section h3 {
    color: #1a5f3f;
    margin-bottom: 16px;
    font-size: 19px;
    font-weight: 700;
}
.card-section:last-child {
    margin-bottom: 0;
}
textarea {
    width: 100%;
    padding: 14px;
    margin-bottom: 14px;
    border-radius: 12px;
    border: 2px solid #e0e8e3;
    font-family: "Inter", sans-serif;
    resize: vertical;
    box-sizing: border-box;
    font-size: 15px;
    background: #f9fbfa;
    transition: all 0.3s ease;
}
textarea:focus {
    outline: none;
    border-color: #1a5f3f;
    background: white;
    box-shadow: 0 0 0 4px rgba(26, 95, 63, 0.06);
}
button {
    padding: 12px 20px;
    background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%);
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 700;
    transition: 0.3s ease;
    font-family: "Inter", sans-serif;
    box-shadow: 0 4px 16px rgba(26, 95, 63, 0.2);
}
button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(26, 95, 63, 0.3);
}
a.btn-delete {
    padding: 8px 14px;
    background: #c44133;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 700;
    transition: 0.3s ease;
    display: inline-block;
    font-size: 13px;
}
a.btn-delete:hover {
    background: #a83529;
    transform: translateY(-2px);
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
}
th, td {
    border: 1px solid #e0e8e3;
    padding: 14px;
    text-align: left;
}
th {
    background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%);
    color: white;
    font-weight: 700;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}
tr:nth-child(even) {
    background: #f9fbfa;
}
td:last-child {
    text-align: center;
}
td:nth-child(2) {
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    max-width: 500px;
}
.empty-msg {
    text-align: center;
    color: #6b7c73;
    padding: 24px;
    font-weight: 500;
}
</style>
</head>
<body>
<header>
    <h1>Manage Announcements</h1>
    <a class="btn" href="admin_dashboard.php">← Back to Dashboard</a>
</header>

<div class="container">
    <div class="card-section">
        <h3>📝 Add New Announcement</h3>
        <form method="POST">
            <textarea name="content" rows="4" placeholder="Write announcement..." required></textarea>
            <button type="submit" name="add">Add Announcement</button>
        </form>
    </div>

    <div class="card-section">
        <h3>📢 Existing Announcements</h3>
        <?php if ($announcements && $announcements->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Content</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($a = $announcements->fetch_assoc()): ?>
                <tr>
                    <td><?= $a['id'] ?></td>
                    <td style="word-wrap: break-word; word-break: break-word; overflow-wrap: break-word;">
                        <?= nl2br(htmlspecialchars($a['content'])) ?>
                    </td>
                    <td><?= htmlspecialchars($a['date_posted']) ?></td>
                    <td><a href="?delete=<?= $a['id'] ?>" onclick="return confirm('Delete this announcement?')" class="btn-delete">Delete</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="empty-msg">No announcements yet.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>