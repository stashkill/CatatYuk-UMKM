<?php
/**
 * Export Report to PDF - CatatYuk
 */

// Include configuration and functions
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Require login
requireLogin();

$current_user = getCurrentUser();

// Get filter parameters
$report_type = $_GET['type'] ?? 'monthly';
$year = intval($_GET['year'] ?? date('Y'));
$month = intval($_GET['month'] ?? date('n'));
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$export_format = $_GET['export'] ?? 'pdf';

// Set date range based on report type
switch ($report_type) {
    case 'monthly':
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        $report_title = 'Laporan Bulanan ' . getMonthName($month) . ' ' . $year;
        $filename = 'laporan_bulanan_' . $year . '_' . sprintf('%02d', $month);
        break;
    case 'yearly':
        $start_date = $year . '-01-01';
        $end_date = $year . '-12-31';
        $report_title = 'Laporan Tahunan ' . $year;
        $filename = 'laporan_tahunan_' . $year;
        break;
    case 'custom':
        if (empty($start_date)) $start_date = date('Y-m-01');
        if (empty($end_date)) $end_date = date('Y-m-d');
        $report_title = 'Laporan Custom ' . formatDate($start_date) . ' - ' . formatDate($end_date);
        $filename = 'laporan_custom_' . str_replace('-', '', $start_date) . '_' . str_replace('-', '', $end_date);
        break;
    default:
        $report_type = 'monthly';
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        $report_title = 'Laporan Bulanan ' . getMonthName($month) . ' ' . $year;
        $filename = 'laporan_bulanan_' . $year . '_' . sprintf('%02d', $month);
}

