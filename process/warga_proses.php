<?php
require_once '../includes/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $no_rumah = sanitize($_POST['no_rumah']);
    $nama_lengkap = sanitize($_POST['nama_lengkap']);
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $status_rumah = $_POST['status_rumah'];
    
    if ($id > 0) {
        // Update existing warga
        if (!empty($password)) {
            $sql = "UPDATE users SET no_rumah = ?, nama_lengkap = ?, username = ?, password = ?, status_rumah = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $no_rumah, $nama_lengkap, $username, $password, $status_rumah, $id);
        } else {
            $sql = "UPDATE users SET no_rumah = ?, nama_lengkap = ?, username = ?, status_rumah = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $no_rumah, $nama_lengkap, $username, $status_rumah, $id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Data warga berhasil diperbarui!';
        } else {
            $_SESSION['error'] = 'Gagal memperbarui data warga!';
        }
    } else {
        // Insert new warga
        $sql = "INSERT INTO users (no_rumah, nama_lengkap, username, password, role, status_rumah) 
                VALUES (?, ?, ?, ?, 'warga', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $no_rumah, $nama_lengkap, $username, $password, $status_rumah);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            
            // Create initial payment records for current year
            $current_year = date('Y');
            for ($month = 1; $month <= 12; $month++) {
                // Get total iuran
                $iuran_sql = "SELECT SUM(biaya_iuran) as total_iuran FROM kategori_kas";
                $iuran_result = $conn->query($iuran_sql);
                $total_iuran = $iuran_result->fetch_assoc()['total_iuran'];
                
                $payment_sql = "INSERT INTO pembayaran_iuran (warga_id, bulan, tahun, nominal, status) 
                               VALUES (?, ?, ?, ?, 'belum')
                               ON DUPLICATE KEY UPDATE warga_id = warga_id";
                $payment_stmt = $conn->prepare($payment_sql);
                $payment_stmt->bind_param("iiid", $new_id, $month, $current_year, $total_iuran);
                $payment_stmt->execute();
            }
            
            $_SESSION['success'] = 'Data warga berhasil ditambahkan!';
        } else {
            $_SESSION['error'] = 'Gagal menambahkan data warga!';
        }
    }
    
    header('Location: ../admin/warga.php');
    exit();
} else {
    header('Location: ../admin/warga.php');
    exit();
}
?>