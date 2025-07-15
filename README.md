# CatatYuk - Aplikasi Pencatatan Keuangan UMKM by Keong Balap Dev.

**CatatYuk** adalah aplikasi web untuk pencatatan keuangan UMKM (Usaha Mikro, Kecil, dan Menengah) yang dirancang khusus untuk membantu pengusaha kecil mengelola cashflow mereka dengan mudah dan efisien. dibuat oleh Keong Balap Dev.

## 🚀 Fitur Utama

### 💰 Manajemen Keuangan
- **Pencatatan Transaksi**: Catat pemasukan dan pengeluaran dengan mudah
- **Kategori Fleksibel**: Organisir transaksi berdasarkan kategori yang dapat disesuaikan
- **Kalkulasi Otomatis**: Perhitungan otomatis total pemasukan, pengeluaran, dan laba rugi
- **Multi-User**: Dukungan untuk admin dan kasir dengan hak akses berbeda

### 📊 Visualisasi Data
- **Dashboard Interaktif**: Ringkasan keuangan dengan grafik dan chart
- **Tren Analisis**: Grafik tren pemasukan dan pengeluaran harian/bulanan
- **Breakdown Kategori**: Visualisasi pengeluaran berdasarkan kategori
- **Responsive Charts**: Grafik yang responsif menggunakan Chart.js

### 💳 Utang & Piutang
- **Manajemen Utang**: Catat dan kelola utang yang harus dibayar
- **Manajemen Piutang**: Catat dan kelola piutang dari pelanggan
- **Pengingat Jatuh Tempo**: Notifikasi otomatis untuk jatuh tempo pembayaran
- **Riwayat Pembayaran**: Tracking pembayaran cicilan utang/piutang

### 📋 Laporan & Export
- **Laporan Bulanan**: Generate laporan keuangan per bulan
- **Laporan Tahunan**: Ringkasan keuangan tahunan
- **Export PDF**: Export laporan ke format PDF
- **Export CSV**: Export data ke format CSV untuk analisis lebih lanjut

### 🔔 Sistem Notifikasi
- **Pengingat Otomatis**: Notifikasi jatuh tempo utang/piutang
- **Ringkasan Bulanan**: Notifikasi ringkasan keuangan setiap bulan
- **Real-time Updates**: Notifikasi real-time untuk aktivitas penting

## 🛠️ Teknologi yang Digunakan

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP 8.0+
- **Database**: MySQL 8.0+
- **Charts**: Chart.js
- **Icons**: Bootstrap Icons
- **Server**: Apache (XAMPP)

## 📋 Persyaratan Sistem

### Minimum Requirements
- **PHP**: 8.0 atau lebih baru
- **MySQL**: 8.0 atau lebih baru
- **Apache**: 2.4 atau lebih baru
- **RAM**: 512 MB
- **Storage**: 100 MB ruang kosong

### Recommended Requirements
- **PHP**: 8.1 atau lebih baru
- **MySQL**: 8.0 atau lebih baru
- **Apache**: 2.4 atau lebih baru
- **RAM**: 1 GB atau lebih
- **Storage**: 500 MB ruang kosong

## 🚀 Instalasi

### 1. Persiapan Environment

