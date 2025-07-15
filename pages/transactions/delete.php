<?php
/**
 * Delete Transaction - CatatYuk
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
$transaction_id = intval($_POST['id'] ?? 0);

if ($transaction_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID transaksi tidak valid']);
    exit();
}

try {
    $db = getDB();
    
    // Get transaction data first
    $stmt = $db->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']);
        exit();
    }
    
    // Check if user can delete this transaction (admin can delete all, kasir can only delete their own)
    if (!hasRole('admin') && $transaction['user_id'] != $current_user['id']) {
        echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menghapus transaksi ini']);
        exit();
    }
    
    // Delete the transaction
    $stmt = $db->prepare("DELETE FROM transactions WHERE id = ?");
    $result = $stmt->execute([$transaction_id]);
    
    if ($result) {
        // Log activity
        logActivity('transaction_deleted', "Deleted {$transaction['type']} transaction: {$transaction['description']} - " . formatCurrency($transaction['amount']));
        
        echo json_encode([
            'success' => true, 
            'message' => 'Transaksi berhasil dihapus'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal menghapus transaksi'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Delete transaction error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan sistem'
    ]);
}
?>

