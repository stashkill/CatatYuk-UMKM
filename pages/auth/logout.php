<?php
/**
 * Logout Page - CatatYuk
 */

// Include configuration and functions
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Start session
startSession();

// Log activity if user is logged in
if (isLoggedIn()) {
    logActivity('logout', 'User logged out successfully');
}

// Destroy session
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page with success message
header('Location: ' . APP_URL . '/pages/auth/login.php?logout=success');
exit();
?>