#### Menggunakan XAMPP (Recommended)
1. Download dan install [XAMPP](https://www.apachefriends.org/download.html)
2. Start Apache dan MySQL dari XAMPP Control Panel
3. Pastikan port 80 (Apache) dan 3306 (MySQL) tidak digunakan aplikasi lain

#### Menggunakan Server Manual
1. Install PHP 8.0+
2. Install MySQL 8.0+
3. Install Apache 2.4+
4. Konfigurasi virtual host jika diperlukan

### 2. Download dan Setup Aplikasi

```bash
# Clone atau download aplikasi ke folder htdocs XAMPP
cd C:\xampp\htdocs  # Windows
cd /opt/lampp/htdocs  # Linux

# Extract atau copy folder CatatYuk
```

### 3. Konfigurasi Database

1. Buka phpMyAdmin di `http://localhost/phpmyadmin`
2. Buat database baru dengan nama `catatYuk`
3. Import file SQL:
   ```sql
   # Jalankan file sql/database_design.sql
   ```

### 4. Konfigurasi Aplikasi

1. Edit file `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'catatYuk');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Kosongkan aja kalau tidak ada passwordnya
   ```

2. Set permission folder (Linux/Mac):
   ```bash
   chmod 755 CatatYuk/
   chmod 644 CatatYuk/config/database.php
   ```

### 5. Akses Aplikasi

1. Buka browser dan akses `http://localhost/CatatYuk`
2. Login dengan akun default:
   - **Admin**: admin / admin123

## 👥 Panduan Penggunaan

### Login dan Dashboard

1. **Login**: Masukkan email dan password untuk mengakses aplikasi
2. **Dashboard**: Lihat ringkasan keuangan, grafik tren, dan notifikasi penting
3. **Navigasi**: Gunakan menu sidebar untuk mengakses fitur-fitur utama

### Mengelola Transaksi

#### Menambah Transaksi Baru
1. Klik menu **"Transaksi"** → **"Tambah Transaksi"**
2. Pilih jenis transaksi (Pemasukan/Pengeluaran)
3. Pilih kategori atau buat kategori baru
4. Isi jumlah, tanggal, dan deskripsi
5. Klik **"Simpan"** untuk menyimpan transaksi

#### Mengedit Transaksi
1. Buka halaman **"Daftar Transaksi"**
2. Klik tombol **"Edit"** pada transaksi yang ingin diubah
3. Ubah data yang diperlukan
4. Klik **"Update"** untuk menyimpan perubahan

#### Menghapus Transaksi
1. Buka halaman **"Daftar Transaksi"**
2. Klik tombol **"Hapus"** pada transaksi yang ingin dihapus
3. Konfirmasi penghapusan

### Mengelola Utang & Piutang

#### Menambah Utang/Piutang
1. Klik menu **"Utang & Piutang"** → **"Tambah Utang/Piutang"**
2. Pilih jenis (Utang/Piutang)
3. Isi nama kontak, jumlah, dan tanggal jatuh tempo
4. Tambahkan deskripsi untuk konteks
5. Klik **"Simpan"** untuk menyimpan data

#### Mencatat Pembayaran
1. Buka halaman **"Daftar Utang & Piutang"**
2. Klik tombol **"Bayar"** pada item yang ingin dibayar
3. Isi jumlah pembayaran dan tanggal
4. Tambahkan catatan jika diperlukan
5. Klik **"Simpan Pembayaran"**

### Melihat Laporan

#### Laporan Bulanan
1. Klik menu **"Laporan"**
2. Pilih **"Laporan Bulanan"** dan tentukan bulan/tahun
3. Klik **"Tampilkan"** untuk melihat laporan
4. Gunakan tombol **"Export PDF"** untuk download laporan

#### Laporan Custom
1. Pilih **"Laporan Custom"** di halaman laporan
2. Tentukan rentang tanggal yang diinginkan
3. Klik **"Tampilkan"** untuk generate laporan

### Mengelola Notifikasi

1. Klik ikon **"Bell"** di header untuk melihat notifikasi
2. Klik notifikasi untuk menandai sebagai dibaca
3. Gunakan halaman **"Notifikasi"** untuk manajemen lengkap
4. Set pengingat otomatis untuk jatuh tempo utang/piutang

## 🔧 Konfigurasi Lanjutan

### Setup Cron Job (Opsional)

Untuk notifikasi otomatis, setup cron job:

```bash
# Edit crontab
crontab -e

# Tambahkan baris berikut untuk menjalankan setiap hari jam 9 pagi
0 9 * * * /usr/bin/php /path/to/CatatYuk/cron/generate_notifications.php
```

### Kustomisasi Tampilan

1. Edit file `assets/css/style.css` untuk mengubah tema
2. Ganti logo di `assets/images/logo.png`
3. Ubah nama aplikasi di `config/app.php`

### Backup Database

```bash
# Backup database
mysqldump -u root -p catatYuk > backup_catatYuk.sql

# Restore database
mysql -u root -p catatYuk < backup_catatYuk.sql
```

## 🔒 Keamanan

### Hak Akses User

- **Admin**: Akses penuh ke semua fitur dan data
- **Kasir**: Akses terbatas, hanya bisa mengelola transaksi dan melihat laporan

### Best Practices

1. **Ganti Password Default**: Segera ganti password default setelah instalasi
2. **Update Berkala**: Selalu update ke versi terbaru
3. **Backup Rutin**: Lakukan backup database secara berkala
4. **HTTPS**: Gunakan HTTPS untuk deployment production
5. **Firewall**: Konfigurasi firewall untuk membatasi akses

## 🐛 Troubleshooting

### Masalah Umum

#### Error "Database Connection Failed"
- Pastikan MySQL service berjalan
- Cek konfigurasi database di `config/database.php`
- Pastikan database `catatYuk` sudah dibuat

#### Halaman Blank/Error 500
- Cek error log Apache di `xampp/apache/logs/error.log`
- Pastikan PHP version 8.0+
- Cek permission file dan folder

#### Chart Tidak Muncul
- Pastikan koneksi internet untuk load Chart.js CDN
- Cek console browser untuk error JavaScript
- Clear browser cache

#### Notifikasi Tidak Muncul
- Pastikan cron job sudah dikonfigurasi
- Cek log file di `logs/cron_notifications.log`
- Pastikan tanggal server sudah benar

### Error Codes

| Code | Deskripsi | Solusi |
|------|-----------|---------|
| E001 | Database connection error | Cek konfigurasi database |
| E002 | Invalid user credentials | Cek username/password |
| E003 | Permission denied | Cek hak akses user |
| E004 | File upload error | Cek permission folder uploads |
| E005 | Session expired | Login ulang |

## 📚 API Documentation

### Authentication Endpoints

```php
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/check
```

### Transaction Endpoints

```php
GET    /api/transactions          # Get all transactions
POST   /api/transactions          # Create new transaction
GET    /api/transactions/{id}     # Get specific transaction
PUT    /api/transactions/{id}     # Update transaction
DELETE /api/transactions/{id}     # Delete transaction
```

### Notification Endpoints

```php
GET  /api/notifications           # Get user notifications
POST /api/notifications/mark-read # Mark notification as read
POST /api/notifications/mark-all  # Mark all as read
```

## 🤝 Kontribusi

Kami menyambut kontribusi dari komunitas! Berikut cara berkontribusi:

### Melaporkan Bug

1. Cek apakah bug sudah dilaporkan di Issues
2. Buat issue baru dengan template bug report
3. Sertakan informasi environment dan langkah reproduksi

### Mengusulkan Fitur

1. Buat issue dengan template feature request
2. Jelaskan use case dan benefit fitur tersebut
3. Diskusikan dengan maintainer sebelum implementasi

### Pull Request

1. Fork repository
2. Buat branch untuk fitur/bugfix
3. Commit dengan pesan yang jelas
4. Buat pull request dengan deskripsi lengkap


## 📞 Support

### Dokumentasi
- [User Manual](docs/user_manual.md)
- [Developer Guide](docs/developer_guide.md)
- [API Documentation](docs/api_documentation.md)

### Kontak
- **Email**: dimastirtajasa10@gmail.com
- **Website**: [Coming Soon](http://127.0.0.1)
- **GitHub**: https://github.com/stashkill/CatatYuk-UMKM 

### Community
- **Discord**: [Coming Soon](http://127.0.0.1)
- **Telegram**: [Coming Soon](http://127.0.0.1)

---

**CatatYuk** - Solusi Pencatatan Keuangan UMKM yang Mudah dan Efisien

*Dibuat dengan ❤️ untuk UMKM Indonesia*

