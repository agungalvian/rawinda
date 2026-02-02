<?php
require_once '../includes/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $tanggal = $_POST['tanggal'];
    $keterangan = sanitize($_POST['keterangan']);
    $kategori_id = intval($_POST['kategori_id']);
    $jenis = $action; // 'income' or 'expense'
    $nominal = floatval(str_replace(['.', ','], '', $_POST['nominal']));
    $warga_id = !empty($_POST['warga_id']) ? intval($_POST['warga_id']) : NULL;
    
    // Convert action to jenis
    $jenis_db = $action == 'income' ? 'masuk' : 'keluar';
    
    $sql = "INSERT INTO transaksi (tanggal, keterangan, kategori_id, jenis, nominal, warga_id, created_by, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'verified')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisdis", $tanggal, $keterangan, $kategori_id, $jenis_db, $nominal, $warga_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Update category balance
        $update_sql = "UPDATE kategori_kas SET saldo = saldo " . ($jenis_db == 'masuk' ? '+' : '-') . " ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("di", $nominal, $kategori_id);
        $update_stmt->execute();
        
        $_SESSION['success'] = 'Transaksi berhasil dicatat!';
    } else {
        $_SESSION['error'] = 'Gagal mencatat transaksi!';
    }
    
    header('Location: ../admin/transaksi.php');
    exit();
} else {
    header('Location: ../admin/transaksi.php');
    exit();
}
?>