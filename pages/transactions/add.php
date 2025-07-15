<?php
/**
 * Add Transaction - CatatYuk
 */

// Include configuration and functions
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Require login
requireLogin();

$page_title = 'Tambah Transaksi';
$current_user = getCurrentUser();

// Get transaction type from URL parameter
$default_type = $_GET['type'] ?? 'income';
if (!in_array($default_type, ['income', 'expense'])) {
    $default_type = 'income';
}

$errors = [];
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = sanitizeInput($_POST['type'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $amount = getCurrencyValue($_POST['amount'] ?? '0');
    $description = sanitizeInput($_POST['description'] ?? '');
    $transaction_date = $_POST['transaction_date'] ?? '';
    $reference_number = sanitizeInput($_POST['reference_number'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    // Validation
    if (empty($type) || !in_array($type, ['income', 'expense'])) {
        $errors[] = 'Jenis transaksi harus dipilih';
    }
    
    if ($category_id <= 0) {
        $errors[] = 'Kategori harus dipilih';
    }
    
    if ($amount <= 0) {
        $errors[] = 'Jumlah harus lebih dari 0';
    }
    
    if (empty($description)) {
        $errors[] = 'Deskripsi harus diisi';
    }
    
    if (empty($transaction_date)) {
        $errors[] = 'Tanggal transaksi harus diisi';
    } elseif (strtotime($transaction_date) > time()) {
        $errors[] = 'Tanggal transaksi tidak boleh di masa depan';
    }
    
    // Validate category type matches transaction type
    if ($category_id > 0) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT type FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $category = $stmt->fetch();
            
            if (!$category) {
                $errors[] = 'Kategori tidak valid';
            } elseif ($category['type'] !== $type) {
                $errors[] = 'Kategori tidak sesuai dengan jenis transaksi';
            }
        } catch (Exception $e) {
            $errors[] = 'Terjadi kesalahan validasi kategori';
        }
    }
    
    // Save transaction if no errors
    if (empty($errors)) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO transactions (user_id, category_id, type, amount, description, transaction_date, reference_number, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $current_user['id'],
                $category_id,
                $type,
                $amount,
                $description,
                $transaction_date,
                $reference_number ?: null,
                $notes ?: null
            ]);
            
            if ($result) {
                $transaction_id = $db->lastInsertId();
                
                // Log activity
                logActivity('transaction_created', "Created {$type} transaction: {$description} - " . formatCurrency($amount));
                
                setFlashMessage('success', 'Transaksi berhasil ditambahkan');
                header('Location: index.php');
                exit();
            } else {
                $errors[] = 'Gagal menyimpan transaksi';
            }
        } catch (Exception $e) {
            error_log('Add transaction error: ' . $e->getMessage());
            $errors[] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}

