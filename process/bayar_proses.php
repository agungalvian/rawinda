<?php
require_once '../includes/config.php';
requireWarga();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $tanggal_transfer = $_POST['tanggal_transfer'];
    $bulan_list = $_POST['bulan'] ?? [];
    $catatan = sanitize($_POST['catatan'] ?? '');
    
    if (empty($bulan_list)) {
        $_SESSION['error'] = 'Pilih minimal satu bulan untuk dibayar!';
        header('Location: ../warga/bayar.php');
        exit();
    }
    
    // Get total iuran per month
    $iuran_sql = "SELECT SUM(biaya_iuran) as total_iuran FROM kategori_kas";
    $iuran_result = $conn->query($iuran_sql);
    $total_iuran = $iuran_result->fetch_assoc()['total_iuran'];
    
    // Handle file upload
    $upload_dir = '../assets/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $bukti_filename = '';
    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === UPLOAD_OK) {
        $file = $_FILES['bukti_transfer'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($file_ext, $allowed_ext) && $file['size'] <= 2 * 1024 * 1024) {
            $bukti_filename = 'bukti_' . $user_id . '_' . time() . '.' . $file_ext;
            move_uploaded_file($file['tmp_name'], $upload_dir . $bukti_filename);
        }
    }
    
    // Process each selected month
    foreach ($bulan_list as $periode) {
        list($bulan, $tahun) = explode('-', $periode);
        
        // Create transaction record
        $sql = "INSERT INTO transaksi (tanggal, keterangan, kategori_id, jenis, nominal, warga_id, status, bukti_file) 
                VALUES (?, 'Pembayaran Iuran Warga', 1, 'masuk', ?, ?, 'pending', ?)";
        $stmt = $conn->prepare($sql);
        $keterangan = "Pembayaran iuran " . date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun)) . ($catatan ? " - $catatan" : "");
        $stmt->bind_param("siis", $tanggal_transfer, $total_iuran, $user_id, $bukti_filename);
        
        if ($stmt->execute()) {
            // Update payment record
            $update_sql = "INSERT INTO pembayaran_iuran (warga_id, bulan, tahun, nominal, status, tanggal_bayar, bukti_bayar) 
                          VALUES (?, ?, ?, ?, 'belum', ?, ?)
                          ON DUPLICATE KEY UPDATE 
                          status = 'belum',
                          tanggal_bayar = ?,
                          bukti_bayar = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("iiissss", $user_id, $bulan, $tahun, $total_iuran, $tanggal_transfer, $bukti_filename, $tanggal_transfer, $bukti_filename);
            $update_stmt->execute();
        }
    }
    
    $_SESSION['success'] = 'Bukti pembayaran berhasil dikirim! Menunggu verifikasi admin.';
    header('Location: ../warga/dashboard.php');
    exit();
} else {
    header('Location: ../warga/bayar.php');
    exit();
}
?>