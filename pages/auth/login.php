<?php
/**
 * Login Page - CatatYuk
 */

// Include configuration and functions
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Start session
startSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/pages/dashboard/index.php');
    exit();
}

$error_message = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password harus diisi';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                SELECT id, username, password, full_name, email, role, status 
                FROM users 
                WHERE username = ? AND status = 'active'
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                // Log activity
                logActivity('login', 'User logged in successfully', $user['id']);
                
                // Redirect to dashboard
                header('Location: ' . APP_URL . '/pages/dashboard/index.php');
                exit();
            } else {
                $error_message = 'Username atau password salah';
                logActivity('login_failed', 'Failed login attempt for username: ' . $username);
            }
        } catch (Exception $e) {
            $error_message = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}

$page_title = 'Login';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . ' - ' . APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .login-logo .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .login-logo .logo-icon i {
            font-size: 2.5rem;
            color: white;
        }
        
        .login-logo h1 {
            color: #333;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-logo p {
            color: #666;
            margin-bottom: 0;
            font-size: 1.1rem;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 1.5rem;
        }
        
        .demo-accounts {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .demo-accounts h6 {
            color: #495057;
            margin-bottom: 1rem;
        }
        
        .demo-account {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #667eea;
        }
        
        .demo-account:last-child {
            margin-bottom: 0;
        }
        
        .demo-account strong {
            color: #333;
        }
        
        .demo-account small {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <div class="logo-icon">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <h1>CatatYuk.</h1>
                <p>Aplikasi Pencatatan Keuangan UMKM</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Username" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
                    <label for="username">
                        <i class="bi bi-person me-2"></i>Username
                    </label>
                </div>
                
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password">
                        <i class="bi bi-lock me-2"></i>Password
                    </label>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="showPassword">
                    <label class="form-check-label" for="showPassword">
                        Tampilkan password
                    </label>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Masuk
                </button>
            </form>
            
            <!-- Demo Accounts -->
            <div class="demo-accounts">
                <h6><i class="bi bi-info-circle me-2"></i>Akun Demo</h6>
                <div class="demo-account">
                    <div><strong>Administrator</strong></div>
                    <div><small>Username: admin | Password: admin123</small></div>
                </div>
            <!--   <div class="demo-account">
                    <div><strong>Kasir</strong></div>
                    <div><small>Username: kasir1 | Password: admin123</small></div>
                </div> -->
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Show/hide password
            $('#showPassword').change(function() {
                const passwordField = $('#password');
                const type = this.checked ? 'text' : 'password';
                passwordField.attr('type', type);
            });
            
            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // Form validation
            $('#loginForm').on('submit', function(e) {
                const username = $('#username').val().trim();
                const password = $('#password').val();
                
                if (!username || !password) {
                    e.preventDefault();
                    alert('Username dan password harus diisi');
                    return false;
                }
            });
            
            // Demo account quick fill
            $('.demo-account').on('click', function() {
                const text = $(this).text();
                if (text.includes('admin')) {
                    $('#username').val('admin');
                    $('#password').val('admin123');
                } else if (text.includes('kasir1')) {
                    $('#username').val('kasir1');
                    $('#password').val('admin123');
                }
            });
            
            // Focus on username field
            $('#username').focus();
        });
    </script>
</body>
</html>

