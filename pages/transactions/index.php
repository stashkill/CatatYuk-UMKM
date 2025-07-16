<?php
/**
 * Transactions List - CatatYuk
 */

// Include configuration and functions
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Require login
requireLogin();

$page_title = 'Daftar Transaksi';
$current_user = getCurrentUser();

// Get filter parameters
$filter_type = $_GET['type'] ?? '';
$filter_category = $_GET['category'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $db = getDB();
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    
    if (!empty($filter_type)) {
        $where_conditions[] = "t.type = ?";
        $params[] = $filter_type;
    }
    
    if (!empty($filter_category)) {
        $where_conditions[] = "t.category_id = ?";
        $params[] = $filter_category;
    }
    
    if (!empty($filter_date_from)) {
        $where_conditions[] = "t.transaction_date >= ?";
        $params[] = $filter_date_from;
    }
    
    if (!empty($filter_date_to)) {
        $where_conditions[] = "t.transaction_date <= ?";
        $params[] = $filter_date_to;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(t.description LIKE ? OR t.reference_number LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get total count
    $count_sql = "
        SELECT COUNT(*) as total
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN users u ON t.user_id = u.id
        $where_clause
    ";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $limit);
    
    // Get transactions
    $sql = "
        SELECT t.*, c.name as category_name, u.full_name as user_name
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN users u ON t.user_id = u.id
        $where_clause
        ORDER BY t.transaction_date DESC, t.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Get categories for filter
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    // Calculate totals for current filter
    $totals_sql = "
        SELECT 
            SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as total_income,
            SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as total_expense
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN users u ON t.user_id = u.id
        $where_clause
    ";
    $stmt = $db->prepare($totals_sql);
    $stmt->execute($params);
    $totals = $stmt->fetch();
    
} catch (Exception $e) {
    error_log('Transactions list error: ' . $e->getMessage());
    $transactions = [];
    $categories = [];
    $total_records = 0;
    $total_pages = 0;
    $totals = ['total_income' => 0, 'total_expense' => 0];
}

// Include header
include '../../components/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Daftar Transaksi</h1>
                    <p class="text-muted mb-0">Kelola semua transaksi keuangan UMKM Anda</p>
                </div>
                <div>
                    <a href="add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Transaksi
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo formatCurrency($totals['total_income']); ?></h4>
                            <p class="mb-0">Total Pemasukan</p>
                        </div>
                        <div>
                            <i class="bi bi-arrow-down-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo formatCurrency($totals['total_expense']); ?></h4>
                            <p class="mb-0">Total Pengeluaran</p>
                        </div>
                        <div>
                            <i class="bi bi-arrow-up-circle" style="font-size: 2.5rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo formatCurrency($totals['total_income'] - $totals['total_expense']); ?></h4>
                            <p class="mb-0">Selisih</p>
                        </div>
                        <div>
                            <i class="bi bi-calculator" style="font-size: 2.5rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bi bi-funnel me-2"></i>Filter & Pencarian
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-2">
                    <label for="type" class="form-label">Jenis</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">Semua</option>
                        <option value="income" <?php echo $filter_type === 'income' ? 'selected' : ''; ?>>Pemasukan</option>
                        <option value="expense" <?php echo $filter_type === 'expense' ? 'selected' : ''; ?>>Pengeluaran</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Kategori</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $filter_category == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($filter_date_from); ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($filter_date_to); ?>">
                </div>
                <div class="col-md-2">
                    <label for="search" class="form-label">Pencarian</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Deskripsi/Referensi" value="<?php echo htmlspecialchars($search); ?>">
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
            
            <?php if (!empty($filter_type) || !empty($filter_category) || !empty($filter_date_from) || !empty($filter_date_to) || !empty($search)): ?>
                <div class="mt-3">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i>Reset Filter
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Transaksi (<?php echo number_format($total_records); ?> data)
            </h5>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-primary" onclick="exportData()">
                    <i class="bi bi-download me-1"></i>Export
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="printTable()">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($transactions)): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th>Referensi</th>
                                <th>Jumlah</th>
                                <th>Oleh</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr class="transaction-<?php echo $transaction['type']; ?>">
                                    <td><?php echo formatDate($transaction['transaction_date']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $transaction['type'] === 'income' ? 'success' : 'danger'; ?>">
                                            <?php echo $transaction['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['reference_number'] ?? '-'); ?></td>
                                    <td>
                                        <strong class="text-<?php echo $transaction['type'] === 'income' ? 'success' : 'danger'; ?>">
                                            <?php echo formatCurrency($transaction['amount']); ?>
                                        </strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit.php?id=<?php echo $transaction['id']; ?>" 
                                               class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger btn-delete" 
                                                    onclick="deleteTransaction(<?php echo $transaction['id']; ?>)" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                    <h4 class="text-muted mt-3">Tidak ada transaksi</h4>
                    <p class="text-muted">Belum ada transaksi yang sesuai dengan filter yang dipilih</p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Transaksi Pertama
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteTransaction(id) {
    if (confirm('Apakah Anda yakin ingin menghapus transaksi ini?')) {
        showLoading();
        
        $.ajax({
            url: 'delete.php',
            method: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showToast('Transaksi berhasil dihapus', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(response.message || 'Gagal menghapus transaksi', 'danger');
                }
            },
            error: function() {
                hideLoading();
                showToast('Terjadi kesalahan sistem', 'danger');
            }
        });
    }
}

function exportData() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.open('?' + params.toString(), '_blank');
}

function printTable() {
    window.print();
}

// Auto-submit form when filter changes
$('#type, #category').change(function() {
    $(this).closest('form').submit();
});
</script>

<?php include '../../components/footer.php'; ?>

