<?php
/**
 * Add Payment - CatatYuk
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
$debt_id = intval($_POST['debt_id'] ?? 0);
$payment_amount = getCurrencyValue($_POST['payment_amount'] ?? '0');
$payment_date = $_POST['payment_date'] ?? '';
$payment_notes = sanitizeInput($_POST['payment_notes'] ?? '');

// Validation
if ($debt_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID utang/piutang tidak valid']);
    exit();
}

if ($payment_amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Jumlah pembayaran harus lebih dari 0']);
    exit();
}

if (empty($payment_date)) {
    echo json_encode(['success' => false, 'message' => 'Tanggal pembayaran harus diisi']);
    exit();
}

if (strtotime($payment_date) > time()) {
    echo json_encode(['success' => false, 'message' => 'Tanggal pembayaran tidak boleh di masa depan']);
    exit();
}

try {
    $db = getDB();
    
    // Get debt/receivable data
    $stmt = $db->prepare("SELECT * FROM debts_receivables WHERE id = ?");
    $stmt->execute([$debt_id]);
    $debt = $stmt->fetch();
    
    if (!$debt) {
        echo json_encode(['success' => false, 'message' => 'Data utang/piutang tidak ditemukan']);
        exit();
    }
    
    // Check if user can add payment (admin can add all, kasir can only add their own)
    if (!hasRole('admin') && $debt['user_id'] != $current_user['id']) {
        echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menambah pembayaran ini']);
        exit();
    }
    
    // Check if debt is already paid
    if ($debt['status'] === 'paid') {
        echo json_encode(['success' => false, 'message' => 'Utang/piutang sudah lunas']);
        exit();
    }
    
    // Check if payment amount exceeds remaining amount
    if ($payment_amount > $debt['remaining_amount']) {
        echo json_encode(['success' => false, 'message' => 'Jumlah pembayaran melebihi sisa yang harus dibayar']);
        exit();
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Insert payment record
        $stmt = $db->prepare("
            INSERT INTO debt_payments (debt_id, amount, payment_date, notes)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$debt_id, $payment_amount, $payment_date, $payment_notes ?: null]);
        
        // Update remaining amount
        $current_remaining = getCurrencyValue($debt['remaining_amount']);
        error_log("DEBUG: remaining_before = {$debt['remaining_amount']}, payment = {$payment_amount}, new_remaining = {$new_remaining}");
        $new_remaining = round($debt['remaining_amount'] - $payment_amount, 2); 
        $new_status = $new_remaining <= 0 ? 'paid' : 'partial';
        
        $stmt = $db->prepare("
            UPDATE debts_receivables 
            SET remaining_amount = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$new_remaining, $new_status, $debt_id]);
        
        // Log activity
        $action_desc = "Added payment for {$debt['type']}: {$debt['contact_name']} - " . formatCurrency($payment_amount);
        logActivity('payment_added', $action_desc);
        
        // Create notification if fully paid
        if ($new_status === 'paid') {
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_id)
                VALUES (?, 'general', ?, ?, ?)
            ");
            
            $title = ucfirst($debt['type']) . ' Lunas';
            $message = ucfirst($debt['type']) . " kepada {$debt['contact_name']} telah lunas sebesar " . formatCurrency($debt['amount']);
            
            $stmt->execute([$current_user['id'], $title, $message, $debt_id]);
        }
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Pembayaran berhasil dicatat',
            'new_status' => $new_status,
            'remaining_amount' => $new_remaining
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Add payment error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan sistem'
    ]);
}
?>

