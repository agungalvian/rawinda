<?php
require_once '../includes/config.php';
requireWarga();

$page_title = "Dashboard Warga";

$user_id = $_SESSION['user_id'];
$current_month = date('m');
$current_year = date('Y');

// Get tagihan warga
$tagihan_sql = "SELECT pi.*, CONCAT(pi.bulan, '/', pi.tahun) as periode 
                FROM pembayaran_iuran pi 
                WHERE pi.warga_id = ? 
                AND (pi.status = 'belum' OR pi.status = 'nunggak')
                ORDER BY pi.tahun DESC, pi.bulan DESC";
$stmt = $conn->prepare($tagihan_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tagihan_result = $stmt->get_result();

$total_tagihan = 0;
$tagihan_list = [];
while ($row = $tagihan_result->fetch_assoc()) {
    $total_tagihan += $row['nominal'];
    $tagihan_list[] = $row;
}

// Get kategori kas
$kategori_sql = "SELECT * FROM kategori_kas";
$kategori_result = $conn->query($kategori_sql);
$total_kas = 0;
$kategori_data = [];
while ($row = $kategori_result->fetch_assoc()) {
    $total_kas += $row['saldo'];
    $kategori_data[] = $row;
}

// Get settings for bank info
$settings_sql = "SELECT * FROM settings WHERE setting_key IN ('bank_name', 'bank_number', 'bank_holder')";
$settings_result = $conn->query($settings_sql);
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get recent transactions
$mutasi_sql = "SELECT t.*, k.nama_kategori, k.warna 
               FROM transaksi t 
               LEFT JOIN kategori_kas k ON t.kategori_id = k.id 
               WHERE t.status = 'verified' 
               ORDER BY t.tanggal DESC LIMIT 5";
$mutasi_result = $conn->query($mutasi_sql);
?>

<?php include '../includes/header.php'; ?>

<div class="flex-1 flex flex-col h-full bg-gray-50">
    <?php include '../includes/warga_navbar.php'; ?>
    
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-50">
        <div class="max-w-5xl mx-auto">
            
            <?php if ($total_tagihan > 0): ?>
            <!-- Status Tagihan -->
            <div class="bg-white rounded-xl shadow-sm border-l-4 border-red-500 overflow-hidden mb-6">
                <div class="p-6 flex flex-col sm:flex-row justify-between items-center bg-red-50/50">
                    <div class="mb-4 sm:mb-0">
                        <p class="text-sm text-red-600 font-bold flex items-center">
                            <i class="fa-solid fa-triangle-exclamation mr-2"></i> Tagihan Belum Dibayar
                        </p>
                        <p class="text-3xl font-extrabold text-gray-800 mt-2"><?php echo formatRupiah($total_tagihan); ?></p>
                        <p class="text-xs text-gray-500 mt-1">
                            <?php echo count($tagihan_list); ?> bulan menunggak
                        </p>
                    </div>
                    <a href="bayar.php" class="bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-8 rounded-lg shadow-md transition transform hover:scale-105">
                        <i class="fa-solid fa-upload mr-2"></i> Bayar Sekarang
                    </a>
                </div>
                <?php if (count($tagihan_list) > 0): ?>
                <div class="px-6 py-4 bg-red-50 border-t border-red-100">
                    <p class="text-sm text-red-700 font-bold mb-2">Detail Tagihan:</p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($tagihan_list as $tagihan): ?>
                        <span class="px-3 py-1 bg-red-100 text-red-800 text-xs rounded-full">
                            <?php echo date('F Y', mktime(0, 0, 0, $tagihan['bulan'], 1, $tagihan['tahun'])); ?>
                            (<?php echo formatRupiah($tagihan['nominal']); ?>)
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Welcome Card -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 text-white shadow-lg mb-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold">Selamat Datang, <?php echo $_SESSION['nama_lengkap']; ?>!</h2>
                        <p class="text-blue-100 mt-1">No. Rumah: <?php echo $_SESSION['no_rumah'] ?? '-'; ?></p>
                    </div>
                    <div class="mt-4 md:mt-0 text-center">
                        <p class="text-sm text-blue-200">Status Pembayaran</p>
                        <p class="text-3xl font-bold">
                            <?php 
                            $lunas_sql = "SELECT COUNT(*) as count FROM pembayaran_iuran WHERE warga_id = ? AND status = 'lunas'";
                            $stmt = $conn->prepare($lunas_sql);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $lunas_count = $stmt->get_result()->fetch_assoc()['count'];
                            echo $lunas_count . ' bulan';
                            ?>
                        </p>
                        <p class="text-xs text-blue-200">Lunas</p>
                    </div>
                </div>
            </div>

            <!-- Transparansi Cards -->
            <div class="mb-6">
                <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
                    <i class="fa-solid fa-chart-simple mr-2 text-blue-600"></i> Transparansi Dana Komplek
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Saldo -->
                    <div class="bg-blue-600 rounded-xl p-5 text-white shadow-lg relative overflow-hidden">
                        <div class="relative z-10">
                            <p class="text-blue-100 text-xs uppercase font-bold">Total Saldo Kas</p>
                            <p class="text-2xl font-bold mt-1"><?php echo formatRupiah($total_kas); ?></p>
                            <div class="mt-4 text-xs bg-blue-800 bg-opacity-50 inline-block px-2 py-1 rounded">
                                Update terakhir: <?php echo date('d/m/Y'); ?>
                            </div>
                        </div>
                        <i class="fa-solid fa-wallet absolute right-4 bottom-4 text-white text-6xl opacity-20"></i>
                    </div>
                    
                    <?php foreach ($kategori_data as $kategori): ?>
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition">
                        <p class="text-gray-500 text-xs uppercase font-bold"><?php echo $kategori['nama_kategori']; ?></p>
                        <p class="text-xl font-bold text-gray-800 mt-1"><?php echo formatRupiah($kategori['saldo']); ?></p>
                        <p class="text-xs text-gray-500 mt-1">Iuran: <?php echo formatRupiah($kategori['biaya_iuran']); ?>/bulan</p>
                        <div class="w-full bg-gray-100 h-2 rounded-full mt-3">
                            <?php 
                            $percentage = $kategori['saldo'] > 1000000 ? 100 : ($kategori['saldo'] / 1000000) * 100;
                            ?>
                            <div class="bg-<?php echo $kategori['warna']; ?>-500 h-2 rounded-full" style="width: <?php echo min($percentage, 100); ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Bank Information -->
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-5 text-white shadow-lg mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg mb-1"><i class="fa-solid fa-building-columns mr-2"></i>Informasi Rekening</h3>
                        <p class="text-green-100">Untuk pembayaran iuran warga</p>
                    </div>
                    <div class="text-right">
                        <p class="font-mono text-xl font-bold"><?php echo $settings['bank_number'] ?? '1234567890'; ?></p>
                        <p class="text-sm text-green-200"><?php echo $settings['bank_name'] ?? 'BCA'; ?></p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-green-400">
                    <p class="text-sm">a.n <span class="font-bold"><?php echo $settings['bank_holder'] ?? 'Paguyuban Rawinda'; ?></span></p>
                </div>
            </div>

            <!-- Mutasi Terakhir -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 font-bold text-gray-700 flex justify-between items-center">
                    <span>Mutasi Terakhir</span>
                    <a href="laporan.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Lihat Detail →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                            <tr>
                                <th class="px-6 py-3">Tanggal</th>
                                <th class="px-6 py-3">Keterangan</th>
                                <th class="px-6 py-3">Kategori</th>
                                <th class="px-6 py-3 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if ($mutasi_result->num_rows > 0): ?>
                                <?php while ($mutasi = $mutasi_result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4"><?php echo date('d/m/Y', strtotime($mutasi['tanggal'])); ?></td>
                                    <td class="px-6 py-4 font-medium text-gray-800"><?php echo $mutasi['keterangan']; ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full bg-<?php echo $mutasi['warna']; ?>-100 text-<?php echo $mutasi['warna']; ?>-800">
                                            <?php echo $mutasi['nama_kategori']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold <?php echo $mutasi['jenis'] == 'masuk' ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $mutasi['jenis'] == 'masuk' ? '+' : '-'; ?> <?php echo formatRupiah($mutasi['nominal']); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fa-solid fa-inbox text-3xl mb-2 block"></i>
                                        Belum ada transaksi
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>