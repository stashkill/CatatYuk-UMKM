<?php
/**
 * Reports - CatatYuk
 */

// Include configuration and functions
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Require login
requireLogin();

$page_title = 'Laporan Keuangan';
$current_user = getCurrentUser();

// Get filter parameters
$report_type = $_GET['type'] ?? 'monthly';
$year = intval($_GET['year'] ?? date('Y'));
$month = intval($_GET['month'] ?? date('n'));
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Set date range based on report type
switch ($report_type) {
    case 'monthly':
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        $report_title = 'Laporan Bulanan ' . getMonthName($month) . ' ' . $year;
        break;
    case 'yearly':
        $start_date = $year . '-01-01';
        $end_date = $year . '-12-31';
        $report_title = 'Laporan Tahunan ' . $year;
        break;
    case 'custom':
        if (empty($start_date)) $start_date = date('Y-m-01');
        if (empty($end_date)) $end_date = date('Y-m-d');
        $report_title = 'Laporan Custom ' . formatDate($start_date) . ' - ' . formatDate($end_date);
        break;
    default:
        $report_type = 'monthly';
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        $report_title = 'Laporan Bulanan ' . getMonthName($month) . ' ' . $year;
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
    
    // Get daily trends (for chart)
    $stmt = $db->prepare("
        SELECT 
            transaction_date,
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as daily_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as daily_expense
        FROM transactions 
        WHERE transaction_date BETWEEN ? AND ?
        GROUP BY transaction_date
        ORDER BY transaction_date ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    $daily_trends = $stmt->fetchAll();
    
    // Get top transactions
    $stmt = $db->prepare("
        SELECT t.*, c.name as category_name
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.transaction_date BETWEEN ? AND ?
        ORDER BY t.amount DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $top_transactions = $stmt->fetchAll();
    
    // Get debt/receivable summary
    $stmt = $db->prepare("
        SELECT 
            type,
            COUNT(*) as count,
            SUM(amount) as total_amount,
            SUM(remaining_amount) as remaining_amount,
            COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_count
        FROM debts_receivables
        GROUP BY type
    ");
    $stmt->execute();
    $debt_summary = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log('Reports error: ' . $e->getMessage());
    $summary = ['total_income' => 0, 'total_expense' => 0, 'income_count' => 0, 'expense_count' => 0, 'avg_income' => 0, 'avg_expense' => 0];
    $categories = [];
    $daily_trends = [];
    $top_transactions = [];
    $debt_summary = [];
}

// Calculate additional metrics
$profit_loss = $summary['total_income'] - $summary['total_expense'];
$profit_margin = $summary['total_income'] > 0 ? ($profit_loss / $summary['total_income']) * 100 : 0;

// Prepare chart data
$chart_labels = [];
$chart_income = [];
$chart_expense = [];

foreach ($daily_trends as $trend) {
    $chart_labels[] = formatDate($trend['transaction_date'], 'd/m');
    $chart_income[] = floatval($trend['daily_income']);
    $chart_expense[] = floatval($trend['daily_expense']);
}

// Prepare category chart data
$income_categories = array_filter($categories, function($cat) { return $cat['category_type'] === 'income'; });
$expense_categories = array_filter($categories, function($cat) { return $cat['category_type'] === 'expense'; });

// Include header
include '../../components/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Laporan Keuangan</h1>
                    <p class="text-muted mb-0"><?php echo $report_title; ?></p>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary" onclick="exportReport()">
                        <i class="bi bi-download me-2"></i>Export PDF
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="printReport()">
                        <i class="bi bi-printer me-2"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bi bi-funnel me-2"></i>Filter Laporan
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label">Jenis Laporan</label>
                    <select class="form-select" id="type" name="type" onchange="toggleDateFields()">
                        <option value="monthly" <?php echo $report_type === 'monthly' ? 'selected' : ''; ?>>Bulanan</option>
                        <option value="yearly" <?php echo $report_type === 'yearly' ? 'selected' : ''; ?>>Tahunan</option>
                        <option value="custom" <?php echo $report_type === 'custom' ? 'selected' : ''; ?>>Custom</option>
                    </select>
                </div>
                
                <div class="col-md-2" id="year_field">
                    <label for="year" class="form-label">Tahun</label>
                    <select class="form-select" id="year" name="year">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $year === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-2" id="month_field" style="display: <?php echo $report_type === 'monthly' ? 'block' : 'none'; ?>;">
                    <label for="month" class="form-label">Bulan</label>
                    <select class="form-select" id="month" name="month">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $month === $m ? 'selected' : ''; ?>><?php echo getMonthName($m); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-2" id="start_date_field" style="display: <?php echo $report_type === 'custom' ? 'block' : 'none'; ?>;">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                
                <div class="col-md-2" id="end_date_field" style="display: <?php echo $report_type === 'custom' ? 'block' : 'none'; ?>;">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo formatCurrency($summary['total_income']); ?></h4>
                            <p class="mb-0">Total Pemasukan</p>
                            <small><?php echo $summary['income_count']; ?> transaksi</small>
                        </div>
                        <div>
                            <i class="bi bi-arrow-up-circle" style="font-size: 3rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo formatCurrency($summary['total_expense']); ?></h4>
                            <p class="mb-0">Total Pengeluaran</p>
                            <small><?php echo $summary['expense_count']; ?> transaksi</small>
                        </div>
                        <div>
                            <i class="bi bi-arrow-down-circle" style="font-size: 3rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-<?php echo $profit_loss >= 0 ? 'info' : 'warning'; ?> text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo formatCurrency($profit_loss); ?></h4>
                            <p class="mb-0"><?php echo $profit_loss >= 0 ? 'Laba' : 'Rugi'; ?></p>
                            <small>Margin: <?php echo number_format($profit_margin, 1); ?>%</small>
                        </div>
                        <div>
                            <i class="bi bi-<?php echo $profit_loss >= 0 ? 'graph-up' : 'graph-down'; ?>" style="font-size: 3rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $summary['income_count'] + $summary['expense_count']; ?></h4>
                            <p class="mb-0">Total Transaksi</p>
                            <small>Rata-rata: <?php echo formatCurrency(($summary['avg_income'] + $summary['avg_expense']) / 2); ?></small>
                        </div>
                        <div>
                            <i class="bi bi-list-ul" style="font-size: 3rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Daily Trends Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>Tren Harian
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="dailyTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Breakdown -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pie-chart me-2"></i>Breakdown Kategori
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Analysis -->
    <div class="row">
        <!-- Category Details -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-check me-2"></i>Detail Kategori
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($categories)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Kategori</th>
                                        <th>Jenis</th>
                                        <th>Jumlah</th>
                                        <th>Transaksi</th>
                                        <th>Rata-rata</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $category['category_type'] === 'income' ? 'success' : 'danger'; ?>">
                                                    <?php echo $category['category_type'] === 'income' ? 'Masuk' : 'Keluar'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatCurrency($category['total_amount']); ?></td>
                                            <td><?php echo $category['transaction_count']; ?></td>
                                            <td><?php echo formatCurrency($category['avg_amount']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">Tidak ada data kategori</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Transactions -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-star me-2"></i>Transaksi Terbesar
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($top_transactions)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Deskripsi</th>
                                        <th>Kategori</th>
                                        <th>Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_transactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo formatDate($transaction['transaction_date']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['category_name']); ?></td>
                                            <td>
                                                <span class="text-<?php echo $transaction['type'] === 'income' ? 'success' : 'danger'; ?>">
                                                    <?php echo formatCurrency($transaction['amount']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">Tidak ada data transaksi</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chart data from PHP
const dailyLabels = <?php echo json_encode($chart_labels); ?>;
const dailyIncome = <?php echo json_encode($chart_income); ?>;
const dailyExpense = <?php echo json_encode($chart_expense); ?>;

// Daily trends chart
const dailyCtx = document.getElementById('dailyTrendsChart').getContext('2d');
const dailyTrendsChart = new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: dailyLabels,
        datasets: [{
            label: 'Pemasukan',
            data: dailyIncome,
            borderColor: '#198754',
            backgroundColor: 'rgba(25, 135, 84, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Pengeluaran',
            data: dailyExpense,
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
                    text: 'Tanggal'
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
        }
    }
});

// Category breakdown chart
const categoryLabels = [];
const categoryData = [];
const categoryColors = [];

<?php foreach ($expense_categories as $category): ?>
    categoryLabels.push('<?php echo addslashes($category['category_name']); ?>');
    categoryData.push(<?php echo $category['total_amount']; ?>);
    categoryColors.push('#' + Math.floor(Math.random()*16777215).toString(16));
<?php endforeach; ?>

const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryChart = new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: categoryLabels,
        datasets: [{
            data: categoryData,
            backgroundColor: categoryColors,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': Rp ' + context.parsed.toLocaleString('id-ID') + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

function toggleDateFields() {
    const type = document.getElementById('type').value;
    const yearField = document.getElementById('year_field');
    const monthField = document.getElementById('month_field');
    const startDateField = document.getElementById('start_date_field');
    const endDateField = document.getElementById('end_date_field');
    
    if (type === 'monthly') {
        yearField.style.display = 'block';
        monthField.style.display = 'block';
        startDateField.style.display = 'none';
        endDateField.style.display = 'none';
    } else if (type === 'yearly') {
        yearField.style.display = 'block';
        monthField.style.display = 'none';
        startDateField.style.display = 'none';
        endDateField.style.display = 'none';
    } else if (type === 'custom') {
        yearField.style.display = 'none';
        monthField.style.display = 'none';
        startDateField.style.display = 'block';
        endDateField.style.display = 'block';
    }
}

function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'pdf');
    window.open('export.php?' + params.toString(), '_blank');
}

function printReport() {
    window.print();
}
</script>

<?php include '../../components/footer.php'; ?>

