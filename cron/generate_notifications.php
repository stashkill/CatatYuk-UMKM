<?php
/**
 * Generate Notifications Cron Job - CatatYuk by Keong Balap Dev
 * 
 * This script should be run daily to generate automatic notifications
 * Add to crontab: 0 9 * * * /usr/bin/php /path/to/CatatYuk/cron/generate_notifications.php
 */

// Include configuration and functions
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Set timezone
date_default_timezone_set('Asia/Jakarta');

$log_file = dirname(__DIR__) . '/logs/cron_notifications.log';
$log_dir = dirname($log_file);

// Create logs directory if not exists
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

logMessage("Starting notification generation cron job");

try {
    $db = getDB();
    $today = date('Y-m-d');
    $notifications_created = 0;
    
    // 1. Check for debt/receivable due date reminders
    logMessage("Checking for debt/receivable due date reminders");
    
    // Get debts/receivables that are due in 3 days and don't have recent reminders
    $stmt = $db->prepare("
        SELECT dr.*, u.full_name as user_name, u.email as user_email
        FROM debts_receivables dr
        JOIN users u ON dr.user_id = u.id
        WHERE dr.status IN ('pending', 'partial')
        AND dr.due_date IS NOT NULL
        AND dr.due_date BETWEEN ? AND DATE_ADD(?, INTERVAL 3 DAY)
        AND NOT EXISTS (
            SELECT 1 FROM notifications n 
            WHERE n.related_id = dr.id 
            AND n.type = CONCAT(dr.type, '_reminder')
            AND n.created_at >= DATE_SUB(?, INTERVAL 7 DAY)
        )
    ");
    $stmt->execute([$today, $today, $today]);
    $due_items = $stmt->fetchAll();
    
    foreach ($due_items as $item) {
        $days_until_due = (strtotime($item['due_date']) - strtotime($today)) / (60 * 60 * 24);
        
        if ($days_until_due <= 3) {
            $notification_type = $item['type'] . '_reminder';
            $title = $item['type'] === 'debt' ? 'Pengingat Pembayaran Utang' : 'Pengingat Penagihan Piutang';
            
            if ($days_until_due < 0) {
                $message = ucfirst($item['type']) . " kepada {$item['contact_name']} sudah jatuh tempo " . abs($days_until_due) . " hari yang lalu. Sisa: " . formatCurrency($item['remaining_amount']);
            } elseif ($days_until_due == 0) {
                $message = ucfirst($item['type']) . " kepada {$item['contact_name']} jatuh tempo hari ini. Sisa: " . formatCurrency($item['remaining_amount']);
            } else {
                $message = ucfirst($item['type']) . " kepada {$item['contact_name']} akan jatuh tempo dalam " . ceil($days_until_due) . " hari. Sisa: " . formatCurrency($item['remaining_amount']);
            }
            
            // Insert notification
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$item['user_id'], $notification_type, $title, $message, $item['id']]);
            
            $notifications_created++;
            logMessage("Created {$notification_type} notification for user {$item['user_name']} - {$item['contact_name']}");
        }
    }
    
    // 2. Check for scheduled notifications
    logMessage("Checking for scheduled notifications");
    
    $stmt = $db->prepare("
        SELECT * FROM notifications 
        WHERE scheduled_date = ? 
        AND is_sent = 0
    ");
    $stmt->execute([$today]);
    $scheduled_notifications = $stmt->fetchAll();
    
    foreach ($scheduled_notifications as $notification) {
        // Mark as sent
        $stmt = $db->prepare("UPDATE notifications SET is_sent = 1 WHERE id = ?");
        $stmt->execute([$notification['id']]);
        
        $notifications_created++;
        logMessage("Activated scheduled notification: {$notification['title']}");
    }
    
    // 3. Generate monthly summary notifications (on 1st of each month)
    if (date('d') === '01') {
        logMessage("Generating monthly summary notifications");
        
        $last_month = date('Y-m-d', strtotime('first day of last month'));
        $last_month_end = date('Y-m-d', strtotime('last day of last month'));
        $month_name = getMonthName(date('n', strtotime($last_month)));
        $year = date('Y', strtotime($last_month));
        
        // Get all users
        $stmt = $db->prepare("SELECT * FROM users WHERE status = 'active'");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        foreach ($users as $user) {
            // Get user's monthly summary
            $stmt = $db->prepare("
                SELECT 
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
                    COUNT(*) as total_transactions
                FROM transactions 
                WHERE user_id = ? AND transaction_date BETWEEN ? AND ?
            ");
            $stmt->execute([$user['id'], $last_month, $last_month_end]);
            $summary = $stmt->fetch();
            
            if ($summary['total_transactions'] > 0) {
                $profit_loss = $summary['total_income'] - $summary['total_expense'];
                
                $title = "Ringkasan Keuangan Bulan {$month_name} {$year}";
                $message = "Pemasukan: " . formatCurrency($summary['total_income']) . 
                          ", Pengeluaran: " . formatCurrency($summary['total_expense']) . 
                          ", " . ($profit_loss >= 0 ? 'Laba' : 'Rugi') . ": " . formatCurrency($profit_loss) . 
                          ". Total {$summary['total_transactions']} transaksi.";
                
                // Insert notification
                $stmt = $db->prepare("
                    INSERT INTO notifications (user_id, type, title, message)
                    VALUES (?, 'monthly_summary', ?, ?)
                ");
                $stmt->execute([$user['id'], $title, $message]);
                
                $notifications_created++;
                logMessage("Created monthly summary notification for user {$user['full_name']}");
            }
        }
    }
    
    // 4. Clean up old notifications (older than 30 days and read)
    logMessage("Cleaning up old notifications");
    
    $stmt = $db->prepare("
        DELETE FROM notifications 
        WHERE is_read = 1 
        AND created_at < DATE_SUB(?, INTERVAL 30 DAY)
    ");
    $stmt->execute([$today]);
    $deleted_count = $stmt->rowCount();
    
    if ($deleted_count > 0) {
        logMessage("Deleted {$deleted_count} old read notifications");
    }
    
    logMessage("Notification generation completed. Created {$notifications_created} new notifications.");
    
} catch (Exception $e) {
    logMessage("Error in notification generation: " . $e->getMessage());
    error_log("Notification cron error: " . $e->getMessage());
}

logMessage("Notification generation cron job finished\n");
?>

