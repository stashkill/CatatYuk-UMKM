<?php
/**
 * Notifications - CatatYuk
 */

// Include configuration and functions
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Require login
requireLogin();

$page_title = 'Notifikasi';
$current_user = getCurrentUser();

// Get filter parameters
$filter_type = $_GET['type'] ?? '';
$filter_read = $_GET['read'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $db = getDB();
    
    // Build WHERE clause
    $where_conditions = ["(user_id = ? OR user_id IS NULL)"];
    $params = [$current_user['id']];
    
    if (!empty($filter_type)) {
        $where_conditions[] = "type = ?";
        $params[] = $filter_type;
    }
    
    if ($filter_read !== '') {
        $where_conditions[] = "is_read = ?";
        $params[] = $filter_read === '1' ? 1 : 0;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM notifications $where_clause";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $limit);
    
    // Get notifications
    $sql = "
        SELECT n.*, 
               CASE 
                   WHEN n.type = 'debt_reminder' THEN dr.contact_name
                   WHEN n.type = 'receivable_reminder' THEN dr.contact_name
                   ELSE NULL
               END as related_contact
        FROM notifications n
        LEFT JOIN debts_receivables dr ON n.related_id = dr.id AND n.type IN ('debt_reminder', 'receivable_reminder')
        $where_clause
        ORDER BY n.is_read ASC, n.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll();
    
    // Get summary statistics
    $summary_sql = "
        SELECT 
            COUNT(*) as total_count,
            COUNT(CASE WHEN is_read = 0 THEN 1 END) as unread_count,
            COUNT(CASE WHEN type = 'debt_reminder' THEN 1 END) as debt_reminder_count,
            COUNT(CASE WHEN type = 'receivable_reminder' THEN 1 END) as receivable_reminder_count,
            COUNT(CASE WHEN type = 'general' THEN 1 END) as general_count
        FROM notifications 
        WHERE user_id = ? OR user_id IS NULL
    ";
    $stmt = $db->prepare($summary_sql);
    $stmt->execute([$current_user['id']]);
    $summary = $stmt->fetch();
    
} catch (Exception $e) {
    error_log('Notifications error: ' . $e->getMessage());
    $notifications = [];
    $total_records = 0;
    $total_pages = 0;
    $summary = ['total_count' => 0, 'unread_count' => 0, 'debt_reminder_count' => 0, 'receivable_reminder_count' => 0, 'general_count' => 0];
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
                    <h1 class="h3 mb-1">Notifikasi</h1>
                    <p class="text-muted mb-0">Kelola semua notifikasi dan pengingat</p>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary" onclick="markAllAsRead()">
                        <i class="bi bi-check-all me-2"></i>Tandai Semua Dibaca
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="clearAllRead()">
                        <i class="bi bi-trash me-2"></i>Hapus yang Sudah Dibaca
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $summary['total_count']; ?></h4>
                            <p class="mb-0">Total Notifikasi</p>
                        </div>
                        <div>
                            <i class="bi bi-bell" style="font-size: 3rem; opacity: 0.7;"></i>
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
                            <h4><?php echo $summary['unread_count']; ?></h4>
                            <p class="mb-0">Belum Dibaca</p>
                        </div>
                        <div>
                            <i class="bi bi-bell-fill" style="font-size: 3rem; opacity: 0.7;"></i>
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
                            <h4><?php echo $summary['debt_reminder_count']; ?></h4>
                            <p class="mb-0">Pengingat Utang</p>
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
                            <h4><?php echo $summary['receivable_reminder_count']; ?></h4>
                            <p class="mb-0">Pengingat Piutang</p>
                        </div>
                        <div>
                            <i class="bi bi-cash-stack" style="font-size: 3rem; opacity: 0.7;"></i>
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
                <i class="bi bi-funnel me-2"></i>Filter Notifikasi
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="type" class="form-label">Jenis Notifikasi</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">Semua Jenis</option>
                        <option value="debt_reminder" <?php echo $filter_type === 'debt_reminder' ? 'selected' : ''; ?>>Pengingat Utang</option>
                        <option value="receivable_reminder" <?php echo $filter_type === 'receivable_reminder' ? 'selected' : ''; ?>>Pengingat Piutang</option>
                        <option value="general" <?php echo $filter_type === 'general' ? 'selected' : ''; ?>>Umum</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="read" class="form-label">Status Baca</label>
                    <select class="form-select" id="read" name="read">
                        <option value="">Semua Status</option>
                        <option value="0" <?php echo $filter_read === '0' ? 'selected' : ''; ?>>Belum Dibaca</option>
                        <option value="1" <?php echo $filter_read === '1' ? 'selected' : ''; ?>>Sudah Dibaca</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-2"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if (!empty($filter_type) || $filter_read !== ''): ?>
                <div class="mt-3">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i>Reset Filter
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Daftar Notifikasi (<?php echo number_format($total_records); ?> notifikasi)
            </h5>
        </div>
        <div class="card-body">
            <?php if (!empty($notifications)): ?>
                <div class="list-group">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="list-group-item notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" 
                             data-id="<?php echo $notification['id']; ?>">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php
                                        $icon_class = [
                                            'debt_reminder' => 'bi-credit-card text-danger',
                                            'receivable_reminder' => 'bi-cash-stack text-success',
                                            'general' => 'bi-info-circle text-info'
                                        ];
                                        $type_text = [
                                            'debt_reminder' => 'Pengingat Utang',
                                            'receivable_reminder' => 'Pengingat Piutang',
                                            'general' => 'Notifikasi Umum'
                                        ];
                                        ?>
                                        <i class="bi <?php echo $icon_class[$notification['type']] ?? 'bi-bell'; ?> me-2"></i>
                                        <h6 class="mb-0 me-2"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                        <span class="badge bg-secondary"><?php echo $type_text[$notification['type']] ?? 'Lainnya'; ?></span>
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="badge bg-warning ms-2">Baru</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <?php if (!empty($notification['related_contact'])): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-person me-1"></i>
                                            Kontak: <?php echo htmlspecialchars($notification['related_contact']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">
                                        <?php echo formatDateTime($notification['created_at']); ?>
                                    </small>
                                    <?php if ($notification['scheduled_date']): ?>
                                        <small class="text-muted d-block">
                                            Dijadwalkan: <?php echo formatDate($notification['scheduled_date']); ?>
                                        </small>
                                    <?php endif; ?>
                                    <div class="btn-group btn-group-sm mt-2">
                                        <?php if (!$notification['is_read']): ?>
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="markAsRead(<?php echo $notification['id']; ?>)" title="Tandai Dibaca">
                                                <i class="bi bi-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($notification['related_id'] && in_array($notification['type'], ['debt_reminder', 'receivable_reminder'])): ?>
                                            <a href="../debts/index.php?search=<?php echo urlencode($notification['related_contact']); ?>" 
                                               class="btn btn-outline-info" title="Lihat Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteNotification(<?php echo $notification['id']; ?>)" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
                    <i class="bi bi-bell-slash text-muted" style="font-size: 4rem;"></i>
                    <h4 class="text-muted mt-3">Tidak ada notifikasi</h4>
                    <p class="text-muted">Belum ada notifikasi yang sesuai dengan filter yang dipilih</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function markAsRead(notificationId) {
    $.ajax({
        url: '../../api/notifications.php',
        method: 'POST',
        data: {
            action: 'mark_read',
            id: notificationId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update UI
                const item = $(`[data-id="${notificationId}"]`);
                item.removeClass('unread');
                item.find('.badge:contains("Baru")').remove();
                item.find('.btn-outline-primary').remove();
                
                showToast('Notifikasi ditandai sebagai dibaca', 'success');
                
                // Update notification count in header
                loadNotifications();
            } else {
                showToast(response.message || 'Gagal menandai notifikasi', 'danger');
            }
        },
        error: function() {
            showToast('Terjadi kesalahan sistem', 'danger');
        }
    });
}

function markAllAsRead() {
    if (confirm('Apakah Anda yakin ingin menandai semua notifikasi sebagai dibaca?')) {
        $.ajax({
            url: '../../api/notifications.php',
            method: 'POST',
            data: {
                action: 'mark_all_read'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('Semua notifikasi ditandai sebagai dibaca', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(response.message || 'Gagal menandai notifikasi', 'danger');
                }
            },
            error: function() {
                showToast('Terjadi kesalahan sistem', 'danger');
            }
        });
    }
}

function deleteNotification(notificationId) {
    if (confirm('Apakah Anda yakin ingin menghapus notifikasi ini?')) {
        $.ajax({
            url: 'delete.php',
            method: 'POST',
            data: { id: notificationId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $(`[data-id="${notificationId}"]`).fadeOut(() => {
                        $(this).remove();
                    });
                    showToast('Notifikasi berhasil dihapus', 'success');
                    
                    // Update notification count in header
                    loadNotifications();
                } else {
                    showToast(response.message || 'Gagal menghapus notifikasi', 'danger');
                }
            },
            error: function() {
                showToast('Terjadi kesalahan sistem', 'danger');
            }
        });
    }
}

function clearAllRead() {
    if (confirm('Apakah Anda yakin ingin menghapus semua notifikasi yang sudah dibaca?')) {
        $.ajax({
            url: 'clear_read.php',
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('Notifikasi yang sudah dibaca berhasil dihapus', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(response.message || 'Gagal menghapus notifikasi', 'danger');
                }
            },
            error: function() {
                showToast('Terjadi kesalahan sistem', 'danger');
            }
        });
    }
}

// Auto-submit form when filter changes
$('#type, #read').change(function() {
    $(this).closest('form').submit();
});

// Click notification to mark as read
$('.notification-item.unread').click(function(e) {
    if (!$(e.target).closest('.btn-group').length) {
        const notificationId = $(this).data('id');
        markAsRead(notificationId);
    }
});
</script>

<?php include '../../components/footer.php'; ?>

