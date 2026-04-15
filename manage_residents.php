<?php
session_start();
include 'db_connect.php';
include 'functions.php';

requireAdmin();

$success = '';
$error = '';

// Handle DELETE
if (isset($_GET['delete'])) {
    if (!isset($_GET['csrf']) || !verifyCSRFToken($_GET['csrf'])) {
        $error = "Invalid security token";
    } else {
        $id = intval($_GET['delete']);
     $stmt = $conn->prepare("DELETE FROM residents WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
            logActivity("Deleted Resident", "Resident ID: $id");
            redirectWithMessage("manage_residents.php", "Resident deleted successfully!", "success");
        } else {
            $error = "Failed to delete resident";
        }
    }
}

// Handle BULK DELETE
if (isset($_POST['bulk_action']) && $_POST['bulk_action'] == 'delete' && !empty($_POST['selected'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token";
    } else {
        $deleted = 0;
        foreach ($_POST['selected'] as $id) {
            if (softDelete('residents', intval($id))) {
                $deleted++;
            }
        }
        logActivity("Bulk Delete Residents", "Deleted $deleted residents");
        redirectWithMessage("manage_residents.php", "$deleted residents deleted successfully!", "success");
    }
}

// PAGINATION & SEARCH
$per_page = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$barangay_filter = isset($_GET['barangay']) ? sanitize($_GET['barangay']) : '';

// Get total count and residents
$total_residents = getResidentsCount($search, $barangay_filter);
$total_pages = ceil($total_residents / $per_page);
$residents = getAllResidents($page, $per_page, $search, $barangay_filter);

// Get statistics
$total_barangays = $conn->query("SELECT COUNT(DISTINCT barangay) AS c FROM residents WHERE deleted_at IS NULL")->fetch_assoc()['c'] ?? 0;
$total_puroks = $conn->query("SELECT COUNT(DISTINCT CONCAT(barangay, '-', purok)) AS c FROM residents WHERE deleted_at IS NULL")->fetch_assoc()['c'] ?? 0;
$barangays = getBarangays();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Residents — ECOPING</title>
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
    max-width: 1400px;
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

.header-actions {
    display: flex;
    gap: 10px;
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
    color: white;
}

.btn-back {
    background: rgba(255,255,255,0.15);
    border: 2px solid rgba(255,255,255,0.25);
}

.btn-back:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-2px);
}

.btn-add {
    background: #2d8659;
}

.btn-add:hover {
    background: #236b47;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(45, 134, 89, 0.3);
}

.container {
    max-width: 1400px;
    margin: 0 auto;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(26, 95, 63, 0.06);
    border-left: 4px solid #1a5f3f;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(26, 95, 63, 0.1);
}

