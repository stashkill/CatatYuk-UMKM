<?php
/**
 * Delete Notification - CatatYuk
 */

// Include configuration and functions
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Require login
startSession();
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$current_user = getCurrentUser();
$notification_id = intval($_POST['id'] ?? 0);

if ($notification_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID notifikasi tidak valid']);
    exit();
}

try {
    $db = getDB();
    
    // Get notification data first
    $stmt = $db->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->execute([$notification_id]);
    $notification = $stmt->fetch();
    
    if (!$notification) {
        echo json_encode(['success' => false, 'message' => 'Notifikasi tidak ditemukan']);
        exit();
    }
    
    // Check if user can delete this notification (admin can delete all, user can only delete their own)
    if (!hasRole('admin') && $notification['user_id'] != $current_user['id'] && $notification['user_id'] !== null) {
        echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menghapus notifikasi ini']);
        exit();
    }
    
    // Delete the notification
    $stmt = $db->prepare("DELETE FROM notifications WHERE id = ?");
    $result = $stmt->execute([$notification_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Notifikasi berhasil dihapus'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal menghapus notifikasi'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Delete notification error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan sistem'
    ]);
}
?>

