<?php
/**
 * Delete Debt/Receivable - CatatYuk
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
$debt_id = intval($_POST['id'] ?? 0);

if ($debt_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit();
}

try {
    $db = getDB();
    
    // Get debt/receivable data first
    $stmt = $db->prepare("SELECT * FROM debts_receivables WHERE id = ?");
    $stmt->execute([$debt_id]);
    $debt = $stmt->fetch();
    
    if (!$debt) {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        exit();
    }
    
    // Check if user can delete this debt (admin can delete all, kasir can only delete their own)
    if (!hasRole('admin') && $debt['user_id'] != $current_user['id']) {
        echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menghapus data ini']);
        exit();
    }
    
    // Check if there are payments
    $stmt = $db->prepare("SELECT COUNT(*) as payment_count FROM debt_payments WHERE debt_id = ?");
    $stmt->execute([$debt_id]);
    $payment_count = $stmt->fetch()['payment_count'];
    
    if ($payment_count > 0) {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus data yang sudah memiliki riwayat pembayaran']);
        exit();
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Delete related notifications
        $stmt = $db->prepare("DELETE FROM notifications WHERE related_id = ? AND type IN ('debt_reminder', 'receivable_reminder')");
        $stmt->execute([$debt_id]);
        
        // Delete the debt/receivable
        $stmt = $db->prepare("DELETE FROM debts_receivables WHERE id = ?");
        $result = $stmt->execute([$debt_id]);
        
        if ($result) {
            // Log activity
            logActivity('debt_deleted', "Deleted {$debt['type']}: {$debt['contact_name']} - " . formatCurrency($debt['amount']));
            
            // Commit transaction
            $db->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Data berhasil dihapus'
            ]);
        } else {
            $db->rollBack();
            echo json_encode([
                'success' => false, 
                'message' => 'Gagal menghapus data'
            ]);
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Delete debt error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan sistem'
    ]);
}
?>