.stat-card h3 {
    font-size: 13px;
    color: #6b7c73;
    font-weight: 700;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

.stat-card .number {
    font-size: 32px;
    font-weight: 800;
    color: #1a5f3f;
}

.card {
    background: white;
    border-radius: 18px;
    padding: 32px;
    margin-bottom: 28px;
    box-shadow: 0 4px 24px rgba(26, 95, 63, 0.06);
}

.card h2 {
    color: #1a5f3f;
    font-size: 23px;
    margin-bottom: 22px;
    font-weight: 700;
}

.toolbar {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    flex-wrap: wrap;
    align-items: center;
}

.search-bar {
    flex: 1;
    min-width: 250px;
    display: flex;
    gap: 12px;
}

.search-bar input,
.search-bar select {
    padding: 12px 16px;
    border: 2px solid #e0e8e3;
    border-radius: 10px;
    font-size: 14px;
    font-family: inherit;
    background: #f9fbfa;
    transition: all 0.3s ease;
}

.search-bar input {
    flex: 1;
}

.search-bar select {
    min-width: 150px;
}

.search-bar input:focus,
.search-bar select:focus {
    outline: none;
    border-color: #1a5f3f;
    background: white;
    box-shadow: 0 0 0 4px rgba(26, 95, 63, 0.06);
}

.bulk-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.bulk-actions select {
    padding: 10px 14px;
    border: 2px solid #e0e8e3;
    border-radius: 8px;
    font-size: 14px;
    background: #f9fbfa;
    font-weight: 600;
}

.btn-apply {
    padding: 10px 18px;
    background: #2d8659;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-apply:hover {
    background: #236b47;
}

.btn-export {
    padding: 10px 18px;
    background: #1a5f3f;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-export:hover {
    background: #144a32;
}

.table-container {
    overflow-x: auto;
    border-radius: 14px;
    border: 1px solid #e0e8e3;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 16px;
    text-align: left;
    border-bottom: 1px solid #e0e8e3;
}

th {
    background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%);
    color: white;
    font-weight: 700;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

th input[type="checkbox"] {
    cursor: pointer;
    width: 16px;
    height: 16px;
}

tr:hover {
    background: #f9fbfa;
}

tr:last-child td {
    border-bottom: none;
}

td {
    font-size: 14px;
    color: #2c3e35;
    font-weight: 500;
}

td input[type="checkbox"] {
    cursor: pointer;
    width: 16px;
    height: 16px;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
    background: #f0f7f3;
    color: #1a5f3f;
}

.btn-edit, .btn-delete {
    padding: 8px 14px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    display: inline-block;
    margin-right: 6px;
    transition: all 0.3s ease;
}

.btn-edit {
    background: #2d8659;
    color: white;
}

.btn-edit:hover {
    background: #236b47;
    transform: translateY(-2px);
}

.btn-delete {
    background: #c44133;
    color: white;
}

.btn-delete:hover {
    background: #a83529;
    transform: translateY(-2px);
}

.empty {
    text-align: center;
    padding: 44px;
    color: #6b7c73;
    font-weight: 500;
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

/* PAGINATION */
.pagination {
    display: flex;
    gap: 8px;
    justify-content: center;
    align-items: center;
    margin-top: 24px;
    flex-wrap: wrap;
}

.page-link {
    padding: 10px 16px;
    border: 2px solid #e0e8e3;
    border-radius: 8px;
    text-decoration: none;
    color: #2c3e35;
    font-weight: 600;
    transition: all 0.3s ease;
    background: white;
}

.page-link:hover {
    border-color: #1a5f3f;
    background: #f0f7f3;
    transform: translateY(-2px);
}

.page-link.active {
    background: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%);
    color: white;
    border-color: #1a5f3f;
}

.pagination-info {
    color: #6b7c73;
    font-size: 14px;
    font-weight: 500;
}

@media (max-width: 768px) {
    header, .container {
        padding: 20px;
    }
    
    .card {
        padding: 22px;
    }
    
    .header-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-bar {
        flex-direction: column;
    }
    
    table, th, td {
        font-size: 12px;
        padding: 12px;
    }
}
</style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="header-title">
            <h1>👥 Manage Residents</h1>
            <p>View and manage all registered residents</p>
        </div>
        <div class="header-actions">
            <a href="add_resident.php" class="btn btn-add">➕ Add New Resident</a>
            <a href="admin_dashboard.php" class="btn btn-back">← Back to Dashboard</a>
        </div>
    </div>
</header>

<div class="container">
    
    <!-- STATISTICS -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Residents</h3>
            <div class="number"><?= $total_residents ?></div>
        </div>
        <div class="stat-card">
            <h3>Streets</h3>
            <div class="number"><?= $total_barangays ?></div>
        </div>
        <div class="stat-card">
            <h3>Blocks</h3>
            <div class="number"><?= $total_puroks ?></div>
        </div>
        <div class="stat-card">
            <h3>Current Page</h3>
            <div class="number"><?= $page ?> / <?= $total_pages ?></div>
        </div>
    </div>

    <!-- RESIDENTS TABLE -->
    <div class="card">
        <h2>📋 All Registered Residents</h2>

        <?php displayFlashMessage(); ?>

        <!-- TOOLBAR -->
        <form method="GET" class="toolbar">
            <div class="search-bar">
                <input type="text" name="search" id="searchInput" placeholder="🔍 Search by name, email, street, or block..." value="<?= htmlspecialchars($search) ?>">
                <select name="barangay" id="barangayFilter">
                    <option value="">All Streets</option>
                    <?php foreach($barangays as $b): ?>
                        <option value="<?= htmlspecialchars($b['barangay']) ?>" <?= $barangay_filter == $b['barangay'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['barangay']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-apply">Search</button>
            </div>
            <?php if($search || $barangay_filter): ?>
                <a href="manage_residents.php" class="btn-export">Clear Filters</a>
            <?php endif; ?>
        </form>

        <!-- BULK ACTIONS -->
        <form method="POST" id="bulkForm" onsubmit="return confirmBulkDelete()">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="bulk-actions">
                <select name="bulk_action" required>
                    <option value="">Bulk Actions...</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn-apply">Apply</button>
                <span class="pagination-info" id="selectedCount">0 selected</span>
            </div>

            <?php if(!empty($residents)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Street</th>
                            <th>Block</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($residents as $r): ?>
                        <tr>
                            <td><input type="checkbox" name="selected[]" value="<?= $r['id'] ?>" class="row-checkbox"></td>
                            <td><?= $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td><?= htmlspecialchars($r['email']) ?></td>
                            <td><?= htmlspecialchars($r['phone'] ?? 'N/A') ?></td>
                            <td><span class="badge"><?= htmlspecialchars($r['barangay']) ?></span></td>
                            <td><?= htmlspecialchars($r['purok']) ?></td>
                            <td><?= formatDate($r['created_at'] ?? date('Y-m-d')) ?></td>
                            <td>
                                <a href="edit_resident.php?id=<?= $r['id'] ?>" class="btn-edit">✏️ Edit</a>
                                <a href="?delete=<?= $r['id'] ?>&csrf=<?= $csrf_token ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this resident?')">🗑️ Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if($total_pages > 1): ?>
            <div class="pagination">
                <span class="pagination-info">
                    Showing <?= (($page - 1) * $per_page) + 1 ?> to <?= min($page * $per_page, $total_residents) ?> of <?= $total_residents ?> residents
                </span>
                
                <?php
                $base_url = "manage_residents.php?search=" . urlencode($search) . "&barangay=" . urlencode($barangay_filter);
                
                // Previous
                if ($page > 1): ?>
                    <a href="<?= $base_url ?>&page=<?= $page - 1 ?>" class="page-link">← Previous</a>
                <?php endif; ?>
                
                <?php
                // Page numbers
                for ($i = 1; $i <= $total_pages; $i++):
                    if ($i == $page): ?>
                        <span class="page-link active"><?= $i ?></span>
                    <?php elseif ($i == 1 || $i == $total_pages || abs($i - $page) <= 2): ?>
                        <a href="<?= $base_url ?>&page=<?= $i ?>" class="page-link"><?= $i ?></a>
                    <?php elseif ($i == 2 || $i == $total_pages - 1): ?>
                        <span class="page-link">...</span>
                    <?php endif;
                endfor; ?>
                
                <?php
                // Next
                if ($page < $total_pages): ?>
                    <a href="<?= $base_url ?>&page=<?= $page + 1 ?>" class="page-link">Next →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
                <p class="empty">📭 No residents found. <?= ($search || $barangay_filter) ? '<a href="manage_residents.php">Clear filters</a> or ' : '' ?><a href="add_resident.php">Add your first resident</a>!</p>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
// SELECT ALL CHECKBOX
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateSelectedCount();
});

// UPDATE SELECTED COUNT
document.querySelectorAll('.row-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const checked = document.querySelectorAll('.row-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = checked + ' selected';
}

// CONFIRM BULK DELETE
function confirmBulkDelete() {
    const checked = document.querySelectorAll('.row-checkbox:checked').length;
    const action = document.querySelector('[name="bulk_action"]').value;
    
    if (checked === 0) {
        alert('Please select at least one resident');
        return false;
    }
    
    if (action === 'delete') {
        return confirm(`Are you sure you want to delete ${checked} resident(s)? This action can be undone.`);
    }
    
    return true;
}

// AUTO SUBMIT ON FILTER CHANGE
document.getElementById('barangayFilter').addEventListener('change', function() {
    this.form.submit();
});
</script>

</body>
</html>