try {
    $db = getDB();
    
    // Get summary statistics
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
            COUNT(CASE WHEN type = 'income' THEN 1 END) as income_count,
            COUNT(CASE WHEN type = 'expense' THEN 1 END) as expense_count,
            AVG(CASE WHEN type = 'income' THEN amount END) as avg_income,
            AVG(CASE WHEN type = 'expense' THEN amount END) as avg_expense
        FROM transactions 
        WHERE transaction_date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $summary = $stmt->fetch();
    
    // Get category breakdown
    $stmt = $db->prepare("
        SELECT 
            c.name as category_name,
            c.type as category_type,
            SUM(t.amount) as total_amount,
            COUNT(t.id) as transaction_count,
            AVG(t.amount) as avg_amount
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.transaction_date BETWEEN ? AND ?
        GROUP BY c.id, c.name, c.type
        ORDER BY c.type ASC, total_amount DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $categories = $stmt->fetchAll();
    
    // Get top transactions
    $stmt = $db->prepare("
        SELECT t.*, c.name as category_name
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.transaction_date BETWEEN ? AND ?
        ORDER BY t.amount DESC
        LIMIT 20
    ");
    $stmt->execute([$start_date, $end_date]);
    $top_transactions = $stmt->fetchAll();
    
    // Get company settings
    $company_name = getAppSetting('company_name', 'UMKM Saya');
    $company_address = getAppSetting('company_address', 'Alamat Perusahaan');
    $company_phone = getAppSetting('company_phone', '08123456789');
    
} catch (Exception $e) {
    error_log('Export report error: ' . $e->getMessage());
    setFlashMessage('error', 'Terjadi kesalahan saat mengambil data laporan');
    header('Location: index.php');
    exit();
}

// Calculate additional metrics
$profit_loss = $summary['total_income'] - $summary['total_expense'];
$profit_margin = $summary['total_income'] > 0 ? ($profit_loss / $summary['total_income']) * 100 : 0;

if ($export_format === 'pdf') {
    // Generate PDF using TCPDF or similar library
    // For now, we'll create an HTML version that can be printed to PDF
    
    // Set headers for PDF download
    header('Content-Type: text/html; charset=utf-8');
    
    // Generate HTML content for PDF
    $html_content = generateReportHTML($report_title, $company_name, $company_address, $company_phone, 
                                     $start_date, $end_date, $summary, $categories, $top_transactions, 
                                     $profit_loss, $profit_margin, $current_user);
    
    $tmp_dir = __DIR__ . '/tmp'; //Make temp folder
    if (!file_exists($tmp_dir)) {
        mkdir(tmp_dir, 0777, true); 
    }
    // Save HTML to temporary file
    $temp_file = $tmp_dir . '/' . $filename . '.html';
    $pdf_file = $tmp_dir . '/' . $filename . '.pdf';

    file_put_contents($temp_file, $html_content);
    
    // Convert HTML to PDF using wkhtmltopdf (if available) or display HTML for browser PDF generation
    if (shell_exec('which wkhtmltopdf')) {
        $command = "wkhtmltopdf --page-size A4 --margin-top 0.75in --margin-right 0.75in --margin-bottom 0.75in --margin-left 0.75in '$temp_file' '$pdf_file'";
        shell_exec($command);
        
        if (file_exists($pdf_file)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
            header('Content-Length: ' . filesize($pdf_file));
            readfile($pdf_file);
            unlink($temp_file);
            unlink($pdf_file);
            exit();
        }
    }
    
    // Fallback: Display HTML with print styles
    echo $html_content;
    echo '<script>window.print();</script>';
    unlink($temp_file);
    
} else {
    // Export as CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV Headers
    fputcsv($output, ['Laporan Keuangan - ' . $report_title]);
    fputcsv($output, ['Periode: ' . formatDate($start_date) . ' - ' . formatDate($end_date)]);
    fputcsv($output, ['Dibuat: ' . formatDateTime(date('Y-m-d H:i:s'))]);
    fputcsv($output, []);
    
    // Summary
    fputcsv($output, ['RINGKASAN']);
    fputcsv($output, ['Total Pemasukan', formatCurrency($summary['total_income'])]);
    fputcsv($output, ['Total Pengeluaran', formatCurrency($summary['total_expense'])]);
    fputcsv($output, ['Laba/Rugi', formatCurrency($profit_loss)]);
    fputcsv($output, ['Margin Laba (%)', number_format($profit_margin, 2)]);
    fputcsv($output, []);
    
    // Category breakdown
    fputcsv($output, ['BREAKDOWN KATEGORI']);
    fputcsv($output, ['Kategori', 'Jenis', 'Total', 'Jumlah Transaksi', 'Rata-rata']);
    
    foreach ($categories as $category) {
        fputcsv($output, [
            $category['category_name'],
            $category['category_type'] === 'income' ? 'Pemasukan' : 'Pengeluaran',
            $category['total_amount'],
            $category['transaction_count'],
            $category['avg_amount']
        ]);
    }
    
    fputcsv($output, []);
    
    // Top transactions
    fputcsv($output, ['TRANSAKSI TERBESAR']);
    fputcsv($output, ['Tanggal', 'Kategori', 'Deskripsi', 'Jenis', 'Jumlah']);
    
    foreach ($top_transactions as $transaction) {
        fputcsv($output, [
            formatDate($transaction['transaction_date']),
            $transaction['category_name'],
            $transaction['description'],
            $transaction['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran',
            $transaction['amount']
        ]);
    }
    
    fclose($output);
}

function generateReportHTML($title, $company_name, $company_address, $company_phone, 
                           $start_date, $end_date, $summary, $categories, $top_transactions, 
                           $profit_loss, $profit_margin, $current_user) {
    
    $html = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #0066cc;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            color: #333;
        }
        
        .company-info {
            margin-top: 10px;
            font-size: 11px;
            color: #666;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #0066cc;
        }
        
        .summary-value {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .income { color: #28a745; }
        .expense { color: #dc3545; }
        .profit { color: #17a2b8; }
        .loss { color: #dc3545; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .text-right {
            text-align: right;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin: 30px 0 15px 0;
            color: #0066cc;
            border-bottom: 1px solid #0066cc;
            padding-bottom: 5px;
        }
        
        .footer {
            margin-top: 50px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
            font-size: 11px;
            color: #666;
        }
        
        @media print {
            body { margin: 0; padding: 15px; }
            .summary-grid { grid-template-columns: repeat(4, 1fr); }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($company_name) . '</h1>
        <h2>' . htmlspecialchars($title) . '</h2>
        <div class="company-info">
            ' . htmlspecialchars($company_address) . ' | Tel: ' . htmlspecialchars($company_phone) . '<br>
            Periode: ' . formatDate($start_date) . ' - ' . formatDate($end_date) . '<br>
            Dibuat: ' . formatDateTime(date('Y-m-d H:i:s')) . ' oleh ' . htmlspecialchars($current_user['full_name']) . '
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <h3>Total Pemasukan</h3>
            <div class="summary-value income">' . formatCurrency($summary['total_income']) . '</div>
            <div>' . $summary['income_count'] . ' transaksi</div>
        </div>
        
        <div class="summary-card">
            <h3>Total Pengeluaran</h3>
            <div class="summary-value expense">' . formatCurrency($summary['total_expense']) . '</div>
            <div>' . $summary['expense_count'] . ' transaksi</div>
        </div>
        
        <div class="summary-card">
            <h3>' . ($profit_loss >= 0 ? 'Laba' : 'Rugi') . '</h3>
            <div class="summary-value ' . ($profit_loss >= 0 ? 'profit' : 'loss') . '">' . formatCurrency($profit_loss) . '</div>
            <div>Margin: ' . number_format($profit_margin, 1) . '%</div>
        </div>
        
        <div class="summary-card">
            <h3>Total Transaksi</h3>
            <div class="summary-value">' . ($summary['income_count'] + $summary['expense_count']) . '</div>
            <div>Rata-rata: ' . formatCurrency(($summary['avg_income'] + $summary['avg_expense']) / 2) . '</div>
        </div>
    </div>';

    if (!empty($categories)) {
        $html .= '<div class="section-title">Breakdown Kategori</div>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Jenis</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Transaksi</th>
                    <th class="text-right">Rata-rata</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($categories as $category) {
            $html .= '<tr>
                <td>' . htmlspecialchars($category['category_name']) . '</td>
                <td>' . ($category['category_type'] === 'income' ? 'Pemasukan' : 'Pengeluaran') . '</td>
                <td class="text-right">' . formatCurrency($category['total_amount']) . '</td>
                <td class="text-right">' . $category['transaction_count'] . '</td>
                <td class="text-right">' . formatCurrency($category['avg_amount']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
    }

    if (!empty($top_transactions)) {
        $html .= '<div class="section-title">Transaksi Terbesar</div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Jenis</th>
                    <th class="text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($top_transactions as $transaction) {
            $html .= '<tr>
                <td>' . formatDate($transaction['transaction_date']) . '</td>
                <td>' . htmlspecialchars($transaction['category_name']) . '</td>
                <td>' . htmlspecialchars($transaction['description']) . '</td>
                <td>' . ($transaction['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran') . '</td>
                <td class="text-right ' . ($transaction['type'] === 'income' ? 'income' : 'expense') . '">' . formatCurrency($transaction['amount']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
    }

    $html .= '<div class="footer">
        <div style="float: left;">
            Laporan ini dibuat secara otomatis oleh sistem CatatYuk<br>
            Aplikasi Pencatatan Keuangan UMKM
        </div>
        <div style="float: right;">
            Halaman 1 dari 1<br>
            ' . date('d/m/Y H:i:s') . '
        </div>
        <div style="clear: both;"></div>
    </div>

</body>
</html>';

    return $html;
}
?>

