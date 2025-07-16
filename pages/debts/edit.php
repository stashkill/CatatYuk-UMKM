<?php
/**
 * Edit Debt/Hutang - CatatYuk
 */

// Include configuration and functions
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Require login
requireLogin();

$page_title = 'Edit Utang/Piutang';
$current_user = getCurrentUser();

$debt_id = intval($_GET['id'] ?? 0);
$debt = null;
$errors = [];

try {
    $db = getDB();
    
    // Fetch existing debt/receivable data
    if ($debt_id > 0) {
        $stmt = $db->prepare("SELECT * FROM debts_receivables WHERE id = ?");
        $stmt->execute([$debt_id]);
        $debt = $stmt->fetch();
        
        if (!$debt) {
            setFlashMessage('error', 'Data utang/piutang tidak ditemukan.');
            header('Location: index.php');
            exit();
        }
        
        // Check if user can edit this debt (admin can edit all, kasir can only edit their own)
        if (!hasRole('admin') && $debt['user_id'] != $current_user['id']) {
            setFlashMessage('error', 'Anda tidak memiliki akses untuk mengedit data ini.');
            header('Location: index.php');
            exit();
        }
    } else {
        setFlashMessage('error', 'ID utang/piutang tidak valid.');
        header('Location: index.php');
        exit();
    }

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
        
        // Update debt/receivable if no errors
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("
                    UPDATE debts_receivables 
                    SET type = ?, contact_name = ?, contact_phone = ?, amount = ?, description = ?, due_date = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([
                    $type,
                    $contact_name,
                    $contact_phone ?: null,
                    $amount,
                    $description,
                    $due_date ?: null,
                    $debt_id
                ]);
                
                if ($result) {
                    // Log activity
                    logActivity('debt_updated', "Updated {$type}: {$contact_name} - " . formatCurrency($amount));
                    
                    // Update or create notification for due date reminder
                    if (!empty($due_date)) {
                        $reminder_date = date('Y-m-d', strtotime($due_date . ' -3 days'));
                        if (strtotime($reminder_date) >= strtotime(date('Y-m-d'))) {
                            $notification_type = $type === 'debt' ? 'debt_reminder' : 'receivable_reminder';
                            $title = $type === 'debt' ? 'Pengingat Pembayaran Utang' : 'Pengingat Penagihan Piutang';
                            $message = sprintf("Jatuh tempo %s kepada %s pada %s", ucfirst($type), $contact_name, formatDate($due_date));
                            
                            // Check if notification already exists for this debt
                            $stmt_check_notif = $db->prepare("SELECT id FROM notifications WHERE related_id = ? AND type = ?");
                            $stmt_check_notif->execute([$debt_id, $notification_type]);
                            $existing_notif = $stmt_check_notif->fetch();

                            if ($existing_notif) {
                                // Update existing notification
                                $stmt_update_notif = $db->prepare("
                                    UPDATE notifications SET title = ?, message = ?, scheduled_date = ?, updated_at = NOW()
                                    WHERE id = ?
                                ");
                                $stmt_update_notif->execute([$title, $message, $reminder_date, $existing_notif["id"]]);
                            } else {
                                // Insert new notification
                                $stmt_insert_notif = $db->prepare("
                                    INSERT INTO notifications (user_id, type, title, message, related_id, scheduled_date)
                                    VALUES (?, ?, ?, ?, ?, ?)
                                ");
                                $stmt_insert_notif->execute([
                                    $current_user["id"],
                                    $notification_type,
                                    $title,
                                    $message,
                                    $debt_id,
                                    $reminder_date
                                ]);
                            }
                        }
                    } else {
                        // If due_date is empty, delete any existing reminder notification for this debt
                        $stmt_delete_notif = $db->prepare("DELETE FROM notifications WHERE related_id = ? AND type IN (
                            'debt_reminder', 'receivable_reminder'
                        )");
                        $stmt_delete_notif->execute([$debt_id]);
                    }
                    
                    setFlashMessage("success", ucfirst($type) . " berhasil diperbarui");
                    header("Location: index.php");
                    exit();
                } else {
                    $errors[] = "Gagal menyimpan perubahan";
                }
            } catch (Exception $e) {
                error_log("Edit debt error: " . $e->getMessage());
                $errors[] = "Terjadi kesalahan sistem. Silakan coba lagi.";
            }
        }
    }
} catch (Exception $e) {
    error_log("Fetch debt error: " . $e->getMessage());
    setFlashMessage("error", "Terjadi kesalahan saat memuat data utang/piutang.");
    header("Location: index.php");
    exit();
}

