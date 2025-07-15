<?php
/**
 * Get Debt/Receivable Data - CatatYuk
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

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$debt_id = intval($_GET['id'] ?? 0);

if ($debt_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit();
}

try {
    $db = getDB();
    
    // Get debt/receivable data with payment history
    $stmt = $db->prepare("
        SELECT dr.*, 
               DATEDIFF(dr.due_date, CURDATE()) as days_until_due,
               (dr.amount - dr.remaining_amount) as paid_amount
        FROM debts_receivables dr
        WHERE dr.id = ?
    ");
    $stmt->execute([$debt_id]);
    $debt = $stmt->fetch();
    
    if (!$debt) {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        exit();
    }
    
    // Get payment history
    $stmt = $db->prepare("
        SELECT * FROM debt_payments 
        WHERE debt_id = ? 
        ORDER BY payment_date DESC, created_at DESC
    ");
    $stmt->execute([$debt_id]);
    $payments = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $debt,
        'payments' => $payments
    ]);
    
} catch (Exception $e) {
    error_log('Get debt error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan sistem'
    ]);
}
?>

