<?php
include 'db_connect.php';

// Handle complaint submission
$report_success = false;
$report_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint'])) {
    $name      = trim($_POST['reporter_name'] ?? 'Anonymous');
    $complaint = trim($_POST['complaint'] ?? '');

    if (!empty($complaint)) {
        $stmt = $conn->prepare("INSERT INTO reports (name, complaint) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $complaint);
        if ($stmt->execute()) {
            $report_success = true;
        } else {
            $report_error = "Failed to submit. Please try again.";
        }
    } else {
        $report_error = "Please enter your complaint before submitting.";
    }
}

$announcements_q = $conn->query("SELECT * FROM announcements ORDER BY date_posted DESC");
$schedules_q     = $conn->query("SELECT * FROM schedules ORDER BY id DESC");
$ann_count       = intval($conn->query("SELECT COUNT(*) AS c FROM announcements")->fetch_assoc()['c'] ?? 0);
$sched_count     = intval($conn->query("SELECT COUNT(*) AS c FROM schedules")->fetch_assoc()['c'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ECOPING — Home</title>
<style>
:root {
    --green-primary: #1a5f3f;
    --green-secondary: #2d8659;
    --green-accent: #4a9d6f;
    --text-dark: #2c3e35;
    --text-muted: #6b7c73;
    --bg-light: #f5f8f6;
    --bg-lighter: #e8f0ec;
    --card: #ffffff;
    --border: #e0e8e3;
    --transition: 300ms cubic-bezier(.2, .9, .3, 1);
}

* { box-sizing: border-box; }
html, body { height: 100%; }

body {
    margin: 0;
    font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: linear-gradient(180deg, var(--bg-light) 0%, var(--bg-lighter) 100%);
    color: var(--text-dark);
    -webkit-font-smoothing: antialiased;
    padding: 28px;
}

/* DARK MODE */
body.dark { background: #1a2420; color: #e6ebe8; }
body.dark .card { background: #243029; color: #e6ebe8; box-shadow: none; border: 1px solid #2d3e35; }
body.dark .ann-item { background: linear-gradient(180deg, #243029 0%, #2d3e35 100%); border-left-color: var(--green-accent); }
body.dark .theme-pill { background: #243029; color: #a8c4b5; border-color: #2d3e35; }
body.dark .table th { background: #1a5f3f !important; }
body.dark .report-form textarea { background: #1a2420; color: #e6ebe8; border-color: #2d3e35; }
body.dark .report-form input[type="text"] { background: #1a2420; color: #e6ebe8; border-color: #2d3e35; }

.wrapper { max-width: 1100px; margin: 0 auto; }

/* TOP BAR */
.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 24px;
}

.brand-left { display: flex; align-items: center; gap: 20px; }

.ecoping-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 800;
    color: var(--green-primary);
    font-size: 32px;
    letter-spacing: 1px;
    margin: 0;
}

.logo-o { display: inline-flex; align-items: center; justify-content: center; }
.logo-o img {
    height: 52px;
    width: auto;
    display: block;
    animation: spin 5s linear infinite;
    border-radius: 50%;
    padding: 4px;
    box-shadow: 0 4px 20px rgba(26, 95, 63, 0.15);
}

.brand-sub { font-size: 14px; color: var(--text-muted); margin-top: 4px; font-weight: 500; }

/* THEME TOGGLE */
.theme-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 999px;
    background: #fff;
    color: var(--green-primary);
    border: 2px solid var(--border);
    cursor: pointer;
    font-weight: 700;
    box-shadow: 0 4px 16px rgba(26, 95, 63, 0.08);
    transition: all 0.3s ease;
}
.theme-pill:hover { transform: translateY(-2px); }

/* LAYOUT */
.grid { display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start; }

/* CARD */
.card {
    background: var(--card);
    border-radius: 18px;
    padding: 24px;
    box-shadow: 0 4px 24px rgba(26, 95, 63, 0.06);
    border: 1px solid var(--border);
    transition: transform var(--transition), box-shadow var(--transition);
}
.card:hover { transform: translateY(-6px); box-shadow: 0 12px 36px rgba(26, 95, 63, 0.1); }
.card-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 16px; }

.small-muted { color: var(--text-muted); font-size: 13px; font-weight: 500; }

/* ANNOUNCEMENTS */
.ann-list { display: flex; flex-direction: column; gap: 14px; }
.ann-item {
    border-left: 4px solid var(--green-primary);
    background: linear-gradient(180deg, #f9fbfa 0%, #f5f8f6 100%);
    padding: 16px 18px;
    border-radius: 10px;
    line-height: 1.5;
    box-shadow: 0 2px 12px rgba(26, 95, 63, 0.04);
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    overflow: hidden;
}
.ann-item time {
    display: block;
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 8px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* TABLE */
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th,
.table td { padding: 14px; border-bottom: 1px solid var(--border); text-align: center; }
.table th {
    background: linear-gradient(135deg, var(--green-primary) 0%, var(--green-secondary) 100%);
    color: white;
    font-weight: 700;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}
.table td { color: var(--text-dark); font-weight: 500; }

/* SIDEBAR */
.side-stack { display: flex; flex-direction: column; gap: 16px; }
.quick-btn {
    background: #f0f7f3;
    color: var(--green-primary);
    padding: 9px 14px;
    border-radius: 8px;
    cursor: pointer;
    border: 1px solid rgba(26, 95, 63, 0.1);
    font-weight: 700;
    font-size: 13px;
    transition: all 0.3s ease;
}
.quick-btn:hover { transform: translateY(-2px); background: #e8f0ec; }

.empty {
    color: var(--text-muted);
    font-size: 14px;
    text-align: center;
    padding: 24px;
    border-radius: 10px;
    background: linear-gradient(180deg, var(--bg-light), var(--bg-lighter));
    font-weight: 500;
}

/* REPORT FORM */
.report-form { margin-top: 14px; display: flex; flex-direction: column; gap: 10px; }
.report-form input[type="text"],
.report-form textarea {
    width: 100%;
    padding: 11px 14px;
    border: 2px solid var(--border);
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    color: var(--text-dark);
    background: #f9fbfa;
    resize: vertical;
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
}
.report-form input[type="text"]:focus,
.report-form textarea:focus {
    border-color: var(--green-secondary);
    box-shadow: 0 0 0 3px rgba(26, 95, 63, 0.08);
}
.report-form textarea { min-height: 90px; }
.report-submit {
    background: linear-gradient(135deg, var(--green-primary), var(--green-secondary));
    color: white;
    border: none;
    border-radius: 10px;
    padding: 11px 18px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: inherit;
}
.report-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(26, 95, 63, 0.25); }

.alert-success-box {
    background: #f0f7f3;
    color: #2e7d52;
    border: 1px solid rgba(45, 134, 89, 0.2);
    border-left: 4px solid #2d8659;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 14px;
    font-weight: 600;
}
.alert-error-box {
    background: #fef5f4;
    color: #c44133;
    border: 1px solid rgba(196, 65, 51, 0.2);
    border-left: 4px solid #c44133;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 14px;
    font-weight: 600;
}

/* ANIMATIONS */
.fade-slide { opacity: 0; transform: translateY(16px); animation: fadeInUp 560ms ease forwards; }
@keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* RESPONSIVE */
@media (max-width: 1000px) {
    .grid { grid-template-columns: 1fr; }
    .topbar { flex-direction: column; align-items: flex-start; gap: 14px; }
    .ecoping-title { font-size: 28px; }
}
</style>
</head>
<body>

<div class="wrapper">

    <!-- TOP BAR -->
    <div class="topbar fade-slide" style="animation-delay: .05s">
        <div class="brand-left">
            <h1 class="ecoping-title">
                EC
                <span class="logo-o"><img src="recycle.png" alt="recycle icon"></span>
                PING
            </h1>
            <div class="brand-sub small-muted">Dumaguete City — Community Waste Management</div>
        </div>

        <button id="themeToggle" class="theme-pill" aria-pressed="false">🌙 Dark Mode</button>
    </div>

    <!-- MAIN GRID -->
    <div class="grid">

        <main>

            <!-- ANNOUNCEMENTS SECTION -->
            <section class="card fade-slide" style="animation-delay: .12s">
                <div class="card-head">
                    <div>
                        <div style="font-weight: 800; font-size: 20px; color: var(--green-primary)">📢 Announcements</div>
                        <div class="small-muted">Latest community notices</div>
                    </div>
                </div>

                <div class="ann-list">
                    <?php if ($announcements_q && $announcements_q->num_rows > 0): ?>
                        <?php while ($a = $announcements_q->fetch_assoc()): ?>
                            <article class="ann-item">
                                <time><?= date("F d, Y", strtotime($a['date_posted'])) ?></time>
                                <div style="word-wrap: break-word; word-break: break-word;">
                                    <?= nl2br(htmlspecialchars($a['content'])) ?>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty">No announcements at the moment.</div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- SCHEDULES SECTION -->
            <section class="card fade-slide" style="animation-delay: .18s; margin-top: 20px;">
                <div class="card-head">
                    <div>
                        <div style="font-weight: 800; font-size: 20px; color: var(--green-primary)">🗓️ Garbage Collection Schedules</div>
                        <div class="small-muted">See upcoming collection for each area</div>
                    </div>
                </div>

                <div style="overflow: auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Street</th>
                                <th>Block</th>
                                <th>Day</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($schedules_q && $schedules_q->num_rows > 0): ?>
                                <?php while ($s = $schedules_q->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s['street']) ?></td>
                                        <td><?= htmlspecialchars($s['block_number']) ?></td>
                                        <td><?= htmlspecialchars($s['collection_day']) ?></td>
                                        <td><?= date("g:i A", strtotime($s['time'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty">No schedules available.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </main>

        <!-- SIDEBAR -->
        <aside class="side-stack">

            <!-- QUICK SUMMARY -->
            <div class="card fade-slide" style="animation-delay: .22s;">
                <div style="font-weight: 800; color: var(--green-primary); margin-bottom: 10px; font-size: 18px;">Quick Summary</div>
                <div class="small-muted">At a glance</div>
                <hr style="border: none; height: 1px; background: var(--border); margin: 14px 0">
                <div style="display: flex; justify-content: space-between; gap: 10px;">
                    <div>
                        <div style="font-size: 28px; font-weight: 800; color: var(--green-primary)"><?= $ann_count ?></div>
                        <div class="small-muted">Announcements</div>
                    </div>
                    <div>
                        <div style="font-size: 28px; font-weight: 800; color: var(--green-primary)"><?= $sched_count ?></div>
                        <div class="small-muted">Schedules</div>
                    </div>
                </div>
            </div>

            <!-- REPORT TO ADMIN -->
            <div class="card fade-slide" style="animation-delay: .26s;">
                <div style="font-weight: 800; color: var(--green-primary); font-size: 18px;">📣 Report to Admin</div>
                <div class="small-muted">Have a concern? Let us know.</div>

                <?php if ($report_success): ?>
                    <div class="alert-success-box" style="margin-top: 14px;">✅ Your report has been submitted! We'll look into it soon.</div>
                <?php else: ?>
                    <?php if ($report_error): ?>
                        <div class="alert-error-box" style="margin-top: 14px;">⚠️ <?= htmlspecialchars($report_error) ?></div>
                    <?php endif; ?>
                    <form method="POST" class="report-form">
                        <input
                            type="text"
                            name="reporter_name"
                            placeholder="Your name (optional)"
                            maxlength="100"
                        >
                        <textarea
                            name="complaint"
                            placeholder="Describe your complaint or concern here..."
                            required
                        ></textarea>
                        <button type="submit" class="report-submit">📤 Submit Report</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- INFO -->
            <div class="card fade-slide" style="animation-delay: .30s;">
                <div style="font-weight: 800; color: var(--green-primary); font-size: 18px;">ℹ️ About</div>
                <div class="small-muted" style="margin-top: 8px;">Administrators manage community schedules, post announcements, and send notifications.</div>
                <div style="margin-top: 14px;">
                    <button class="quick-btn" onclick="alert('Administrators can login to manage community schedules, post announcements, and send notifications.')">Learn More</button>
                </div>
            </div>

            <!-- LOGO CARD -->
            <div class="card fade-slide" style="animation-delay: .34s; text-align: center;">
                <img src="recycle.png" alt="recycle" style="width: 120px; height: auto; display: block; margin: 0 auto 12px; animation: spin 6s linear infinite;">
                <div class="small-muted" style="font-weight: 600;">Recycle • Reduce • Reuse</div>
            </div>

        </aside>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('.fade-slide').forEach((el, i) => {
        el.style.animationDelay = (i * 80 + 120) + 'ms';
    });

    const toggle = document.getElementById('themeToggle');

    function setTheme(isDark) {
        if (isDark) {
            document.body.classList.add('dark');
            toggle.innerText = '☀ Light Mode';
            toggle.setAttribute('aria-pressed', 'true');
        } else {
            document.body.classList.remove('dark');
            toggle.innerText = '🌙 Dark Mode';
            toggle.setAttribute('aria-pressed', 'false');
        }
    }

    setTheme(localStorage.getItem('ecoping_theme') === 'dark');

    toggle.addEventListener('click', () => {
        const isDark = document.body.classList.toggle('dark');
        localStorage.setItem('ecoping_theme', isDark ? 'dark' : 'light');
        setTheme(isDark);
    });

    toggle.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggle.click();
        }
    });

});
</script>

</body>
</html>