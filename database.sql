-- Database: rawinda_finance
CREATE DATABASE IF NOT EXISTS rawinda_finance;
USE rawinda_finance;

-- Table: users (admin dan warga)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'warga') NOT NULL,
    no_rumah VARCHAR(10),
    status_rumah ENUM('dihuni', 'kosong', 'sewa') DEFAULT 'dihuni',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: kategori_kas
CREATE TABLE kategori_kas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(50) NOT NULL,
    saldo DECIMAL(15,2) DEFAULT 0.00,
    warna VARCHAR(20),
    biaya_iuran DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: transaksi
CREATE TABLE transaksi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tanggal DATE NOT NULL,
    keterangan TEXT,
    kategori_id INT,
    jenis ENUM('masuk', 'keluar') NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    bukti_file VARCHAR(255),
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    warga_id INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori_kas(id) ON DELETE CASCADE,
    FOREIGN KEY (warga_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: pembayaran_iuran
CREATE TABLE pembayaran_iuran (
    id INT PRIMARY KEY AUTO_INCREMENT,
    warga_id INT NOT NULL,
    bulan INT NOT NULL,
    tahun INT NOT NULL,
    nominal DECIMAL(10,2) NOT NULL,
    status ENUM('lunas', 'belum', 'nunggak') DEFAULT 'belum',
    tanggal_bayar DATE,
    bukti_bayar VARCHAR(255),
    verified_by INT,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (warga_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_iuran (warga_id, bulan, tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: settings
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: logs (untuk auditing)
CREATE TABLE logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin)
INSERT INTO users (username, password, nama_lengkap, role) VALUES 
('admin', 'admin', 'Admin Utama', 'admin'),
('bendahara', '1234', 'Ibu Bendahara', 'admin');

-- Insert default warga user (password: warga)
INSERT INTO users (username, password, nama_lengkap, role, no_rumah, status_rumah) VALUES 
('warga', 'warga', 'Budi Santoso', 'warga', 'A1-05', 'dihuni'),
('warga_a105', '1234', 'Budi Santoso', 'warga', 'A1-05', 'dihuni'),
('warga_b210', '1234', 'Siti Aminah', 'warga', 'B2-10', 'dihuni'),
('warga_c301', '1234', 'Pak Doni', 'warga', 'C3-01', 'dihuni'),
('warga_d412', '1234', 'Bu Ratna', 'warga', 'D4-12', 'sewa'),
('warga_e508', '1234', 'Pak Joko', 'warga', 'E5-08', 'dihuni');

-- Insert kategori kas
INSERT INTO kategori_kas (nama_kategori, saldo, warna, biaya_iuran) VALUES 
('Kas Perumahan', 10750000.00, 'blue', 50000.00),
('Dana Sosial', 3200000.00, 'green', 10000.00),
('Kas RT', 1500000.00, 'purple', 10000.00);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('bank_name', 'BCA'),
('bank_number', '1234567890'),
('bank_holder', 'Paguyuban Rawinda'),
('app_name', 'Sistem Keuangan Rawinda Pratama'),
('app_year', '2026');

-- Insert sample transactions
INSERT INTO transaksi (tanggal, keterangan, kategori_id, jenis, nominal, status, created_by) VALUES 
('2026-02-01', 'Iuran Warga Budi Santoso', 1, 'masuk', 70000.00, 'verified', 1),
('2026-01-30', 'Perbaikan Lampu Jalan Blok C', 1, 'keluar', 150000.00, 'verified', 1),
('2026-02-02', 'Honor Satpam & Kebersihan', 1, 'keluar', 3000000.00, 'verified', 1),
('2026-02-01', 'Santunan Duka (Blok B3)', 2, 'keluar', 500000.00, 'verified', 1),
('2026-02-01', 'Konsumsi Rapat Pengurus', 3, 'keluar', 150000.00, 'verified', 1);

-- Insert sample pembayaran
INSERT INTO pembayaran_iuran (warga_id, bulan, tahun, nominal, status, tanggal_bayar) VALUES 
(3, 1, 2026, 70000.00, 'lunas', '2026-01-15'),
(3, 2, 2026, 70000.00, 'lunas', '2026-02-01'),
(4, 1, 2026, 70000.00, 'lunas', '2026-01-15'),
(4, 2, 2026, 70000.00, 'nunggak', NULL),
(5, 1, 2026, 70000.00, 'lunas', '2026-01-20'),
(5, 2, 2026, 70000.00, 'lunas', '2026-02-02'),
(6, 1, 2026, 70000.00, 'lunas', '2026-01-18'),
(6, 2, 2026, 70000.00, 'belum', NULL),
(7, 1, 2026, 70000.00, 'nunggak', NULL),
(7, 2, 2026, 70000.00, 'nunggak', NULL);

-- Create indexes for better performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_transaksi_tanggal ON transaksi(tanggal);
CREATE INDEX idx_transaksi_status ON transaksi(status);
CREATE INDEX idx_pembayaran_iuran_warga ON pembayaran_iuran(warga_id);
CREATE INDEX idx_pembayaran_iuran_status ON pembayaran_iuran(status);
CREATE INDEX idx_logs_user_id ON logs(user_id);