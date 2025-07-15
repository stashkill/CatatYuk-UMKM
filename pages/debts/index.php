<?php
/**
 * Debts & Receivables List - CatatYuk
 */

// Include configuration and functions
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Require login
requireLogin();

$page_title = 'Utang & Piutang';
$current_user = getCurrentUser();

// Get filter parameters
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';
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
        $where_conditions[] = "type = ?";
        $params[] = $filter_type;
    }
    
    if (!empty($filter_status)) {
        $where_conditions[] = "status = ?";
        $params[] = $filter_status;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(contact_name LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Update overdue status
    $db->exec("
        UPDATE debts_receivables 
        SET status = 'overdue' 
        WHERE status IN ('pending', 'partial') 
        AND due_date IS NOT NULL 
        AND due_date < CURDATE()
    ");
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM debts_receivables $where_clause";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $limit);
    
    // Get debts and receivables
    $sql = "
        SELECT *, 
               DATEDIFF(due_date, CURDATE()) as days_until_due,
               (amount - remaining_amount) as paid_amount
        FROM debts_receivables 
        $where_clause
        ORDER BY 
            CASE 
                WHEN status = 'overdue' THEN 1
                WHEN status = 'pending' AND due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 2
                WHEN status = 'partial' THEN 3
                ELSE 4
            END,
            due_date ASC,
            created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $debts_receivables = $stmt->fetchAll();
    
    // Get summary statistics
    $summary_sql = "
        SELECT 
            type,
            COUNT(*) as count,
            SUM(amount) as total_amount,
            SUM(remaining_amount) as total_remaining,
            COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_count,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'partial' THEN 1 END) as partial_count,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count
        FROM debts_receivables 
        GROUP BY type
    ";
    $stmt = $db->prepare($summary_sql);
    $stmt->execute();
    $summary_data = $stmt->fetchAll();
    
    // Organize summary data
    $summary = [
        'debt' => ['count' => 0, 'total_amount' => 0, 'total_remaining' => 0, 'overdue_count' => 0, 'pending_count' => 0, 'partial_count' => 0, 'paid_count' => 0],
        'receivable' => ['count' => 0, 'total_amount' => 0, 'total_remaining' => 0, 'overdue_count' => 0, 'pending_count' => 0, 'partial_count' => 0, 'paid_count' => 0]
    ];
    
    foreach ($summary_data as $data) {
        $summary[$data['type']] = $data;
    }
    
} catch (Exception $e) {
    error_log('Debts list error: ' . $e->getMessage());
    $debts_receivables = [];
    $total_records = 0;
    $total_pages = 0;
    $summary = [
        'debt' => ['count' => 0, 'total_amount' => 0, 'total_remaining' => 0, 'overdue_count' => 0, 'pending_count' => 0, 'partial_count' => 0, 'paid_count' => 0],
        'receivable' => ['count' => 0, 'total_amount' => 0, 'total_remaining' => 0, 'overdue_count' => 0, 'pending_count' => 0, 'partial_count' => 0, 'paid_count' => 0]
    ];
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
                    <h1 class="h3 mb-1">Utang & Piutang</h1>
                    <p class="text-muted mb-0">Kelola utang dan piutang UMKM Anda</p>
                </div>
                <div>
                    <a href="add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Utang/Piutang
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo formatCurrency($summary['debt']['total_remaining']); ?></h4>
                            <p class="mb-0">Total Utang</p>
                            <small><?php echo $summary['debt']['count']; ?> item</small>
                        </div>
                        <div>
                            <i class="bi bi-credit-card" style="font-size: 3rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo formatCurrency($summary['receivable']['total_remaining']); ?></h4>
                            <p class="mb-0">Total Piutang</p>
                            <small><?php echo $summary['receivable']['count']; ?> item</small>
                        </div>
                        <div>
                            <i class="bi bi-cash-stack" style="font-size: 3rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo formatCurrency($summary['receivable']['total_remaining'] - $summary['debt']['total_remaining']); ?></h4>
                            <p class="mb-0">Saldo Bersih</p>
                            <small><?php echo $summary['receivable']['total_remaining'] >= $summary['debt']['total_remaining'] ? 'Surplus' : 'Defisit'; ?></small>
                        </div>
                        <div>
                            <i class="bi bi-calculator" style="font-size: 3rem; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $summary['debt']['overdue_count'] + $summary['receivable']['overdue_count']; ?></h4>
                            <p class="mb-0">Jatuh Tempo</p>
                            <small>Perlu perhatian segera</small>
                        </div>
                        <div>
                            <i class="bi bi-exclamation-triangle" style="font-size: 3rem; opacity: 0.7;"></i>
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
                <div class="col-md-3">
                    <label for="type" class="form-label">Jenis</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">Semua</option>
                        <option value="debt" <?php echo $filter_type === 'debt' ? 'selected' : ''; ?>>Utang</option>
                        <option value="receivable" <?php echo $filter_type === 'receivable' ? 'selected' : ''; ?>>Piutang</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Belum Dibayar</option>
                        <option value="partial" <?php echo $filter_status === 'partial' ? 'selected' : ''; ?>>Dibayar Sebagian</option>
                        <option value="paid" <?php echo $filter_status === 'paid' ? 'selected' : ''; ?>>Lunas</option>
                        <option value="overdue" <?php echo $filter_status === 'overdue' ? 'selected' : ''; ?>>Jatuh Tempo</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Pencarian</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Nama kontak atau deskripsi" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Cari
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if (!empty($filter_type) || !empty($filter_status) || !empty($search)): ?>
                <div class="mt-3">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i>Reset Filter
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Debts & Receivables Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Daftar Utang & Piutang (<?php echo number_format($total_records); ?> data)
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
            <?php if (!empty($debts_receivables)): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="debtsTable">
                        <thead>
                            <tr>
                                <th>Jenis</th>
                                <th>Kontak</th>
                                <th>Deskripsi</th>
                                <th>Jumlah Total</th>
                                <th>Sisa</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($debts_receivables as $item): ?>
                                <tr class="<?php echo $item['status'] === 'overdue' ? 'table-danger' : ($item['days_until_due'] <= 3 && $item['days_until_due'] >= 0 ? 'table-warning' : ''); ?>">
                                    <td>
                                        <span class="badge bg-<?php echo $item['type'] === 'debt' ? 'danger' : 'success'; ?>">
                                            <?php echo $item['type'] === 'debt' ? 'Utang' : 'Piutang'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['contact_name']); ?></strong>
                                        <?php if (!empty($item['contact_phone'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($item['contact_phone']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td><?php echo formatCurrency($item['amount']); ?></td>
                                    <td>
                                        <strong class="text-<?php echo $item['type'] === 'debt' ? 'danger' : 'success'; ?>">
                                            <?php echo formatCurrency($item['remaining_amount']); ?>
                                        </strong>
                                        <?php if ($item['paid_amount'] > 0): ?>
                                            <br><small class="text-muted">Dibayar: <?php echo formatCurrency($item['paid_amount']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($item['due_date']): ?>
                                            <?php echo formatDate($item['due_date']); ?>
                                            <?php if ($item['days_until_due'] !== null): ?>
                                                <br>
                                                <small class="text-<?php echo $item['days_until_due'] < 0 ? 'danger' : ($item['days_until_due'] <= 3 ? 'warning' : 'muted'); ?>">
                                                    <?php 
                                                    if ($item['days_until_due'] < 0) {
                                                        echo abs($item['days_until_due']) . ' hari terlambat';
                                                    } elseif ($item['days_until_due'] == 0) {
                                                        echo 'Jatuh tempo hari ini';
                                                    } elseif ($item['days_until_due'] <= 7) {
                                                        echo $item['days_until_due'] . ' hari lagi';
                                                    }
                                                    ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'pending' => 'warning',
                                            'partial' => 'info',
                                            'paid' => 'success',
                                            'overdue' => 'danger'
                                        ];
                                        $status_text = [
                                            'pending' => 'Belum Dibayar',
                                            'partial' => 'Sebagian',
                                            'paid' => 'Lunas',
                                            'overdue' => 'Jatuh Tempo'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $status_class[$item['status']]; ?>">
                                            <?php echo $status_text[$item['status']]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($item['status'] !== 'paid'): ?>
                                                <button type="button" class="btn btn-outline-success" 
                                                        onclick="addPayment(<?php echo $item['id']; ?>)" title="Bayar">
                                                    <i class="bi bi-cash"></i>
                                                </button>
                                            <?php endif; ?>
                                            <a href="edit.php?id=<?php echo $item['id']; ?>" 
                                               class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="deleteDebt(<?php echo $item['id']; ?>)" title="Hapus">
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
                    <i class="bi bi-credit-card text-muted" style="font-size: 4rem;"></i>
                    <h4 class="text-muted mt-3">Tidak ada data utang/piutang</h4>
                    <p class="text-muted">Belum ada data yang sesuai dengan filter yang dipilih</p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Utang/Piutang Pertama
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="paymentForm">
                <div class="modal-body">
                    <input type="hidden" id="debt_id" name="debt_id">
                    <div class="mb-3">
                        <label for="payment_amount" class="form-label">Jumlah Pembayaran</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control currency-input" id="payment_amount" name="payment_amount" required>
                        </div>
                        <div class="form-text">Sisa yang harus dibayar: <span id="remaining_amount"></span></div>
                    </div>
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Tanggal Pembayaran</label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_notes" class="form-label">Catatan</label>
                        <textarea class="form-control" id="payment_notes" name="payment_notes" rows="3" placeholder="Catatan pembayaran (opsional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addPayment(debtId) {
    // Get debt details via AJAX
    $.ajax({
        url: 'get_debt.php',
        method: 'GET',
        data: { id: debtId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const debt = response.data;
                $('#debt_id').val(debtId);
                $('#remaining_amount').text(formatCurrency(debt.remaining_amount));
                $('#payment_amount').val('');
                $('#payment_notes').val('');
                $('#paymentModal').modal('show');
            } else {
                showToast(response.message || 'Gagal memuat data', 'danger');
            }
        },
        error: function() {
            showToast('Terjadi kesalahan sistem', 'danger');
        }
    });
}

function deleteDebt(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data utang/piutang ini?')) {
        showLoading();
        
        $.ajax({
            url: 'delete.php',
            method: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showToast('Data berhasil dihapus', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(response.message || 'Gagal menghapus data', 'danger');
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

// Payment form submission
$('#paymentForm').on('submit', function(e) {
    e.preventDefault();
    
    const amount = getCurrencyValue($('#payment_amount').val());
    if (amount <= 0) {
        showToast('Jumlah pembayaran harus lebih dari 0', 'danger');
        return;
    }
    
    showLoading();
    
    $.ajax({
        url: 'add_payment.php',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            hideLoading();
            if (response.success) {
                $('#paymentModal').modal('hide');
                showToast('Pembayaran berhasil dicatat', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(response.message || 'Gagal menyimpan pembayaran', 'danger');
            }
        },
        error: function() {
            hideLoading();
            showToast('Terjadi kesalahan sistem', 'danger');
        }
    });
});

// Format currency in payment modal
$('#payment_amount').on('input', function() {
    formatCurrencyInput(this);
});

// Auto-submit form when filter changes
$('#type, #status').change(function() {
    $(this).closest('form').submit();
});

function formatCurrency(amount) {
    return 'Rp ' + parseFloat(amount).toLocaleString('id-ID');
}
</script>

<?php include '../../components/footer.php'; ?>

