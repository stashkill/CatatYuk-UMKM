<?php
/**
 * Clear Read Notifications - CatatYuk
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

try {
    $db = getDB();
    
    // Delete read notifications for current user
    $stmt = $db->prepare("DELETE FROM notifications WHERE is_read = 1 AND (user_id = ? OR user_id IS NULL)");
    $result = $stmt->execute([$current_user['id']]);
    
    if ($result) {
        $deleted_count = $stmt->rowCount();
        
        echo json_encode([
            'success' => true, 
            'message' => "Berhasil menghapus {$deleted_count} notifikasi yang sudah dibaca",
            'deleted_count' => $deleted_count
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal menghapus notifikasi'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Clear read notifications error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan sistem'
    ]);
}
?>

