# Sistem Keuangan Rawinda Pratama

Sistem manajemen keuangan untuk komplek perumahan dengan PHP, MySQL, dan Tailwind CSS.

## Fitur Utama

### 1. Untuk Warga
- Dashboard dengan informasi keuangan
- Laporan keuangan transparan
- Pembayaran iuran online
- Riwayat pembayaran pribadi
- Notifikasi tagihan

### 2. Untuk Admin
- Dashboard admin lengkap
- Manajemen data warga
- Input pemasukan & pengeluaran
- Verifikasi pembayaran
- Laporan keuangan detail
- Rekap tahunan pembayaran
- Pengaturan sistem

## Instalasi

### 1. Prasyarat
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (Apache/Nginx)
- Composer (opsional)

### 2. Langkah Instalasi

```bash
# 1. Clone atau extract proyek
unzip rawinda_pratama.zip -d /var/www/html/

# 2. Buat database
mysql -u root -p < database.sql

# 3. Konfigurasi database
# Edit file includes/config.php
# Sesuaikan dengan kredensial database Anda

# 4. Set permissions
chmod -R 755 assets/uploads/
chown -R www-data:www-data assets/uploads/

# 5. Akses aplikasi
# Buka browser ke http://localhost/rawinda_pratama