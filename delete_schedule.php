<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $stmt = $conn->prepare("DELETE FROM schedules WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: manage_schedules.php?deleted=1");
    } else {
        header("Location: manage_schedules.php?error=delete_failed");
    }
} else {
    header("Location: manage_schedules.php");
}
exit();
?>