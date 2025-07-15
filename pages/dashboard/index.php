<?php
/**
 * Dashboard - CatatYuk
 */

// Include configuration and functions
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Require login
requireLogin();

$page_title = 'Dashboard';
$current_user = getCurrentUser();

// Get date range for current month
$current_month = date('Y-m');
$start_date = $current_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

try {
    $db = getDB();
    
    // Get monthly statistics
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
            COUNT(CASE WHEN type = 'income' THEN 1 END) as income_count,
            COUNT(CASE WHEN type = 'expense' THEN 1 END) as expense_count
        FROM transactions 
        WHERE transaction_date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $monthly_stats = $stmt->fetch();
    
    // Calculate profit/loss
    $total_income = $monthly_stats['total_income'] ?? 0;
    $total_expense = $monthly_stats['total_expense'] ?? 0;
    $profit_loss = $total_income - $total_expense;
    
    // Get debt/receivable statistics
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN type = 'debt' AND status != 'paid' THEN remaining_amount ELSE 0 END) as total_debt,
            SUM(CASE WHEN type = 'receivable' AND status != 'paid' THEN remaining_amount ELSE 0 END) as total_receivable,
            COUNT(CASE WHEN type = 'debt' AND status = 'overdue' THEN 1 END) as overdue_debt_count,
            COUNT(CASE WHEN type = 'receivable' AND status = 'overdue' THEN 1 END) as overdue_receivable_count
        FROM debts_receivables
    ");
    $stmt->execute();
    $debt_stats = $stmt->fetch();
    
    // Get recent transactions
    $stmt = $db->prepare("
        SELECT t.*, c.name as category_name, u.full_name as user_name
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_transactions = $stmt->fetchAll();
    
    // Get upcoming due dates
    $stmt = $db->prepare("
        SELECT *, DATEDIFF(due_date, CURDATE()) as days_until_due
        FROM debts_receivables 
        WHERE status != 'paid' AND due_date IS NOT NULL 
        AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY due_date ASC
        LIMIT 5
    ");
    $stmt->execute();
    $upcoming_dues = $stmt->fetchAll();
    
    // Get monthly chart data (last 6 months)
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month,
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
        FROM transactions 
        WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute();
    $chart_data = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    $monthly_stats = ['total_income' => 0, 'total_expense' => 0, 'income_count' => 0, 'expense_count' => 0];
    $debt_stats = ['total_debt' => 0, 'total_receivable' => 0, 'overdue_debt_count' => 0, 'overdue_receivable_count' => 0];
    $recent_transactions = [];
    $upcoming_dues = [];
    $chart_data = [];
}

// Include header
include '../../components/header.php';
?>

<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Selamat datang, <?php echo htmlspecialchars($current_user['full_name']); ?>!</h1>
                    <p class="text-muted mb-0">Berikut adalah ringkasan keuangan UMKM Anda untuk bulan <?php echo getMonthName(date('n')); ?> <?php echo date('Y'); ?></p>
                </div>
                <div class="text-end">
                    <small class="text-muted">Terakhir diperbarui: <?php echo formatDateTime(date('Y-m-d H:i:s')); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card income">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?php echo formatCurrency($total_income); ?></h3>
                        <p>Total Pemasukan</p>
                        <small><?php echo $monthly_stats['income_count']; ?> transaksi</small>
                    </div>
                    <div>
                        <i class="bi bi-arrow-up-circle" style="font-size: 3rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card expense">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?php echo formatCurrency($total_expense); ?></h3>
                        <p>Total Pengeluaran</p>
                        <small><?php echo $monthly_stats['expense_count']; ?> transaksi</small>
                    </div>
                    <div>
                        <i class="bi bi-arrow-down-circle" style="font-size: 3rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card profit">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?php echo formatCurrency($profit_loss); ?></h3>
                        <p><?php echo $profit_loss >= 0 ? 'Laba' : 'Rugi'; ?></p>
                        <small><?php echo $profit_loss >= 0 ? 'Keuntungan' : 'Kerugian'; ?> bulan ini</small>
                    </div>
                    <div>
                        <i class="bi bi-<?php echo $profit_loss >= 0 ? 'graph-up' : 'graph-down'; ?>" style="font-size: 3rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card debt">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?php echo formatCurrency($debt_stats['total_debt'] - $debt_stats['total_receivable']); ?></h3>
                        <p>Saldo Utang/Piutang</p>
                        <small>
                            <?php if ($debt_stats['overdue_debt_count'] > 0 || $debt_stats['overdue_receivable_count'] > 0): ?>
                                <span class="text-danger"><?php echo $debt_stats['overdue_debt_count'] + $debt_stats['overdue_receivable_count']; ?> jatuh tempo</span>
                            <?php else: ?>
                                Tidak ada yang jatuh tempo
                            <?php endif; ?>
                        </small>
                    </div>
                    <div>
                        <i class="bi bi-credit-card" style="font-size: 3rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Recent Activity -->
    <div class="row">
        <!-- Chart Section -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>
                        Tren Keuangan (6 Bulan Terakhir)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="financialChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i>
                        Aksi Cepat
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo APP_URL; ?>/pages/transactions/add.php?type=income" class="btn btn-success">
                            <i class="bi bi-plus-circle me-2"></i>Tambah Pemasukan
                        </a>
                        <a href="<?php echo APP_URL; ?>/pages/transactions/add.php?type=expense" class="btn btn-danger">
                            <i class="bi bi-dash-circle me-2"></i>Tambah Pengeluaran
                        </a>
                        <a href="<?php echo APP_URL; ?>/pages/debts/add.php" class="btn btn-warning">
                            <i class="bi bi-credit-card me-2"></i>Catat Utang/Piutang
                        </a>
                        <a href="<?php echo APP_URL; ?>/pages/reports/" class="btn btn-info">
                            <i class="bi bi-file-earmark-text me-2"></i>Lihat Laporan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions and Upcoming Dues -->
    <div class="row">
        <!-- Recent Transactions -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Transaksi Terbaru
                    </h5>
                    <a href="<?php echo APP_URL; ?>/pages/transactions/" class="btn btn-sm btn-outline-primary">
                        Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_transactions)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Kategori</th>
                                        <th>Deskripsi</th>
                                        <th>Jumlah</th>
                                        <th>Oleh</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_transactions as $transaction): ?>
                                        <tr class="transaction-<?php echo $transaction['type']; ?>">
                                            <td><?php echo formatDate($transaction['transaction_date']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                            <td>
                                                <span class="text-<?php echo $transaction['type'] === 'income' ? 'success' : 'danger'; ?>">
                                                    <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>
                                                    <?php echo formatCurrency($transaction['amount']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">Belum ada transaksi</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Dues -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-alarm me-2"></i>
                        Jatuh Tempo Minggu Ini
                    </h5>
                    <a href="<?php echo APP_URL; ?>/pages/debts/" class="btn btn-sm btn-outline-primary">
                        Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($upcoming_dues)): ?>
                        <?php foreach ($upcoming_dues as $due): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                                <div>
                                    <strong><?php echo htmlspecialchars($due['contact_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo ucfirst($due['type']); ?> - <?php echo formatCurrency($due['remaining_amount']); ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?php echo $due['days_until_due'] <= 1 ? 'danger' : 'warning'; ?>">
                                        <?php 
                                        if ($due['days_until_due'] == 0) {
                                            echo 'Hari ini';
                                        } elseif ($due['days_until_due'] == 1) {
                                            echo 'Besok';
                                        } else {
                                            echo $due['days_until_due'] . ' hari';
                                        }
                                        ?>
                                    </span>
                                    <br>
                                    <small class="text-muted"><?php echo formatDate($due['due_date']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">Tidak ada yang jatuh tempo</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chart data from PHP
const chartData = <?php echo json_encode($chart_data); ?>;

// Prepare chart data
const months = [];
const incomeData = [];
const expenseData = [];

chartData.forEach(function(item) {
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
    const monthIndex = parseInt(item.month.split('-')[1]) - 1;
    months.push(monthNames[monthIndex] + ' ' + item.month.split('-')[0]);
    incomeData.push(parseFloat(item.income) || 0);
    expenseData.push(parseFloat(item.expense) || 0);
});

// Create chart
const ctx = document.getElementById('financialChart').getContext('2d');
const financialChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Pemasukan',
            data: incomeData,
            borderColor: '#198754',
            backgroundColor: 'rgba(25, 135, 84, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Pengeluaran',
            data: expenseData,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                    }
                }
            }
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: 'Bulan'
                }
            },
            y: {
                display: true,
                title: {
                    display: true,
                    text: 'Jumlah (Rp)'
                },
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        },
        interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
        }
    }
});
</script>

<?php include '../../components/footer.php'; ?>

