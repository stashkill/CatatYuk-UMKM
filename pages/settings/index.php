<?php
/**
 * Settings Page - CatatYuk
 */

// Include configuration and functions
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Require login
requireLogin();

$page_title = 'Pengaturan';
$current_user = getCurrentUser();

$errors = [];
$success_message = '';

try {
    $db = getDB();
    
    // Get current settings
    $settings = [];
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM app_settings");
    $stmt->execute();
    $settings_data = $stmt->fetchAll();
    
    foreach ($settings_data as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
    
    // Set default values if not exists
    $default_settings = [
        'company_name' => 'UMKM Saya',
        'company_address' => '',
        'company_phone' => '',
        'currency_symbol' => 'Rp',
        'notification_days_before' => '3'
    ];
    
    foreach ($default_settings as $key => $value) {
        if (!isset($settings[$key])) {
            $settings[$key] = $value;
        }
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            // Update user profile
            $full_name = sanitizeInput($_POST['full_name'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validation
            if (empty($full_name)) {
                $errors[] = 'Nama lengkap harus diisi';
            }
            
            if (empty($email) || !isValidEmail($email)) {
                $errors[] = 'Email tidak valid';
            }
            
            // Check if email already exists (for other users)
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $current_user['id']]);
            if ($stmt->fetch()) {
                $errors[] = 'Email sudah digunakan oleh pengguna lain';
            }
            
            // Password validation if changing password
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $errors[] = 'Password saat ini harus diisi untuk mengubah password';
                } else {
                    // Verify current password
                    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$current_user['id']]);
                    $user_data = $stmt->fetch();
                    
                    if (!verifyPassword($current_password, $user_data['password'])) {
                        $errors[] = 'Password saat ini tidak benar';
                    }
                }
                
                if (strlen($new_password) < 6) {
                    $errors[] = 'Password baru minimal 6 karakter';
                }
                
                if ($new_password !== $confirm_password) {
                    $errors[] = 'Konfirmasi password tidak cocok';
                }
            }
            
            // Update profile if no errors
            if (empty($errors)) {
                try {
                    if (!empty($new_password)) {
                        // Update with new password
                        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$full_name, $email, hashPassword($new_password), $current_user['id']]);
                    } else {
                        // Update without password change
                        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$full_name, $email, $current_user['id']]);
                    }
                    
                    // Update session
                    $_SESSION['full_name'] = $full_name;
                    
                    // Log activity
                    logActivity('profile_updated', 'Updated user profile');
                    
                    $success_message = 'Profil berhasil diperbarui';
                } catch (Exception $e) {
                    error_log('Update profile error: ' . $e->getMessage());
                    $errors[] = 'Terjadi kesalahan saat menyimpan profil';
                }
            }
        } elseif ($action === 'update_app_settings') {
            // Update application settings
            $company_name = sanitizeInput($_POST['company_name'] ?? '');
            $company_address = sanitizeInput($_POST['company_address'] ?? '');
            $company_phone = sanitizeInput($_POST['company_phone'] ?? '');
            $currency_symbol = sanitizeInput($_POST['currency_symbol'] ?? '');
            $notification_days_before = intval($_POST['notification_days_before'] ?? 3);
            
            // Validation
            if (empty($company_name)) {
                $errors[] = 'Nama perusahaan harus diisi';
            }
            
            if (!empty($company_phone) && !isValidPhone($company_phone)) {
                $errors[] = 'Format nomor telepon tidak valid';
            }
            
            if ($notification_days_before < 1 || $notification_days_before > 30) {
                $errors[] = 'Hari notifikasi harus antara 1-30 hari';
            }
            
            // Update settings if no errors
            if (empty($errors)) {
                try {
                    $settings_to_update = [
                        'company_name' => $company_name,
                        'company_address' => $company_address,
                        'company_phone' => $company_phone,
                        'currency_symbol' => $currency_symbol,
                        'notification_days_before' => $notification_days_before
                    ];
                    
                    foreach ($settings_to_update as $key => $value) {
                        updateAppSetting($key, $value);
                        $settings[$key] = $value; // Update local array
                    }
                    
                    // Log activity
                    logActivity('app_settings_updated', 'Updated application settings');
                    
                    $success_message = 'Pengaturan aplikasi berhasil diperbarui';
                } catch (Exception $e) {
                    error_log('Update app settings error: ' . $e->getMessage());
                    $errors[] = 'Terjadi kesalahan saat menyimpan pengaturan';
                }
            }
        }
    }
    
    // Get user data for form
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$current_user['id']]);
    $user_data = $stmt->fetch();
    
} catch (Exception $e) {
    error_log('Settings page error: ' . $e->getMessage());
    $errors[] = 'Terjadi kesalahan saat memuat pengaturan';
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
                    <h1 class="h3 mb-1">Pengaturan</h1>
                    <p class="text-muted mb-0">Kelola profil dan pengaturan aplikasi</p>
                </div>
                <div>
                    <a href="../dashboard/index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Terjadi kesalahan:</h6>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Settings Navigation -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-gear me-2"></i>Menu Pengaturan
                    </h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#profile-settings" class="list-group-item list-group-item-action active" data-bs-toggle="pill">
                        <i class="bi bi-person me-2"></i>Profil Pengguna
                    </a>
                    <a href="#app-settings" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                        <i class="bi bi-building me-2"></i>Pengaturan Aplikasi
                    </a>
                    <a href="#notification-settings" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                        <i class="bi bi-bell me-2"></i>Notifikasi
                    </a>
                    <a href="#backup-settings" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                        <i class="bi bi-cloud-download me-2"></i>Backup & Restore
                    </a>
                </div>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="col-lg-9">
            <div class="tab-content">
                <!-- Profile Settings -->
                <div class="tab-pane fade show active" id="profile-settings">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-person me-2"></i>Profil Pengguna
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="profileForm">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" 
                                               value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled>
                                        <div class="form-text">Username tidak dapat diubah</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="role" class="form-label">Role</label>
                                        <input type="text" class="form-control" id="role" 
                                               value="<?php echo ucfirst($user_data['role']); ?>" disabled>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                    </div>
                                </div>

                                <hr>
                                <h6>Ubah Password</h6>
                                <p class="text-muted">Kosongkan jika tidak ingin mengubah password</p>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="current_password" class="form-label">Password Saat Ini</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="new_password" class="form-label">Password Baru</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                        <div class="form-text">Minimal 6 karakter</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>Simpan Profil
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- App Settings -->
                <div class="tab-pane fade" id="app-settings">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-building me-2"></i>Pengaturan Aplikasi
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="appSettingsForm">
                                <input type="hidden" name="action" value="update_app_settings">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="company_name" class="form-label">Nama Perusahaan/UMKM <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="company_name" name="company_name" 
                                               value="<?php echo htmlspecialchars($settings['company_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="company_phone" class="form-label">Nomor Telepon</label>
                                        <input type="tel" class="form-control" id="company_phone" name="company_phone" 
                                               value="<?php echo htmlspecialchars($settings['company_phone']); ?>" 
                                               placeholder="08123456789">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="company_address" class="form-label">Alamat Perusahaan</label>
                                    <textarea class="form-control" id="company_address" name="company_address" rows="3" 
                                              placeholder="Alamat lengkap perusahaan"><?php echo htmlspecialchars($settings['company_address']); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="currency_symbol" class="form-label">Simbol Mata Uang</label>
                                        <select class="form-select" id="currency_symbol" name="currency_symbol">
                                            <option value="Rp" <?php echo $settings['currency_symbol'] === 'Rp' ? 'selected' : ''; ?>>Rp (Rupiah)</option>
                                            <option value="$" <?php echo $settings['currency_symbol'] === '$' ? 'selected' : ''; ?>>$ (Dollar)</option>
                                            <option value="€" <?php echo $settings['currency_symbol'] === '€' ? 'selected' : ''; ?>>€ (Euro)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="notification_days_before" class="form-label">Pengingat Jatuh Tempo</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="notification_days_before" 
                                                   name="notification_days_before" min="1" max="30" 
                                                   value="<?php echo htmlspecialchars($settings['notification_days_before']); ?>">
                                            <span class="input-group-text">hari sebelumnya</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>Simpan Pengaturan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="tab-pane fade" id="notification-settings">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-bell me-2"></i>Pengaturan Notifikasi
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info" role="alert">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Fitur dalam pengembangan</strong><br>
                                Pengaturan notifikasi email dan desktop akan tersedia dalam versi mendatang.
                            </div>
                            
                            <form>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_notifications" disabled>
                                        <label class="form-check-label" for="email_notifications">
                                            Notifikasi Email
                                        </label>
                                    </div>
                                    <div class="form-text">Terima notifikasi melalui email untuk pengingat jatuh tempo</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="desktop_notifications" disabled>
                                        <label class="form-check-label" for="desktop_notifications">
                                            Notifikasi Desktop
                                        </label>
                                    </div>
                                    <div class="form-text">Tampilkan notifikasi di browser untuk pengingat penting</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="sms_notifications" disabled>
                                        <label class="form-check-label" for="sms_notifications">
                                            Notifikasi SMS
                                        </label>
                                    </div>
                                    <div class="form-text">Terima SMS untuk pengingat jatuh tempo (fitur premium)</div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Backup Settings -->
                <div class="tab-pane fade" id="backup-settings">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-cloud-download me-2"></i>Backup & Restore Data
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Fitur dalam pengembangan</strong><br>
                                Fitur backup dan restore data akan tersedia dalam versi mendatang.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6>Backup Data</h6>
                                    <p class="text-muted">Unduh backup data aplikasi dalam format JSON atau CSV</p>
                                    <button type="button" class="btn btn-outline-primary" disabled>
                                        <i class="bi bi-download me-2"></i>Download Backup
                                    </button>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6>Restore Data</h6>
                                    <p class="text-muted">Pulihkan data dari file backup yang telah disimpan</p>
                                    <button type="button" class="btn btn-outline-secondary" disabled>
                                        <i class="bi bi-upload me-2"></i>Upload Restore
                                    </button>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="mb-3">
                                <h6>Backup Otomatis</h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="auto_backup" disabled>
                                    <label class="form-check-label" for="auto_backup">
                                        Aktifkan backup otomatis harian
                                    </label>
                                </div>
                                <div class="form-text">Data akan di-backup secara otomatis setiap hari pada pukul 02:00</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-save forms
    autoSaveForm('profileForm');
    autoSaveForm('appSettingsForm');
    
    // Phone number formatting
    $('#company_phone').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.startsWith('62')) {
            value = '0' + value.substring(2);
        }
        this.value = value;
    });
    
    // Password validation
    $('#new_password, #confirm_password').on('input', function() {
        const newPassword = $('#new_password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (newPassword && confirmPassword) {
            if (newPassword !== confirmPassword) {
                $('#confirm_password')[0].setCustomValidity('Password tidak cocok');
            } else {
                $('#confirm_password')[0].setCustomValidity('');
            }
        }
    });
    
    // Form validation
    $('#profileForm').on('submit', function(e) {
        const newPassword = $('#new_password').val();
        const currentPassword = $('#current_password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (newPassword && !currentPassword) {
            e.preventDefault();
            showToast('Password saat ini harus diisi untuk mengubah password', 'danger');
            $('#current_password').focus();
            return false;
        }
        
        if (newPassword && newPassword !== confirmPassword) {
            e.preventDefault();
            showToast('Konfirmasi password tidak cocok', 'danger');
            $('#confirm_password').focus();
            return false;
        }
        
        if (newPassword && newPassword.length < 6) {
            e.preventDefault();
            showToast('Password baru minimal 6 karakter', 'danger');
            $('#new_password').focus();
            return false;
        }
        
        showLoading();
    });
    
    $('#appSettingsForm').on('submit', function(e) {
        const companyName = $('#company_name').val().trim();
        const notificationDays = parseInt($('#notification_days_before').val());
        
        if (!companyName) {
            e.preventDefault();
            showToast('Nama perusahaan harus diisi', 'danger');
            $('#company_name').focus();
            return false;
        }
        
        if (notificationDays < 1 || notificationDays > 30) {
            e.preventDefault();
            showToast('Hari notifikasi harus antara 1-30 hari', 'danger');
            $('#notification_days_before').focus();
            return false;
        }
        
        showLoading();
    });
    
    // Tab switching
    $('[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).attr('href');
        history.replaceState(null, null, target);
    });
    
    // Load tab from URL hash
    if (window.location.hash) {
        const hash = window.location.hash;
        $('[data-bs-toggle="pill"][href="' + hash + '"]').tab('show');
    }
});
</script>

<?php include '../../components/footer.php'; ?>

