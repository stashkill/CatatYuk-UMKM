-- Database: catatYuk
-- Aplikasi Pencatatan Keuangan UMKM (Cashflow Tracker)

CREATE DATABASE IF NOT EXISTS catatYuk;
USE catatYuk;

-- Tabel Users (Admin dan Kasir)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Kategori Transaksi
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Transaksi Keuangan
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    reference_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

-- Tabel Utang Piutang
CREATE TABLE debts_receivables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('debt', 'receivable') NOT NULL,
    contact_name VARCHAR(100) NOT NULL,
    contact_phone VARCHAR(20),
    amount DECIMAL(15,2) NOT NULL,
    remaining_amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    due_date DATE,
    status ENUM('pending', 'partial', 'paid', 'overdue') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Pembayaran Utang Piutang
CREATE TABLE debt_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    debt_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (debt_id) REFERENCES debts_receivables(id) ON DELETE CASCADE
);

-- Tabel Notifikasi/Pengingat
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type ENUM('debt_reminder', 'receivable_reminder', 'general') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    related_id INT, -- ID dari debt/receivable yang terkait
    is_read BOOLEAN DEFAULT FALSE,
    scheduled_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Settings Aplikasi
CREATE TABLE app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Activity Logs (untuk audit trail)
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert Data Default

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@catatYuk.com', 'admin'),
('kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir 1', 'kasir1@catatYuk.com', 'kasir');

-- Insert default categories
INSERT INTO categories (name, type, description) VALUES 
('Penjualan Produk', 'income', 'Pendapatan dari penjualan produk utama'),
('Penjualan Jasa', 'income', 'Pendapatan dari penyediaan jasa'),
('Pendapatan Lain', 'income', 'Pendapatan dari sumber lain'),
('Pembelian Bahan Baku', 'expense', 'Pengeluaran untuk pembelian bahan baku'),
('Gaji Karyawan', 'expense', 'Pengeluaran untuk gaji dan tunjangan karyawan'),
('Sewa Tempat', 'expense', 'Pengeluaran untuk sewa tempat usaha'),
('Listrik & Air', 'expense', 'Pengeluaran untuk utilitas'),
('Transportasi', 'expense', 'Pengeluaran untuk transportasi'),
('Pemasaran', 'expense', 'Pengeluaran untuk kegiatan pemasaran'),
('Operasional Lain', 'expense', 'Pengeluaran operasional lainnya');

-- Insert default settings
INSERT INTO app_settings (setting_key, setting_value, description) VALUES 
('company_name', 'UMKM Saya', 'Nama perusahaan/UMKM'),
('company_address', 'Alamat Perusahaan', 'Alamat perusahaan'),
('company_phone', '08123456789', 'Nomor telepon perusahaan'),
('notification_days_before', '3', 'Hari sebelum jatuh tempo untuk notifikasi'),
('currency_symbol', 'Rp', 'Simbol mata uang');

-- Create indexes for better performance
CREATE INDEX idx_transactions_date ON transactions(transaction_date);
CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_debts_due_date ON debts_receivables(due_date);
CREATE INDEX idx_debts_status ON debts_receivables(status);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);

