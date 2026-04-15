<?php
session_start();
include 'db_connect.php';
include 'functions.php';

requireAdmin();

$admin_name = $_SESSION['admin'];

// Mark report as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $conn->query("UPDATE reports SET status = 'read' WHERE id = " . intval($_GET['mark_read']));
    header("Location: admin_dashboard.php");
    exit();
}

// Check which tables have soft delete columns
$has_res_soft  = $conn->query("SHOW COLUMNS FROM residents     LIKE 'deleted_at'")->num_rows > 0;
$has_ann_soft  = $conn->query("SHOW COLUMNS FROM announcements LIKE 'deleted_at'")->num_rows > 0;
$has_sch_soft  = $conn->query("SHOW COLUMNS FROM schedules     LIKE 'deleted_at'")->num_rows > 0;

$res_filter = $has_res_soft ? "WHERE deleted_at IS NULL" : "";
$ann_filter = $has_ann_soft ? "WHERE deleted_at IS NULL" : "";
$sch_filter = $has_sch_soft ? "WHERE deleted_at IS NULL" : "";

// Dashboard data
$announcements       = $conn->query("SELECT * FROM announcements $ann_filter ORDER BY date_posted DESC LIMIT 10");
$schedules           = $conn->query("SELECT * FROM schedules $sch_filter ORDER BY collection_day ASC");
$resident_count      = $conn->query("SELECT COUNT(*) AS c FROM residents $res_filter")->fetch_assoc()['c'] ?? 0;
$total_barangays     = $conn->query("SELECT COUNT(DISTINCT barangay) AS c FROM residents $res_filter")->fetch_assoc()['c'] ?? 0;
$total_schedules     = $conn->query("SELECT COUNT(*) AS c FROM schedules $sch_filter")->fetch_assoc()['c'] ?? 0;
$total_announcements = $conn->query("SELECT COUNT(*) AS c FROM announcements $ann_filter")->fetch_assoc()['c'] ?? 0;

$recent_activities = getRecentActivities(5);

// Reports
$reports_result  = $conn->query("SELECT * FROM reports ORDER BY created_at DESC LIMIT 10");
$unread_count    = $conn->query("SELECT COUNT(*) AS c FROM reports WHERE status = 'unread'")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — ECOPING</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: linear-gradient(135deg, #f5f8f6 0%, #e8f0ec 100%);
    min-height: 100vh;
}

/* HEADER */
header {
    background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%);
    color: white;
    padding: 28px 40px;
    box-shadow: 0 4px 24px rgba(26, 95, 63, 0.12);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.header-left { display: flex; align-items: center; gap: 16px; }

.admin-avatar {
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,0.15);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    border: 2px solid rgba(255,255,255,0.25);
}

.header-text h1 { font-size: 21px; margin: 0; font-weight: 700; }
.header-text p  { font-size: 13px; margin: 0; opacity: 0.85; font-weight: 500; }
.header-actions { display: flex; gap: 10px; }