try {
    $db = getDB();
    
    // Get categories
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY type ASC, name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    // Group categories by type
    $income_categories = array_filter($categories, function($cat) { return $cat['type'] === 'income'; });
    $expense_categories = array_filter($categories, function($cat) { return $cat['type'] === 'expense'; });
    
} catch (Exception $e) {
    error_log('Categories fetch error: ' . $e->getMessage());
    $income_categories = [];
    $expense_categories = [];
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
                    <h1 class="h3 mb-1">Tambah Transaksi</h1>
                    <p class="text-muted mb-0">Catat transaksi pemasukan atau pengeluaran baru</p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Terjadi kesalahan:</h6>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Transaction Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-plus-circle me-2"></i>Form Transaksi
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="transactionForm">
                        <!-- Transaction Type -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label">Jenis Transaksi <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="type" id="type_income" 
                                           value="income" <?php echo $default_type === 'income' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-success" for="type_income">
                                        <i class="bi bi-arrow-up-circle me-2"></i>Pemasukan
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="type" id="type_expense" 
                                           value="expense" <?php echo $default_type === 'expense' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-danger" for="type_expense">
                                        <i class="bi bi-arrow-down-circle me-2"></i>Pengeluaran
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Category -->
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Pilih Kategori</option>
                                    <optgroup label="Pemasukan" id="income_categories" style="display: <?php echo $default_type === 'income' ? 'block' : 'none'; ?>;">
                                        <?php foreach ($income_categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Pengeluaran" id="expense_categories" style="display: <?php echo $default_type === 'expense' ? 'block' : 'none'; ?>;">
                                        <?php foreach ($expense_categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>

                            <!-- Amount -->
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control currency-input" id="amount" name="amount" 
                                           placeholder="0" required>
                                </div>
                                <div class="form-text">Masukkan jumlah tanpa titik atau koma</div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Transaction Date -->
                            <div class="col-md-6 mb-3">
                                <label for="transaction_date" class="form-label">Tanggal Transaksi <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="transaction_date" name="transaction_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <!-- Reference Number -->
                            <div class="col-md-6 mb-3">
                                <label for="reference_number" class="form-label">Nomor Referensi</label>
                                <input type="text" class="form-control" id="reference_number" name="reference_number" 
                                       placeholder="Nomor nota, invoice, dll (opsional)">
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="description" name="description" 
                                   placeholder="Deskripsi singkat transaksi" required>
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="notes" class="form-label">Catatan</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Catatan tambahan (opsional)"></textarea>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>Batal
                            </a>
                            <div>
                                <button type="button" class="btn btn-outline-primary me-2" onclick="resetForm()">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Simpan Transaksi
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-save form data
    autoSaveForm('transactionForm');
    
    // Handle transaction type change
    $('input[name="type"]').change(function() {
        const type = $(this).val();
        
        // Show/hide category groups
        if (type === 'income') {
            $('#income_categories').show();
            $('#expense_categories').hide();
        } else {
            $('#income_categories').hide();
            $('#expense_categories').show();
        }
        
        // Reset category selection
        $('#category_id').val('');
        
        // Update form styling
        updateFormStyling(type);
    });
    
    // Initialize form styling
    const initialType = $('input[name="type"]:checked').val();
    updateFormStyling(initialType);
    
    // Format currency input
    $('#amount').on('input', function() {
        formatCurrencyInput(this);
    });
    
    // Form validation
    $('#transactionForm').on('submit', function(e) {
        const amount = getCurrencyValue($('#amount').val());
        const description = $('#description').val().trim();
        const category = $('#category_id').val();
        const date = $('#transaction_date').val();
        
        if (amount <= 0) {
            e.preventDefault();
            showToast('Jumlah harus lebih dari 0', 'danger');
            $('#amount').focus();
            return false;
        }
        
        if (!description) {
            e.preventDefault();
            showToast('Deskripsi harus diisi', 'danger');
            $('#description').focus();
            return false;
        }
        
        if (!category) {
            e.preventDefault();
            showToast('Kategori harus dipilih', 'danger');
            $('#category_id').focus();
            return false;
        }
        
        if (!date) {
            e.preventDefault();
            showToast('Tanggal transaksi harus diisi', 'danger');
            $('#transaction_date').focus();
            return false;
        }
        
        // Show loading
        showLoading();
    });
});

function updateFormStyling(type) {
    const card = $('.card');
    const submitBtn = $('button[type="submit"]');
    
    if (type === 'income') {
        card.removeClass('border-danger').addClass('border-success');
        submitBtn.removeClass('btn-danger').addClass('btn-success');
    } else {
        card.removeClass('border-success').addClass('border-danger');
        submitBtn.removeClass('btn-success').addClass('btn-danger');
    }
}

function resetForm() {
    if (confirm('Apakah Anda yakin ingin mereset form?')) {
        document.getElementById('transactionForm').reset();
        $('#transaction_date').val('<?php echo date('Y-m-d'); ?>');
        $('#amount').focus();
        
        // Clear auto-saved data
        localStorage.removeItem('autosave_transactionForm');
        
        showToast('Form berhasil direset', 'info');
    }
}

// Quick amount buttons
function addQuickAmountButtons() {
    const amounts = [10000, 25000, 50000, 100000, 250000, 500000];
    const container = $('<div class="mt-2"></div>');
    
    amounts.forEach(amount => {
        const btn = $(`<button type="button" class="btn btn-outline-secondary btn-sm me-1 mb-1">${formatCurrency(amount)}</button>`);
        btn.click(() => {
            $('#amount').val(amount.toLocaleString('id-ID'));
        });
        container.append(btn);
    });
    
    $('#amount').parent().after(container);
}

// Add quick amount buttons after page load
$(document).ready(function() {
    addQuickAmountButtons();
});
</script>

<?php include '../../components/footer.php'; ?>

