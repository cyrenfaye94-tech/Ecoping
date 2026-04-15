<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM announcements WHERE id=$id");
$row = $result->fetch_assoc();

if (isset($_POST['update'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];

    $stmt = $conn->prepare("UPDATE announcements SET title=?, content=? WHERE id=?");
    $stmt->bind_param("ssi", $title, $content, $id);
    $stmt->execute();
    header("Location: manage_announcements.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Announcement</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="style.css?ver=<?php echo time(); ?>">
    <style>
    header {
        background-color: #2e8b57;
        color: white;
        padding: 20px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 0;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    header h1 {
        margin: 0;
        font-size: 22px;
    }
    header a.btn {
        background-color: #43a047;
        color: white;
        text-decoration: none;
        padding: 8px 14px;
        border-radius: 6px;
    }
    header a.btn:hover {
        background-color: #256f47;
    }
    .container {
        max-width: 700px;
        margin: 30px auto;
    }
    .container h2 {
        color: #2e8b57;
    }
    input[type="text"], textarea {
        width: 100%;
        padding: 10px;
        margin-bottom: 12px;
        border-radius: 6px;
        border: 1px solid #ccc;
        box-sizing: border-box;
    }
    input[type="text"]:focus, textarea:focus {
        outline: none;
        border-color: #2e8b57;
        box-shadow: 0 0 5px rgba(46, 139, 87, 0.3);
    }
    button {
        padding: 10px 15px;
        background: #2e8b57;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        margin-right: 5px;
    }
    button:hover {
        background: #256f47;
    }
    </style>
</head>
<body>
<header>
    <h1>Edit Announcement</h1>
    <a class="btn" href="manage_announcements.php">← Back</a>
</header>

<div class="container">
    <h2>Edit Announcement</h2>
    <form method="POST">
        <label>Title</label>
        <input type="text" name="title" value="<?= htmlspecialchars($row['title']) ?>" required>
        
        <label>Content</label>
        <textarea name="content" rows="5" required><?= htmlspecialchars($row['content']) ?></textarea>
        
        <button type="submit" name="update">Update</button>
        <a href="manage_announcements.php"><button type="button">Cancel</button></a>
    </form>
</div>
</body>
</html>