// Include header
include "../../components/header.php";
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Edit Utang/Piutang</h1>
                    <p class="text-muted mb-0">Ubah detail utang atau piutang</p>
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
                        <i class="bi bi-pencil-square me-2"></i>Form Edit Utang/Piutang
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
                                           value="debt" <?php echo $debt["type"] === "debt" ? "checked" : ""; ?> <?php echo $debt["status"] !== "pending" ? "disabled" : ""; ?>>
                                    <label class="btn btn-outline-danger" for="type_debt">
                                        <i class="bi bi-credit-card me-2"></i>Utang
                                        <small class="d-block">Uang yang harus saya bayar</small>
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="type" id="type_receivable" 
                                           value="receivable" <?php echo $debt["type"] === "receivable" ? "checked" : ""; ?> <?php echo $debt["status"] !== "pending" ? "disabled" : ""; ?>>
                                    <label class="btn btn-outline-success" for="type_receivable">
                                        <i class="bi bi-cash-stack me-2"></i>Piutang
                                        <small class="d-block">Uang yang harus dibayar ke saya</small>
                                    </label>
                                </div>
                                <?php if ($debt["status"] !== "pending"): ?>
                                    <div class="form-text text-warning">Jenis tidak dapat diubah karena status sudah <?php echo $debt["status"]; ?>.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Contact Name -->
                            <div class="col-md-6 mb-3">
                                <label for="contact_name" class="form-label">Nama Kontak <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="contact_name" name="contact_name" 
                                       placeholder="Nama orang/perusahaan" required 
                                       value="<?php echo htmlspecialchars($_POST["contact_name"] ?? $debt["contact_name"]); ?>">
                            </div>

                            <!-- Contact Phone -->
                            <div class="col-md-6 mb-3">
                                <label for="contact_phone" class="form-label">Nomor Telepon</label>
                                <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                       placeholder="08123456789 (opsional)" data-type="phone"
                                       value="<?php echo htmlspecialchars($_POST["contact_phone"] ?? $debt["contact_phone"]); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <!-- Amount -->
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <?php
                                    $amount = $_POST['amount'] ?? ($debt['amount'] ?? '');
                                    $amount = number_format((float) $amount, 0, '', '.');
                                    ?>
                                    <input type="text" class="form-control currency-input" id="amount" name="amount" 
                                           placeholder="0" value="<?php echo htmlspecialchars($amount); ?>" required>
                                </div>
                                <div class="form-text">Masukkan jumlah total utang/piutang</div>
                            </div>

                            <!-- Due Date -->
                            <div class="col-md-6 mb-3">
                                <label for="due_date" class="form-label">Tanggal Jatuh Tempo</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       value="<?php echo htmlspecialchars($_POST["due_date"] ?? $debt["due_date"]); ?>">
                                <div class="form-text">Kosongkan jika tidak ada jatuh tempo</div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Deskripsi utang/piutang (tujuan, konteks, dll)" required><?php echo htmlspecialchars($_POST["description"] ?? $debt["description"]); ?></textarea>
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
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Update Data
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
    autoSaveForm("debtForm");
    
    // Handle type change
    $("input[name=\'type\"]").change(function() {
        const type = $(this).val();
        updateFormStyling(type);
    });
    
    // Initialize form styling
    const initialType = $("input[name=\'type\"]:checked").val();
    updateFormStyling(initialType);
    
    // Format currency input
    $("#amount").on("input", function() {
        formatCurrencyInput(this);
    });
    
    // Phone number formatting
    $("#contact_phone").on("input", function() {
        let value = this.value.replace(/\D/g, "");
        if (value.startsWith("62")) {
            value = "0" + value.substring(2);
        }
        this.value = value;
    });
    
    // Form validation
    $("#debtForm").on("submit", function(e) {
        const amount = getCurrencyValue($("#amount").val());
        const contactName = $("#contact_name").val().trim();
        const description = $("#description").val().trim();
        const phone = $("#contact_phone").val().trim();
        
        if (amount <= 0) {
            e.preventDefault();
            showToast("Jumlah harus lebih dari 0", "danger");
            $("#amount").focus();
            return false;
        }
        
        if (!contactName) {
            e.preventDefault();
            showToast("Nama kontak harus diisi", "danger");
            $("#contact_name").focus();
            return false;
        }
        
        if (!description) {
            e.preventDefault();
            showToast("Deskripsi harus diisi", "danger");
            $("#description").focus();
            return false;
        }
        
        if (phone && !isValidPhone(phone)) {
            e.preventDefault();
            showToast("Format nomor telepon tidak valid", "danger");
            $("#contact_phone").focus();
            return false;
        }
        
        // Show loading
        showLoading();
    });
});

function updateFormStyling(type) {
    const card = $(".card");
    const submitBtn = $("button[type=\'submit\"]");
    
    if (type === "debt") {
        card.removeClass("border-success").addClass("border-danger");
        submitBtn.removeClass("btn-success").addClass("btn-danger");
    } else {
        card.removeClass("border-danger").addClass("border-success");
        submitBtn.removeClass("btn-danger").addClass("btn-success");
    }
}

function formatCurrencyValue(amount) {
    return parseFloat(amount).toLocaleString("id-ID");
}
</script>

<?php include "../../components/footer.php"; ?>


