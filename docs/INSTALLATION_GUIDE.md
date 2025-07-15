# Panduan Instalasi CatatYuk

Panduan lengkap untuk menginstal dan mengkonfigurasi aplikasi CatatYuk di berbagai environment.

## Daftar Isi

1. [Persiapan Environment](#persiapan-environment)
2. [Instalasi dengan XAMPP](#instalasi-dengan-xampp)
3. [Instalasi Manual](#instalasi-manual)
4. [Konfigurasi Database](#konfigurasi-database)
5. [Konfigurasi Aplikasi](#konfigurasi-aplikasi)
6. [Testing Instalasi](#testing-instalasi)
7. [Troubleshooting](#troubleshooting)

## Persiapan Environment

### Persyaratan Sistem

#### Minimum Requirements
- **Operating System**: Windows 10, macOS 10.14, Ubuntu 18.04 atau yang lebih baru
- **PHP**: 8.0 atau lebih baru
- **MySQL**: 8.0 atau lebih baru
- **Apache**: 2.4 atau lebih baru
- **RAM**: 512 MB
- **Storage**: 100 MB ruang kosong
- **Browser**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

#### Recommended Requirements
- **Operating System**: Windows 11, macOS 12+, Ubuntu 20.04+
- **PHP**: 8.1 atau lebih baru dengan ekstensi: mysqli, pdo_mysql, gd, curl, json
- **MySQL**: 8.0 atau lebih baru
- **Apache**: 2.4 dengan mod_rewrite enabled
- **RAM**: 1 GB atau lebih
- **Storage**: 500 MB ruang kosong
- **Browser**: Versi terbaru dari browser modern

### PHP Extensions yang Diperlukan

Pastikan ekstensi PHP berikut sudah terinstall dan aktif:

```ini
extension=mysqli
extension=pdo_mysql
extension=gd
extension=curl
extension=json
extension=mbstring
extension=openssl
extension=zip
```

Cek ekstensi yang terinstall dengan:
```bash
php -m
```

## Instalasi dengan XAMPP

XAMPP adalah cara termudah untuk menjalankan CatatYuk di localhost.

### 1. Download dan Install XAMPP

#### Windows
1. Download XAMPP dari [https://www.apachefriends.org/download.html](https://www.apachefriends.org/download.html)
2. Pilih versi PHP 8.0 atau lebih baru
3. Jalankan installer dan ikuti petunjuk instalasi
4. Install di direktori default `C:\xampp`

#### macOS
1. Download XAMPP untuk macOS
2. Mount file DMG dan drag XAMPP ke Applications
3. Buka Terminal dan jalankan:
   ```bash
   sudo /Applications/XAMPP/xamppfiles/xampp start
   ```

#### Linux (Ubuntu/Debian)
1. Download XAMPP untuk Linux
2. Buat file executable:
   ```bash
   chmod +x xampp-linux-x64-8.1.0-installer.run
   ```
3. Install dengan:
   ```bash
   sudo ./xampp-linux-x64-8.1.0-installer.run
   ```

### 2. Start Services

1. Buka XAMPP Control Panel
2. Start service **Apache** dan **MySQL**
3. Pastikan status menunjukkan "Running" dengan background hijau

### 3. Verifikasi Instalasi

1. Buka browser dan akses `http://localhost`
2. Anda akan melihat halaman welcome XAMPP
3. Klik "phpMyAdmin" untuk memastikan MySQL berjalan

### 4. Download CatatYuk

1. Download atau clone source code CatatYuk
2. Extract ke folder `htdocs` XAMPP:
   - Windows: `C:\xampp\htdocs\CatatYuk`
   - macOS: `/Applications/XAMPP/xamppfiles/htdocs/CatatYuk`
   - Linux: `/opt/lampp/htdocs/CatatYuk`

## Instalasi Manual

Untuk instalasi di server production atau environment custom.

### 1. Install PHP

#### Ubuntu/Debian
```bash
sudo apt update
sudo apt install php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-gd php8.1-curl php8.1-mbstring php8.1-zip
```

#### CentOS/RHEL
```bash
sudo yum install epel-release
sudo yum install php81 php81-php-cli php81-php-fpm php81-php-mysql php81-php-gd php81-php-curl php81-php-mbstring
```

#### Windows
1. Download PHP dari [https://windows.php.net/download/](https://windows.php.net/download/)
2. Extract ke `C:\php`
3. Tambahkan `C:\php` ke PATH environment variable
4. Copy `php.ini-production` ke `php.ini`
5. Enable ekstensi yang diperlukan

### 2. Install MySQL

#### Ubuntu/Debian
```bash
sudo apt install mysql-server mysql-client
sudo mysql_secure_installation
```

#### CentOS/RHEL
```bash
sudo yum install mysql-server mysql
sudo systemctl start mysqld
sudo systemctl enable mysqld
sudo mysql_secure_installation
```

#### Windows
1. Download MySQL Installer dari [https://dev.mysql.com/downloads/installer/](https://dev.mysql.com/downloads/installer/)
2. Jalankan installer dan pilih "Server only"
3. Ikuti wizard setup dan buat root password

### 3. Install Apache

#### Ubuntu/Debian
```bash
sudo apt install apache2
sudo systemctl start apache2
sudo systemctl enable apache2
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### CentOS/RHEL
```bash
sudo yum install httpd
sudo systemctl start httpd
sudo systemctl enable httpd
```

#### Windows
1. Download Apache dari [https://httpd.apache.org/download.cgi](https://httpd.apache.org/download.cgi)
2. Extract dan install sesuai dokumentasi Apache
3. Konfigurasi sebagai Windows Service

### 4. Konfigurasi Virtual Host (Opsional)

Buat virtual host untuk CatatYuk:

```apache
<VirtualHost *:80>
    ServerName catatYuk.local
    DocumentRoot /var/www/html/CatatYuk
    
    <Directory /var/www/html/CatatYuk>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/catatYuk_error.log
    CustomLog ${APACHE_LOG_DIR}/catatYuk_access.log combined
</VirtualHost>
```

Tambahkan ke `/etc/hosts`:
```
127.0.0.1 catatYuk.local
```

## Konfigurasi Database

### 1. Akses MySQL

#### Via phpMyAdmin (XAMPP)
1. Buka `http://localhost/phpmyadmin`
2. Login dengan username `root` (password kosong untuk XAMPP default)

#### Via Command Line
```bash
mysql -u root -p
```

### 2. Buat Database

```sql
CREATE DATABASE catatYuk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Buat User Database (Recommended untuk Production)

```sql
CREATE USER 'catatYuk_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON catatYuk.* TO 'catatYuk_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Import Schema Database

#### Via phpMyAdmin
1. Pilih database `catatYuk`
2. Klik tab "Import"
3. Pilih file `sql/database_design.sql`
4. Klik "Go" untuk import

#### Via Command Line
```bash
mysql -u root -p catatYuk < sql/database_design.sql
```

### 5. Verifikasi Import

Cek apakah tabel sudah terbuat:
```sql
USE catatYuk;
SHOW TABLES;
```

Anda akan melihat tabel:
- users
- categories
- transactions
- debts_receivables
- debt_payments
- notifications
- activity_logs
- app_settings

## Konfigurasi Aplikasi

### 1. Konfigurasi Database

Edit file `config/database.php`:

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'catatYuk');
define('DB_USER', 'catatYuk_user');  // atau 'root' untuk XAMPP
define('DB_PASS', 'your_password');   // atau '' untuk XAMPP
define('DB_CHARSET', 'utf8mb4');

// Database Connection Options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
?>
```

### 2. Konfigurasi Aplikasi

Edit file `config/app.php` (buat jika belum ada):

```php
<?php
// Application Configuration
define('APP_NAME', 'CatatYuk');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/CatatYuk');
define('APP_TIMEZONE', 'Asia/Jakarta');

// Security Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_LIFETIME', 1800); // 30 minutes

// File Upload Configuration
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);

// Email Configuration (untuk notifikasi)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');
define('SMTP_ENCRYPTION', 'tls');
?>
```

### 3. Set Permissions (Linux/macOS)

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/html/CatatYuk

# Set permissions
sudo chmod -R 755 /var/www/html/CatatYuk
sudo chmod -R 644 /var/www/html/CatatYuk/config/
sudo chmod 600 /var/www/html/CatatYuk/config/database.php

# Create logs directory
mkdir -p /var/www/html/CatatYuk/logs
sudo chmod 755 /var/www/html/CatatYuk/logs
```

### 4. Konfigurasi Apache .htaccess

Pastikan file `.htaccess` ada di root directory:

```apache
RewriteEngine On

# Redirect to HTTPS (uncomment for production)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove index.php from URL
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</IfModule>

# Prevent access to sensitive files
<Files "*.php">
    <RequireAll>
        Require all granted
    </RequireAll>
</Files>

<FilesMatch "\.(htaccess|htpasswd|ini|log|sh|sql)$">
    Require all denied
</FilesMatch>

# Cache static files
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
</IfModule>
```

## Testing Instalasi

### 1. Test Koneksi Database

Buat file `test_db.php` di root directory:

```php
<?php
require_once 'config/database.php';

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Database connection successful!<br>";
    
    // Test query
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Found " . $result['count'] . " users in database<br>";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage();
}
?>
```

Akses `http://localhost/CatatYuk/test_db.php`

### 2. Test Aplikasi

1. Akses `http://localhost/CatatYuk`
2. Anda akan diarahkan ke halaman login
3. Login dengan akun default:
   - **Admin**: admin@catatYuk.com / admin123
   - **Kasir**: kasir@catatYuk.com / kasir123

### 3. Test Fitur Utama

1. **Dashboard**: Pastikan grafik dan statistik muncul
2. **Transaksi**: Coba tambah, edit, dan hapus transaksi
3. **Utang/Piutang**: Test CRUD utang dan piutang
4. **Laporan**: Generate laporan dan test export PDF
5. **Notifikasi**: Cek sistem notifikasi

### 4. Test Responsiveness

Test aplikasi di berbagai ukuran layar:
- Desktop (1920x1080)
- Tablet (768x1024)
- Mobile (375x667)

## Troubleshooting

### Error "Database Connection Failed"

**Penyebab**: Konfigurasi database salah atau MySQL tidak berjalan

**Solusi**:
1. Cek apakah MySQL service berjalan:
   ```bash
   # Linux
   sudo systemctl status mysql
   
   # Windows (XAMPP)
   # Cek XAMPP Control Panel
   ```

2. Verifikasi konfigurasi di `config/database.php`
3. Test koneksi manual:
   ```bash
   mysql -u root -p -h localhost
   ```

### Error 500 Internal Server Error

**Penyebab**: Error PHP atau konfigurasi Apache

**Solusi**:
1. Cek error log Apache:
   ```bash
   # Linux
   tail -f /var/log/apache2/error.log
   
   # XAMPP
   # Cek xampp/apache/logs/error.log
   ```

2. Cek PHP error log:
   ```bash
   tail -f /var/log/php/error.log
   ```

3. Enable error reporting sementara:
   ```php
   // Tambahkan di awal index.php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

### Halaman Blank/White Screen

**Penyebab**: Fatal error PHP atau memory limit

**Solusi**:
1. Increase memory limit di `php.ini`:
   ```ini
   memory_limit = 256M
   ```

2. Cek syntax error:
   ```bash
   php -l index.php
   ```

3. Cek file permissions

### Chart/Grafik Tidak Muncul

**Penyebab**: JavaScript error atau CDN tidak dapat diakses

**Solusi**:
1. Cek console browser (F12)
2. Pastikan koneksi internet untuk CDN
3. Download Chart.js secara lokal jika perlu

### Session/Login Issues

**Penyebab**: Konfigurasi session PHP

**Solusi**:
1. Cek konfigurasi session di `php.ini`:
   ```ini
   session.save_path = "/tmp"
   session.gc_maxlifetime = 3600
   ```

2. Pastikan folder session writable:
   ```bash
   sudo chmod 777 /tmp
   ```

### Permission Denied Errors

**Penyebab**: File permissions salah

**Solusi**:
```bash
# Set correct ownership
sudo chown -R www-data:www-data /var/www/html/CatatYuk

# Set correct permissions
sudo chmod -R 755 /var/www/html/CatatYuk
sudo chmod -R 644 /var/www/html/CatatYuk/config/
```

### Slow Performance

**Penyebab**: Database tidak teroptimasi atau server resource terbatas

**Solusi**:
1. Optimize MySQL:
   ```sql
   OPTIMIZE TABLE transactions;
   OPTIMIZE TABLE debts_receivables;
   ```

2. Add database indexes:
   ```sql
   CREATE INDEX idx_transaction_date ON transactions(transaction_date);
   CREATE INDEX idx_user_id ON transactions(user_id);
   ```

3. Enable PHP OPcache:
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=4000
   ```

## Deployment ke Production

### 1. Security Checklist

- [ ] Ganti semua password default
- [ ] Enable HTTPS dengan SSL certificate
- [ ] Set `display_errors = Off` di php.ini
- [ ] Remove file test dan development
- [ ] Set proper file permissions
- [ ] Enable firewall
- [ ] Regular security updates

### 2. Performance Optimization

- [ ] Enable PHP OPcache
- [ ] Configure MySQL query cache
- [ ] Use CDN untuk static assets
- [ ] Enable Gzip compression
- [ ] Optimize images

### 3. Backup Strategy

- [ ] Setup automated database backup
- [ ] Backup application files
- [ ] Test restore procedures
- [ ] Monitor disk space

### 4. Monitoring

- [ ] Setup error logging
- [ ] Monitor server resources
- [ ] Setup uptime monitoring
- [ ] Configure alerts

---

Jika Anda mengalami masalah yang tidak tercakup dalam panduan ini, silakan buat issue di GitHub repository atau hubungi tim support.

