<?php
/**
 * Global Functions for CatatYuk by Keong Balap Dev
 */

// Start session if not already started
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Check if user has specific role
function hasRole($role) {
    startSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/pages/auth/login.php');
        exit();
    }
}

// Redirect to login if not admin
function requireAdmin() {
    requireLogin();
    if (!hasRole('admin')) {
        header('Location: ' . APP_URL . '/pages/dashboard/index.php?error=access_denied');
        exit();
    }
}

// Sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Format currency (Indonesian Rupiah)
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function getCurrencyValue($formatted_string) {
    // Remove 'Rp ' prefix, dots, and commas
    $value = str_replace(["Rp ", ".", ","], "", $formatted_string);
    // Replace comma with dot for decimal if any (though usually not needed for IDR)
    $value = str_replace(",", ".", $value);
    return (float) $value;
}

// Format date to Indonesian format
function formatDate($date, $format = 'd/m/Y') {
    if ($date === null || $date === '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($date));
}

// Format datetime to Indonesian format
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if ($datetime === null || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($datetime));
}

// Generate random string for tokens
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_HASH_ALGO);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Get user data from session
function getCurrentUser() {
    startSession();
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role']
        ];
    }
    return null;
}

// Set flash message
function setFlashMessage($type, $message) {
    startSession();
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Get and clear flash message
function getFlashMessage() {
    startSession();
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate phone number (Indonesian format)
function isValidPhone($phone) {
    $pattern = '/^(\+62|62|0)8[1-9][0-9]{6,9}$/';
    return preg_match($pattern, $phone);
}

// Calculate days between dates
function daysBetween($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    return $interval->days;
}

// Check if date is overdue
function isOverdue($date) {
    return strtotime($date) < strtotime(date('Y-m-d'));
}

// Get month name in Indonesian
function getMonthName($month) {
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $months[(int)$month] ?? '';
}

// Log activity (for audit trail)
function logActivity($action, $description, $user_id = null) {
    try {
        $db = getDB();
        $user_id = $user_id ?? ($_SESSION['user_id'] ?? null);
        
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        // Log error but don't break the application
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

// Get application settings
function getAppSetting($key, $default = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Update application settings
function updateAppSetting($key, $value) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO app_settings (setting_key, setting_value, updated_at) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
        ");
        return $stmt->execute([$key, $value, $value]);
    } catch (Exception $e) {
        return false;
    }
}

// Debug function (only in development)
function debug($data, $die = false) {
    if (defined('DEBUG') && DEBUG === true) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        if ($die) die();
    }
}
?>