/* BUTTONS */
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
.btn-primary    { background: #2d8659; color: white; }
.btn-primary:hover { background: #236b47; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(45, 134, 89, 0.3); }
.btn-secondary  { background: rgba(255,255,255,0.15); color: white; border: 2px solid rgba(255,255,255,0.25); }
.btn-secondary:hover { background: rgba(255,255,255,0.25); }
.btn-logout     { background: #c44133; color: white; }
.btn-logout:hover { background: #a83529; }

/* CONTAINER */
.container { max-width: 1400px; margin: 32px auto; padding: 0 20px; }

/* ALERTS */
.alert {
    padding: 16px 22px;
    border-radius: 12px;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 15px;
    font-weight: 500;
    animation: slideDown 0.3s ease;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.alert-success { background: #f0f7f3; color: #2e7d52; border-left: 4px solid #2d8659; }
.alert-error   { background: #fef5f4; color: #c44133; border-left: 4px solid #c44133; }
.alert-info    { background: #e3f2fd; color: #1565c0; border-left: 4px solid #1976d2; }

/* METRICS */
.metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 22px;
    margin-bottom: 32px;
}

.metric-card {
    background: white;
    padding: 28px;
    border-radius: 18px;
    box-shadow: 0 4px 24px rgba(26, 95, 63, 0.06);
    transition: all 0.3s ease;
    border-left: 4px solid #1a5f3f;
}
.metric-card:hover { transform: translateY(-6px); box-shadow: 0 12px 36px rgba(26, 95, 63, 0.12); }

.metric-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }

.metric-icon {
    width: 54px;
    height: 54px;
    background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    box-shadow: 0 4px 16px rgba(26, 95, 63, 0.2);
}

.metric-info h3 { font-size: 13px; color: #6b7c73; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
.metric-info p  { font-size: 38px; font-weight: 800; color: #1a5f3f; margin: 0; }
.metric-footer  { margin-top: 16px; padding-top: 16px; border-top: 1px solid #e0e8e3; font-size: 13px; color: #6b7c73; font-weight: 500; }

/* CARDS */
.card { background: white; border-radius: 18px; padding: 32px; margin-bottom: 28px; box-shadow: 0 4px 24px rgba(26, 95, 63, 0.06); }

.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 15px; }
.card-title  { display: flex; align-items: center; gap: 12px; }
.card-title h2 { color: #1a5f3f; font-size: 23px; margin: 0; font-weight: 700; }
.card-title .icon { font-size: 28px; }
.card-subtitle { color: #6b7c73; font-size: 14px; margin-top: 6px; font-weight: 500; }

/* TABLE */
.table-container { overflow-x: auto; border-radius: 14px; border: 1px solid #e0e8e3; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 16px; text-align: left; border-bottom: 1px solid #e0e8e3; }
th {
    background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%);
    color: white;
    font-weight: 700;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}
tr:hover { background: #f9fbfa; }
tr:last-child td { border-bottom: none; }
td { font-size: 14px; color: #2c3e35; font-weight: 500; }

/* ANNOUNCEMENTS */
.announcement {
    border-left: 4px solid #1a5f3f;
    padding: 16px 22px;
    margin-bottom: 16px;
    background: linear-gradient(90deg, #f9fbfa 0%, #ffffff 100%);
    border-radius: 10px;
    transition: all 0.3s ease;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    overflow: hidden;
}
.announcement:hover { transform: translateX(6px); box-shadow: 0 4px 18px rgba(26, 95, 63, 0.08); }
.announcement strong { color: #1a5f3f; display: block; margin-bottom: 6px; font-size: 13px; font-weight: 700; }
.announcement-content { color: #2c3e35; font-size: 15px; line-height: 1.6; }

.empty { text-align: center; padding: 44px; color: #6b7c73; font-style: italic; font-weight: 500; }

/* QUICK ACTIONS */
.quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 22px; }
.action-btn {
    background: linear-gradient(135deg, #2d8659 0%, #1a5f3f 100%);
    color: white;
    padding: 16px 22px;
    border-radius: 12px;
    text-decoration: none;
    text-align: center;
    font-weight: 700;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 16px rgba(26, 95, 63, 0.15);
}
.action-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(26, 95, 63, 0.25); }

/* ACTIVITY LOG */
.activity-item {
    padding: 12px 16px;
    border-left: 3px solid #e0e8e3;
    margin-bottom: 12px;
    background: #f9fbfa;
    border-radius: 8px;
    transition: all 0.2s ease;
}
.activity-item:hover { border-left-color: #1a5f3f; background: white; }
.activity-action  { font-weight: 600; color: #1a5f3f; font-size: 14px; }
.activity-details { color: #6b7c73; font-size: 13px; margin-top: 4px; }
.activity-time    { color: #6b7c73; font-size: 12px; margin-top: 4px; }

/* BADGE */
.badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; background: #f0f7f3; color: #1a5f3f; }
.badge-unread { background: #fef5f4; color: #c44133; }

/* REPORTS */
.report-item {
    padding: 14px 16px;
    border-left: 3px solid #e0e8e3;
    margin-bottom: 12px;
    background: #f9fbfa;
    border-radius: 8px;
    transition: all 0.2s ease;
    word-wrap: break-word;
    word-break: break-word;
}
.report-item.unread {
    border-left-color: #c44133;
    background: #fef9f8;
}
.report-item:hover { background: white; box-shadow: 0 2px 12px rgba(26, 95, 63, 0.06); }
.report-name    { font-weight: 700; color: #1a5f3f; font-size: 14px; margin-bottom: 4px; }
.report-text    { color: #2c3e35; font-size: 14px; line-height: 1.5; margin-bottom: 8px; }
.report-meta    { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; }
.report-time    { color: #6b7c73; font-size: 12px; }
.mark-read-btn  {
    font-size: 12px;
    font-weight: 700;
    color: #2d8659;
    text-decoration: none;
    background: #f0f7f3;
    padding: 4px 10px;
    border-radius: 6px;
    transition: background 0.2s;
}
.mark-read-btn:hover { background: #e0f0e8; }

/* UNREAD BADGE IN TITLE */
.unread-badge {
    background: #c44133;
    color: white;
    font-size: 11px;
    font-weight: 800;
    padding: 3px 9px;
    border-radius: 999px;
    margin-left: 6px;
    vertical-align: middle;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    header { padding: 22px; }
    .header-content { flex-direction: column; align-items: flex-start; }
    .metrics { grid-template-columns: 1fr; }
    .card { padding: 22px; }
    .card-header { flex-direction: column; align-items: flex-start; }
    th, td { font-size: 12px; padding: 12px; }
}
</style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="header-left">
            <div class="admin-avatar">👤</div>
            <div class="header-text">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?= htmlspecialchars($admin_name) ?>!</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="index.php" class="btn btn-secondary">🏠 Home</a>
            <a href="logout.php" class="btn btn-logout">🚪 Logout</a>
        </div>
    </div>
</header>

<div class="container">

    <?php displayFlashMessage(); ?>

    <!-- METRICS -->
    <div class="metrics">
        <div class="metric-card">
            <div class="metric-header">
                <div class="metric-info">
                    <h3>Total Residents</h3>
                    <p><?= $resident_count ?></p>
                </div>
                <div class="metric-icon">👥</div>
            </div>
            <div class="metric-footer">Across <?= $total_barangays ?> Streets</div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <div class="metric-info">
                    <h3>Announcements</h3>
                    <p><?= $total_announcements ?></p>
                </div>
                <div class="metric-icon">📢</div>
            </div>
            <div class="metric-footer">Community notices posted</div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <div class="metric-info">
                    <h3>Collection Schedules</h3>
                    <p><?= $total_schedules ?></p>
                </div>
                <div class="metric-icon">🗓️</div>
            </div>
            <div class="metric-footer">Active garbage schedules</div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span class="icon">⚡</span>
                <h2>Quick Actions</h2>
            </div>
        </div>
        <div class="quick-actions">
            <a href="add_announcement.php" class="action-btn">📝 New Announcement</a>
            <a href="manage_schedules.php" class="action-btn">📅 Manage Schedules</a>
            <a href="notify_truck.php"     class="action-btn">🚛 Send Truck Alert</a>
            <a href="add_resident.php"     class="action-btn">➕ Add Resident</a>
            <a href="manage_residents.php" class="action-btn">👥 Manage Residents</a>
        </div>
    </div>

    <!-- TWO COLUMN LAYOUT -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 28px; align-items: start;">

        <div>
            <!-- RECENT ANNOUNCEMENTS -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">
                            <span class="icon">📢</span>
                            <h2>Recent Announcements</h2>
                        </div>
                        <p class="card-subtitle">Latest community notices and updates</p>
                    </div>
                    <a href="manage_announcements.php" class="btn btn-primary">Manage All</a>
                </div>

                <?php if ($announcements && $announcements->num_rows > 0): ?>
                    <?php while ($a = $announcements->fetch_assoc()): ?>
                        <div class="announcement">
                            <strong>📅 <?= date("F d, Y", strtotime($a['date_posted'])) ?></strong>
                            <div class="announcement-content">
                                <?= nl2br(htmlspecialchars($a['content'])) ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty">📭 No announcements yet. Create your first announcement!</p>
                <?php endif; ?>
            </div>

            <!-- SCHEDULES TABLE -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">
                            <span class="icon">🗓️</span>
                            <h2>Garbage Collection Schedules</h2>
                        </div>
                        <p class="card-subtitle">Manage collection schedules by barangay and purok</p>
                    </div>
                    <a href="manage_schedules.php" class="btn btn-primary">Manage Schedules</a>
                </div>

                <?php if ($schedules && $schedules->num_rows > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                            <th>Street</th>
                            <th>Block</th>
                                    <th>Collection Day</th>
                                    <th>Time</th>
                                    <th>Contact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($s = $schedules->fetch_assoc()): ?>
                                    <tr>
                                <td><?= htmlspecialchars($s['street']) ?></td>
                                <td><?= htmlspecialchars($s['block_number']) ?></td>
                                        <td><span class="badge"><?= htmlspecialchars($s['collection_day']) ?></span></td>
                                        <td><?= date("g:i A", strtotime($s['time'])) ?></td>
                                        <td><?= htmlspecialchars($s['contact'] ?? 'N/A') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty">📋 No garbage schedules yet. Add schedules to get started!</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT SIDEBAR -->
        <div>

            <!-- RECENT ACTIVITY -->
            <div class="card" style="margin-bottom: 28px;">
                <div class="card-header">
                    <div class="card-title">
                        <span class="icon">📊</span>
                        <h2>Recent Activity</h2>
                    </div>
                </div>

                <?php if (!empty($recent_activities)): ?>
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-action"><?= htmlspecialchars($activity['action']) ?></div>
                            <?php if ($activity['details']): ?>
                                <div class="activity-details"><?= htmlspecialchars($activity['details']) ?></div>
                            <?php endif; ?>
                            <div class="activity-time">
                                <?= timeAgo($activity['created_at']) ?> • <?= htmlspecialchars($activity['admin_username']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty">No recent activity</p>
                <?php endif; ?>
            </div>

            <!-- RECENT REPORTS -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span class="icon">📣</span>
                        <h2>
                            Recent Reports
                            <?php if ($unread_count > 0): ?>
                                <span class="unread-badge"><?= $unread_count ?> new</span>
                            <?php endif; ?>
                        </h2>
                    </div>
                </div>

                <?php if ($reports_result && $reports_result->num_rows > 0): ?>
                    <?php while ($r = $reports_result->fetch_assoc()): ?>
                        <div class="report-item <?= $r['status'] === 'unread' ? 'unread' : '' ?>">
                            <div class="report-name">
                                👤 <?= htmlspecialchars($r['name'] ?: 'Anonymous') ?>
                                <?php if ($r['status'] === 'unread'): ?>
                                    <span class="badge badge-unread" style="margin-left: 6px; font-size: 11px;">New</span>
                                <?php endif; ?>
                            </div>
                            <div class="report-text"><?= nl2br(htmlspecialchars($r['complaint'])) ?></div>
                            <div class="report-meta">
                                <span class="report-time">🕐 <?= date("M d, Y g:i A", strtotime($r['created_at'])) ?></span>
                                <?php if ($r['status'] === 'unread'): ?>
                                    <a href="?mark_read=<?= $r['id'] ?>" class="mark-read-btn">✓ Mark as Read</a>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #6b7c73;">✓ Read</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty">📭 No reports submitted yet.</p>
                <?php endif; ?>
            </div>

        </div>

    </div>

</div>

<script>
// Auto-dismiss flash messages after 5 seconds
setTimeout(() => {
    const msg = document.getElementById('flash-message');
    if (msg) {
        msg.style.transition = 'opacity 0.5s';
        msg.style.opacity = '0';
        setTimeout(() => msg.remove(), 500);
    }
}, 5000);
</script>

</body>
</html>