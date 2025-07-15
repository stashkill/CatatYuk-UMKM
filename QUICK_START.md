# Quick Start Guide - CatatYuk By Keong Balap Dev

Panduan cepat untuk menjalankan aplikasi CatatYuk dalam 5 menit aja!

## 🚀 Instalasi Cepat dengan XAMPP

### 1. Download XAMPP
- Download XAMPP dari [https://www.apachefriends.org/download.html](https://www.apachefriends.org/download.html)
- Pilih versi PHP 8.0+
- Install dengan pengaturan default

### 2. Setup Aplikasi
1. **Copy folder CatatYuk** ke `C:\xampp\htdocs\` (Windows) atau `/opt/lampp/htdocs/` (Linux)
2. **Start XAMPP** dan jalankan Apache + MySQL
3. **Buka phpMyAdmin** di `http://localhost/phpmyadmin`
4. **Buat database** dengan nama `catatYuk`
5. **Import database** dari file `sql/database_design.sql`

### 3. Konfigurasi Database
Edit file `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'catatYuk');
define('DB_USER', 'root');
define('DB_PASS', ''); // Kosong untuk XAMPP default
```

### 4. Akses Aplikasi
- Buka browser: `http://localhost/CatatYuk`
- Login dengan:
  - **Admin**: admin / admin123

## ✅ Checklist Instalasi

- [ ] XAMPP terinstall dan berjalan
- [ ] Folder CatatYuk di htdocs
- [ ] Database catatYuk sudah dibuat
- [ ] File SQL sudah diimport
- [ ] Konfigurasi database sudah benar
- [ ] Aplikasi bisa diakses di browser
- [ ] Login berhasil dengan akun default

## 🎯 Langkah Pertama Setelah Login

### 1. Ganti Password Default
- Masuk ke menu **Settings** → **Profile**
- Ganti password admin dan kasir

### 2. Setup Kategori
- Buka menu **Transaksi** → **Kategori**
- Tambah kategori sesuai bisnis Anda:
  - **Pemasukan**: Penjualan, Jasa, Lain-lain
  - **Pengeluaran**: Bahan Baku, Operasional, Gaji, dll

### 3. Input Transaksi Pertama
- Klik **Transaksi** → **Tambah Transaksi**
- Pilih jenis (Pemasukan/Pengeluaran)
- Isi detail transaksi
- Simpan

### 4. Coba Fitur Utama
- **Dashboard**: Lihat ringkasan keuangan
- **Laporan**: Generate laporan bulanan
- **Utang/Piutang**: Catat utang atau piutang
- **Notifikasi**: Cek sistem pengingat

## 🔧 Troubleshooting Cepat

### Error "Database Connection Failed"
```bash
# Cek apakah MySQL berjalan di XAMPP Control Panel
# Pastikan database 'catatYuk' sudah dibuat
# Cek konfigurasi di config/database.php
```

### Halaman Blank/Error 500
```bash
# Cek error log di xampp/apache/logs/error.log
# Pastikan PHP version 8.0+
# Cek permission folder CatatYuk
```

### Chart Tidak Muncul
```bash
# Pastikan koneksi internet (untuk CDN Chart.js)
# Clear browser cache
# Cek console browser (F12) untuk error JavaScript
```

## 📱 Fitur Mobile

Aplikasi sudah responsive dan bisa diakses dari:
- **Laptop/PC (Windows, Linux, MacOS)**
- **Smartphone (Android,Ios, & More)**
- **Tablet**

Interface otomatis menyesuaikan ukuran layar!

## 🔒 Keamanan Dasar

### Setelah Instalasi:
1. **Ganti password default** segera
2. **Backup database** secara berkala
3. **Update XAMPP** ke versi terbaru
4. **Jangan expose** ke internet tanpa security tambahan

## 📞 Bantuan

Jika mengalami masalah:
1. Cek file `docs/INSTALLATION_GUIDE.md` untuk panduan detail
2. Lihat section Troubleshooting di README.md
3. Cek log error di `xampp/apache/logs/error.log`
4. Kontak email saya ke dimastirtajasa10@gmail.com

## 🎉 Selamat!

Aplikasi CatatYuk siap digunakan untuk mengelola keuangan UMKM kamu!

**Tips**: Mulai dengan mencatat transaksi harian, lalu eksplorasi fitur laporan dan analisis untuk insight bisnis yang lebih baik.

