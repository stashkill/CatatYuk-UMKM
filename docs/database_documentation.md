# Dokumentasi Database CatatYuk

## Overview
Database CatatYuk dirancang untuk mendukung aplikasi pencatatan keuangan UMKM dengan fitur lengkap termasuk manajemen user, transaksi keuangan, utang piutang, dan sistem notifikasi.

## Struktur Tabel

### 1. Tabel `users`
Menyimpan data pengguna aplikasi (admin dan kasir).

**Kolom:**
- `id`: Primary key, auto increment
- `username`: Username unik untuk login
- `password`: Password terenkripsi (bcrypt)
- `full_name`: Nama lengkap pengguna
- `email`: Email pengguna
- `role`: Peran pengguna (admin/kasir)
- `status`: Status aktif/tidak aktif
- `created_at`, `updated_at`: Timestamp

**Default Users:**
- Admin: username `admin`, password `admin123`
- Kasir: username `kasir1`, password `admin123`

### 2. Tabel `categories`
Menyimpan kategori transaksi untuk klasifikasi pemasukan dan pengeluaran.

**Kolom:**
- `id`: Primary key
- `name`: Nama kategori
- `type`: Jenis (income/expense)
- `description`: Deskripsi kategori
- `created_at`: Timestamp

**Kategori Default:**
- **Income:** Penjualan Produk, Penjualan Jasa, Pendapatan Lain
- **Expense:** Pembelian Bahan Baku, Gaji Karyawan, Sewa Tempat, Listrik & Air, Transportasi, Pemasaran, Operasional Lain

### 3. Tabel `transactions`
Menyimpan semua transaksi keuangan (pemasukan dan pengeluaran).

**Kolom:**
- `id`: Primary key
- `user_id`: Foreign key ke tabel users
- `category_id`: Foreign key ke tabel categories
- `type`: Jenis transaksi (income/expense)
- `amount`: Jumlah uang
- `description`: Deskripsi transaksi
- `transaction_date`: Tanggal transaksi
- `reference_number`: Nomor referensi (opsional)
- `notes`: Catatan tambahan
- `created_at`, `updated_at`: Timestamp

### 4. Tabel `debts_receivables`
Menyimpan data utang dan piutang.

**Kolom:**
- `id`: Primary key
- `user_id`: Foreign key ke tabel users
- `type`: Jenis (debt/receivable)
- `contact_name`: Nama kontak
- `contact_phone`: Nomor telepon kontak
- `amount`: Jumlah total
- `remaining_amount`: Sisa yang belum dibayar
- `description`: Deskripsi
- `due_date`: Tanggal jatuh tempo
- `status`: Status (pending/partial/paid/overdue)
- `created_at`, `updated_at`: Timestamp

### 5. Tabel `debt_payments`
Menyimpan riwayat pembayaran utang/piutang.

**Kolom:**
- `id`: Primary key
- `debt_id`: Foreign key ke tabel debts_receivables
- `amount`: Jumlah pembayaran
- `payment_date`: Tanggal pembayaran
- `notes`: Catatan
- `created_at`: Timestamp

### 6. Tabel `notifications`
Menyimpan notifikasi dan pengingat.

**Kolom:**
- `id`: Primary key
- `user_id`: Foreign key ke tabel users
- `type`: Jenis notifikasi
- `title`: Judul notifikasi
- `message`: Isi pesan
- `related_id`: ID terkait (untuk debt/receivable)
- `is_read`: Status sudah dibaca
- `scheduled_date`: Tanggal terjadwal
- `created_at`: Timestamp

### 7. Tabel `app_settings`
Menyimpan pengaturan aplikasi.

**Kolom:**
- `id`: Primary key
- `setting_key`: Kunci pengaturan
- `setting_value`: Nilai pengaturan
- `description`: Deskripsi
- `updated_at`: Timestamp

## Relasi Antar Tabel

1. **users → transactions**: One-to-Many (satu user bisa memiliki banyak transaksi)
2. **categories → transactions**: One-to-Many (satu kategori bisa digunakan banyak transaksi)
3. **users → debts_receivables**: One-to-Many (satu user bisa memiliki banyak utang/piutang)
4. **debts_receivables → debt_payments**: One-to-Many (satu utang/piutang bisa memiliki banyak pembayaran)
5. **users → notifications**: One-to-Many (satu user bisa memiliki banyak notifikasi)

## Fitur yang Didukung

1. **Autentikasi Multi-Role**: Admin dan Kasir dengan hak akses berbeda
2. **Pencatatan Transaksi**: Pemasukan dan pengeluaran dengan kategori
3. **Manajemen Utang Piutang**: Tracking utang dan piutang dengan pembayaran bertahap
4. **Sistem Notifikasi**: Pengingat jatuh tempo dan notifikasi lainnya
5. **Laporan Keuangan**: Data terstruktur untuk generate laporan
6. **Audit Trail**: Timestamp pada semua tabel untuk tracking perubahan

## Index untuk Performa

- `idx_transactions_date`: Index pada tanggal transaksi
- `idx_transactions_type`: Index pada jenis transaksi
- `idx_debts_due_date`: Index pada tanggal jatuh tempo
- `idx_debts_status`: Index pada status utang/piutang
- `idx_notifications_user`: Index pada user notifikasi
- `idx_notifications_read`: Index pada status baca notifikasi

