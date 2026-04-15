<?php
// ============================================
// ECOPING CORE FUNCTIONS
// Include this in all files: include 'functions.php';
// ============================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// SECURITY FUNCTIONS
// ============================================

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate Philippine phone number
 */
function validatePhone($phone) {
    // Accept formats: 09XXXXXXXXX or +639XXXXXXXXX
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return preg_match('/^(09|\+639)\d{9}$/', $phone);
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['admin']) && isset($_SESSION['admin_id']);
}

/**
 * Require admin authentication
 */
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: admin_login.php");
        exit();
    }
}

/**
 * Get current admin info
 */
function getCurrentAdmin() {
    global $conn;
    if (!isAdmin()) return null;
    
    $stmt = $conn->prepare("SELECT id, username, created_at FROM admin WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// ============================================
// ACTIVITY LOGGING
// ============================================

/**
 * Log admin activity
 */
function logActivity($action, $details = '') {
    global $conn;
    
    if (!isset($_SESSION['admin_id'])) return false;
    
    $admin_id = $_SESSION['admin_id'];
    $admin_username = $_SESSION['admin'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, admin_username, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $admin_id, $admin_username, $action, $details, $ip, $user_agent);
    
    return $stmt->execute();
}

/**
 * Get recent activities
 */
function getRecentActivities($limit = 50) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ============================================
// DATABASE HELPERS
// ============================================

/**
 * Get all residents (excluding deleted)
 */
function getAllResidents($page = 1, $per_page = 50, $search = '', $barangay = '') {
    global $conn;
    
    $offset = ($page - 1) * $per_page;
    
    $where = ["deleted_at IS NULL"];
    $params = [];
    $types = "";
    
    if (!empty($search)) {
        $where[] = "(name LIKE ? OR email LIKE ? OR barangay LIKE ? OR purok LIKE ?)";
        $search_term = "%$search%";
        $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
        $types .= "ssss";
    }
    
    if (!empty($barangay)) {
        $where[] = "barangay = ?";
        $params[] = $barangay;
        $types .= "s";
    }
    
    $where_clause = implode(" AND ", $where);
    
    $sql = "SELECT * FROM residents WHERE $where_clause ORDER BY name ASC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get total residents count
 */
function getResidentsCount($search = '', $barangay = '') {
    global $conn;
    
    $where = ["deleted_at IS NULL"];
    $params = [];
    $types = "";
    
    if (!empty($search)) {
        $where[] = "(name LIKE ? OR email LIKE ? OR barangay LIKE ? OR purok LIKE ?)";
        $search_term = "%$search%";
        $params = [$search_term, $search_term, $search_term, $search_term];
        $types = "ssss";
    }
    
    if (!empty($barangay)) {
        $where[] = "barangay = ?";
        $params[] = $barangay;
        $types .= "s";
    }
    
    $where_clause = implode(" AND ", $where);
    
    $sql = "SELECT COUNT(*) as total FROM residents WHERE $where_clause";
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['total'] ?? 0;
}

/**
 * Soft delete (set deleted_at timestamp)
 */
function softDelete($table, $id) {
    global $conn;
    
    $allowed_tables = ['residents', 'schedules', 'announcements'];
    if (!in_array($table, $allowed_tables)) {
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE $table SET deleted_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    return $stmt->execute();
}

/**
 * Restore soft deleted item
 */
function restoreDeleted($table, $id) {
    global $conn;
    
    $allowed_tables = ['residents', 'schedules', 'announcements'];
    if (!in_array($table, $allowed_tables)) {
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE $table SET deleted_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    return $stmt->execute();
}

// ============================================
// EMAIL QUEUE FUNCTIONS
// ============================================

/**
 * Queue email for sending
 */
function queueEmail($recipient_email, $recipient_name, $subject, $message) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO email_queue (recipient_email, recipient_name, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $recipient_email, $recipient_name, $subject, $message);
    
    return $stmt->execute();
}

/**
 * Queue bulk emails
 */
function queueBulkEmails($recipients, $subject, $message) {
    $queued = 0;
    foreach ($recipients as $recipient) {
        if (queueEmail($recipient['email'], $recipient['name'], $subject, $message)) {
            $queued++;
        }
    }
    return $queued;
}

/**
 * Process email queue (call this from cron job)
 */
function processEmailQueue($limit = 50) {
    global $conn;
    
    include_once 'send_email.php';
    
    $stmt = $conn->prepare("SELECT * FROM email_queue WHERE status = 'pending' AND attempts < 3 ORDER BY created_at ASC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $emails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $sent = 0;
    $failed = 0;
    
    foreach ($emails as $email) {
        // Try to send
        if (sendEmailDirect($email['recipient_email'], $email['subject'], $email['message'])) {
            // Mark as sent
            $update = $conn->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?");
            $update->bind_param("i", $email['id']);
            $update->execute();
            $sent++;
        } else {
            // Increment attempts
            $update = $conn->prepare("UPDATE email_queue SET attempts = attempts + 1, status = IF(attempts >= 2, 'failed', 'pending') WHERE id = ?");
            $update->bind_param("i", $email['id']);
            $update->execute();
            $failed++;
        }
    }
    
    return ['sent' => $sent, 'failed' => $failed];
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Format date for display
 */
function formatDate($date, $format = 'F d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format date and time
 */
function formatDateTime($datetime, $format = 'F d, Y g:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Time ago function
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return "just now";
    if ($diff < 3600) return floor($diff / 60) . " minutes ago";
    if ($diff < 86400) return floor($diff / 3600) . " hours ago";
    if ($diff < 604800) return floor($diff / 86400) . " days ago";
    
    return date('F d, Y', $time);
}

/**
 * Get setting value
 */
function getSetting($key, $default = null) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['setting_value'] ?? $default;
}

/**
 * Update setting
 */
function updateSetting($key, $value) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    
    return $stmt->execute();
}

/**
 * Get unique barangays
 */
function getBarangays() {
    global $conn;
    
    $result = $conn->query("SELECT DISTINCT barangay FROM residents WHERE deleted_at IS NULL ORDER BY barangay ASC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Generate pagination HTML
 */
function generatePagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    // Previous
    if ($current_page > 1) {
        $html .= '<a href="' . $base_url . '&page=' . ($current_page - 1) . '" class="page-link">← Previous</a>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            $html .= '<span class="page-link active">' . $i . '</span>';
        } else {
            // Show first, last, current, and 2 pages around current
            if ($i == 1 || $i == $total_pages || abs($i - $current_page) <= 2) {
                $html .= '<a href="' . $base_url . '&page=' . $i . '" class="page-link">' . $i . '</a>';
            } elseif ($i == 2 || $i == $total_pages - 1) {
                $html .= '<span class="page-link">...</span>';
            }
        }
    }
    
    // Next
    if ($current_page < $total_pages) {
        $html .= '<a href="' . $base_url . '&page=' . ($current_page + 1) . '" class="page-link">Next →</a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit();
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Display flash message HTML
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $type = $flash['type'];
        $message = htmlspecialchars($flash['message']);
        echo "<div class='alert alert-{$type}' id='flash-message'>";
        echo "<span>" . ($type == 'success' ? '✅' : '⚠️') . " {$message}</span>";
        echo "</div>";
    }
}

// ============================================
// VALIDATION FUNCTIONS
// ============================================

/**
 * Validate required fields
 */
function validateRequired($fields) {
    $errors = [];
    foreach ($fields as $name => $value) {
        if (empty(trim($value))) {
            $errors[] = ucfirst($name) . " is required";
        }
    }
    return $errors;
}

/**
 * Validate array of rules
 */
function validate($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule_string) {
        $rules_array = explode('|', $rule_string);
        $value = $data[$field] ?? '';
        
        foreach ($rules_array as $rule) {
            if ($rule == 'required' && empty(trim($value))) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required";
                break;
            }
            
            if ($rule == 'email' && !empty($value) && !validateEmail($value)) {
                $errors[$field] = "Invalid email format";
                break;
            }
            
            if ($rule == 'phone' && !empty($value) && !validatePhone($value)) {
                $errors[$field] = "Invalid phone number";
                break;
            }
            
            if (strpos($rule, 'min:') === 0 && !empty($value)) {
                $min = intval(substr($rule, 4));
                if (strlen($value) < $min) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min} characters";
                    break;
                }
            }
            
            if (strpos($rule, 'max:') === 0 && !empty($value)) {
                $max = intval(substr($rule, 4));
                if (strlen($value) > $max) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$max} characters";
                    break;
                }
            }
        }
    }
    
    return $errors;
}

?>