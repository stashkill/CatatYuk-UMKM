<?php
/**
 * CatatYuk - Entry Point
 * Aplikasi Pencatatan Keuangan UMKM (Cashflow Tracker) By Keong Balap Dev
 */

// Include configuration and functions
require_once 'config/database.php';
require_once 'includes/functions.php';

// Start session
startSession();

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect to dashboard
    header('Location: ' . APP_URL . '/pages/dashboard/index.php');
} else {
    // Redirect to login
    header('Location: ' . APP_URL . '/pages/auth/login.php');
}

exit();
?>

