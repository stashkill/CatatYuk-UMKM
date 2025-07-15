<?php
/**
 * Add Debt/Receivable - CatatYuk
 */

// Include configuration and functions
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Require login
requireLogin();

$page_title = 'Tambah Utang/Piutang';
$current_user = getCurrentUser();

// Get type from URL parameter
$default_type = $_GET['type'] ?? 'debt';
if (!in_array($default_type, ['debt', 'receivable'])) {
    $default_type = 'debt';
}

$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = sanitizeInput($_POST['type'] ?? '');
    $contact_name = sanitizeInput($_POST['contact_name'] ?? '');
    $contact_phone = sanitizeInput($_POST['contact_phone'] ?? '');
    $amount = getCurrencyValue($_POST['amount'] ?? '0');
    $description = sanitizeInput($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? '';
    
    // Validation
    if (empty($type) || !in_array($type, ['debt', 'receivable'])) {
        $errors[] = 'Jenis harus dipilih';
    }
    
    if (empty($contact_name)) {
        $errors[] = 'Nama kontak harus diisi';
    }
    
    if (!empty($contact_phone) && !isValidPhone($contact_phone)) {
        $errors[] = 'Format nomor telepon tidak valid';
    }
    
    if ($amount <= 0) {
        $errors[] = 'Jumlah harus lebih dari 0';
    }
    
    if (empty($description)) {
        $errors[] = 'Deskripsi harus diisi';
    }
    
    if (!empty($due_date) && strtotime($due_date) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Tanggal jatuh tempo tidak boleh di masa lalu';
    }
    
    // Save debt/receivable if no errors
    if (empty($errors)) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO debts_receivables (user_id, type, contact_name, contact_phone, amount, remaining_amount, description, due_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $current_user['id'],
                $type,
                $contact_name,
                $contact_phone ?: null,
                $amount,
                $amount, // remaining_amount initially equals amount
                $description,
                $due_date ?: null
            ]);
            
            if ($result) {
                $debt_id = $db->lastInsertId();
                
                // Log activity
                logActivity('debt_created', "Created {$type}: {$contact_name} - " . formatCurrency($amount));
                
                // Create notification for due date reminder
                if (!empty($due_date)) {
                    $reminder_date = date('Y-m-d', strtotime($due_date . ' -3 days'));
                    if (strtotime($reminder_date) >= strtotime(date('Y-m-d'))) {
                        $stmt = $db->prepare("
                            INSERT INTO notifications (user_id, type, title, message, related_id, scheduled_date)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        
                        $notification_type = $type === 'debt' ? 'debt_reminder' : 'receivable_reminder';
                        $title = $type === 'debt' ? 'Pengingat Pembayaran Utang' : 'Pengingat Penagihan Piutang';
                        $message = "Jatuh tempo {$type} kepada {$contact_name} pada " . formatDate($due_date);
                        
                        $stmt->execute([
                            $current_user['id'],
                            $notification_type,
                            $title,
                            $message,
                            $debt_id,
                            $reminder_date
                        ]);
                    }
                }
                
                setFlashMessage('success', ucfirst($type) . ' berhasil ditambahkan');
                header('Location: index.php');
                exit();
            } else {
                $errors[] = 'Gagal menyimpan data';
            }
        } catch (Exception $e) {
            error_log('Add debt error: ' . $e->getMessage());
            $errors[] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
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
                    <h1 class="h3 mb-1">Tambah Utang/Piutang</h1>
                    <p class="text-muted mb-0">Catat utang atau piutang baru</p>
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

    <!-- Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-plus-circle me-2"></i>Form Utang/Piutang
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="debtForm">
                        <!-- Type Selection -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label">Jenis <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="type" id="type_debt" 
                                           value="debt" <?php echo $default_type === 'debt' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-danger" for="type_debt">
                                        <i class="bi bi-credit-card me-2"></i>Utang
                                        <small class="d-block">Uang yang harus saya bayar</small>
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="type" id="type_receivable" 
                                           value="receivable" <?php echo $default_type === 'receivable' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-success" for="type_receivable">
                                        <i class="bi bi-cash-stack me-2"></i>Piutang
                                        <small class="d-block">Uang yang harus dibayar ke saya</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Contact Name -->
                            <div class="col-md-6 mb-3">
                                <label for="contact_name" class="form-label">Nama Kontak <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="contact_name" name="contact_name" 
                                       placeholder="Nama orang/perusahaan" required 
                                       value="<?php echo htmlspecialchars($_POST['contact_name'] ?? ''); ?>">
                            </div>

                            <!-- Contact Phone -->
                            <div class="col-md-6 mb-3">
                                <label for="contact_phone" class="form-label">Nomor Telepon</label>
                                <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                       placeholder="08123456789 (opsional)" data-type="phone"
                                       value="<?php echo htmlspecialchars($_POST['contact_phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <!-- Amount -->
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control currency-input" id="amount" name="amount" 
                                           placeholder="0" required 
                                           value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>">
                                </div>
                                <div class="form-text">Masukkan jumlah total utang/piutang</div>
                            </div>

                            <!-- Due Date -->
                            <div class="col-md-6 mb-3">
                                <label for="due_date" class="form-label">Tanggal Jatuh Tempo</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>">
                                <div class="form-text">Kosongkan jika tidak ada jatuh tempo</div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Deskripsi utang/piutang (tujuan, konteks, dll)" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <!-- Info Box -->
                        <div class="alert alert-info" role="alert">
                            <h6><i class="bi bi-info-circle me-2"></i>Informasi:</h6>
                            <ul class="mb-0">
                                <li><strong>Utang:</strong> Uang yang Anda pinjam dan harus dibayar kembali</li>
                                <li><strong>Piutang:</strong> Uang yang dipinjam orang lain dan harus dibayar ke Anda</li>
                                <li>Jika ada tanggal jatuh tempo, sistem akan mengirim pengingat 3 hari sebelumnya</li>
                                <li>Status akan otomatis berubah menjadi "Jatuh Tempo" jika melewati tanggal yang ditentukan</li>
                            </ul>
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
                                    <i class="bi bi-check-circle me-2"></i>Simpan Data
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
    autoSaveForm('debtForm');
    
    // Handle type change
    $('input[name="type"]').change(function() {
        const type = $(this).val();
        updateFormStyling(type);
    });
    
    // Initialize form styling
    const initialType = $('input[name="type"]:checked').val();
    updateFormStyling(initialType);
    
    // Format currency input
    $('#amount').on('input', function() {
        formatCurrencyInput(this);
    });
    
    // Phone number formatting
    $('#contact_phone').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.startsWith('62')) {
            value = '0' + value.substring(2);
        } else if (value.startsWith('8')) {
            value = '0' + value;
        }
        this.value = value;
    });
    
    // Form validation
    $('#debtForm').on('submit', function(e) {
        const amount = getCurrencyValue($('#amount').val());
        const contactName = $('#contact_name').val().trim();
        const description = $('#description').val().trim();
        const phone = $('#contact_phone').val().trim();
        
        if (amount <= 0) {
            e.preventDefault();
            showToast('Jumlah harus lebih dari 0', 'danger');
            $('#amount').focus();
            return false;
        }
        
        if (!contactName) {
            e.preventDefault();
            showToast('Nama kontak harus diisi', 'danger');
            $('#contact_name').focus();
            return false;
        }
        
        if (!description) {
            e.preventDefault();
            showToast('Deskripsi harus diisi', 'danger');
            $('#description').focus();
            return false;
        }
        
        if (phone && !isValidPhone(phone)) {
            e.preventDefault();
            showToast('Format nomor telepon tidak valid', 'danger');
            $('#contact_phone').focus();
            return false;
        }
        
        // Show loading
        showLoading();
    });
});

function updateFormStyling(type) {
    const card = $('.card');
    const submitBtn = $('button[type="submit"]');
    
    if (type === 'debt') {
        card.removeClass('border-success').addClass('border-danger');
        submitBtn.removeClass('btn-success').addClass('btn-danger');
    } else {
        card.removeClass('border-danger').addClass('border-success');
        submitBtn.removeClass('btn-danger').addClass('btn-success');
    }
}

function resetForm() {
    if (confirm('Apakah Anda yakin ingin mereset form?')) {
        document.getElementById('debtForm').reset();
        $('#contact_name').focus();
        
        // Clear auto-saved data
        localStorage.removeItem('autosave_debtForm');
        
        showToast('Form berhasil direset', 'info');
    }
}

// Quick amount buttons
function addQuickAmountButtons() {
    const amounts = [100000, 500000, 1000000, 2500000, 5000000, 10000000];
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

function formatCurrency(amount) {
    return 'Rp ' + parseFloat(amount).toLocaleString('id-ID');
}
</script>

<?php include '../../components/footer.php'; ?>

