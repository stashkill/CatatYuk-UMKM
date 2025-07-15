<?php
/**
 * Notifications API - CatatYuk
 */

// Include configuration and functions
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Require login
startSession();
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$current_user = getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDB();
    
    if ($method === 'GET') {
        // Get notifications for current user
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? OR user_id IS NULL 
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $stmt->execute([$current_user['id']]);
        $notifications = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $notifications
        ]);
        
    } elseif ($method === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'mark_read') {
            $notification_id = $_POST['id'] ?? 0;
            
            $stmt = $db->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE id = ? AND (user_id = ? OR user_id IS NULL)
            ");
            $stmt->execute([$notification_id, $current_user['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
            
        } elseif ($action === 'mark_all_read') {
            $stmt = $db->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE (user_id = ? OR user_id IS NULL) AND is_read = FALSE
            ");
            $stmt->execute([$current_user['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
            
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
        }
        
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
    error_log('Notifications API error: ' . $e->getMessage());
}
?>

