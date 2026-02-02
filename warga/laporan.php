<?php
require_once '../includes/config.php';
requireWarga();

$page_title = "Laporan Keuangan Warga";

$user_id = $_SESSION['user_id'];
$current_month = date('m');
$current_year = date('Y');

// Get monthly report data
$report_sql = "SELECT 
                k.nama_kategori,
                k.warna,
                k.biaya_iuran,
                k.saldo,
                COALESCE(SUM(CASE WHEN t.jenis = 'masuk' THEN t.nominal ELSE 0 END), 0) as total_masuk,
                COALESCE(SUM(CASE WHEN t.jenis = 'keluar' THEN t.nominal ELSE 0 END), 0) as total_keluar
               FROM kategori_kas k
               LEFT JOIN transaksi t ON k.id = t.kategori_id 
                AND MONTH(t.tanggal) = ? 
                AND YEAR(t.tanggal) = ?
                AND t.status = 'verified'
               GROUP BY k.id";

$stmt = $conn->prepare($report_sql);
$stmt->bind_param("ii", $current_month, $current_year);
$stmt->execute();
$report_result = $stmt->get_result();

// Get user payment status
$payment_sql = "SELECT 
                bulan,
                tahun,
                status,
                nominal,
                tanggal_bayar
               FROM pembayaran_iuran 
               WHERE warga_id = ? 
               ORDER BY tahun DESC, bulan DESC";
$stmt = $conn->prepare($payment_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payment_result = $stmt->get_result();

// Calculate totals
$total_masuk = 0;
$total_keluar = 0;
$reports = [];
while ($row = $report_result->fetch_assoc()) {
    $reports[] = $row;
    $total_masuk += $row['total_masuk'];
    $total_keluar += $row['total_keluar'];
}
$surplus = $total_masuk - $total_keluar;
?>

<?php include '../includes/header.php'; ?>

<div class="flex-1 flex flex-col h-full bg-gray-50">
    <?php include '../includes/warga_navbar.php'; ?>
    
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-50">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Laporan Keuangan</h1>
                    <p class="text-gray-500 text-sm mt-1">Bulan Berjalan: <span class="font-bold text-blue-600"><?php echo date('F Y'); ?></span></p>
                </div>
                <div class="bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full font-bold">
                    <i class="fa-solid fa-circle-check mr-1"></i> Data Terverifikasi
                </div>
            </div>

            <!-- Summary Card -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center divide-y md:divide-y-0 md:divide-x divide-gray-100">
                    <div class="pb-4 md:pb-0">
                        <p class="text-gray-500 text-xs uppercase font-bold tracking-wide">Pemasukan Bulan Ini</p>
                        <p class="text-xl font-bold text-green-600 mt-2"><?php echo formatRupiah($total_masuk); ?></p>
                    </div>
                    <div class="py-4 md:py-0">
                        <p class="text-gray-500 text-xs uppercase font-bold tracking-wide">Pengeluaran Bulan Ini</p>
                        <p class="text-xl font-bold text-red-600 mt-2"><?php echo formatRupiah($total_keluar); ?></p>
                    </div>
                    <div class="pt-4 md:pt-0">
                        <p class="text-gray-500 text-xs uppercase font-bold tracking-wide">Surplus / Defisit</p>
                        <p class="text-xl font-bold <?php echo $surplus >= 0 ? 'text-blue-600' : 'text-red-600'; ?> mt-2">
                            <?php echo $surplus >= 0 ? '+' : ''; ?><?php echo formatRupiah($surplus); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Detailed Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <?php foreach ($reports as $report): ?>
                <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-gray-800"><?php echo $report['nama_kategori']; ?></h3>
                        <div class="bg-<?php echo $report['warna']; ?>-100 p-2 rounded-full text-<?php echo $report['warna']; ?>-600">
                            <i class="fa-solid fa-<?php echo $report['nama_kategori'] == 'Kas Perumahan' ? 'city' : ($report['nama_kategori'] == 'Dana Sosial' ? 'hand-holding-heart' : 'users'); ?>"></i>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Pemasukan:</span> 
                            <span class="font-bold text-green-600"><?php echo formatRupiah($report['total_masuk']); ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Pengeluaran:</span> 
                            <span class="font-bold text-red-600"><?php echo formatRupiah($report['total_keluar']); ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Iuran Warga:</span> 
                            <span class="font-bold text-blue-600"><?php echo formatRupiah($report['biaya_iuran']); ?>/bulan</span>
                        </div>
                        <div class="flex justify-between font-bold pt-2 border-t border-gray-100 mt-2">
                            <span>Saldo Kas:</span> 
                            <span class="text-<?php echo $report['warna']; ?>-700"><?php echo formatRupiah($report['saldo']); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Payment History -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 font-bold text-gray-700">
                    Riwayat Pembayaran Anda
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                            <tr>
                                <th class="px-6 py-3">Periode</th>
                                <th class="px-6 py-3">Nominal</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Tanggal Bayar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if ($payment_result->num_rows > 0): ?>
                                <?php while ($payment = $payment_result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium">
                                        <?php echo date('F Y', mktime(0, 0, 0, $payment['bulan'], 1, $payment['tahun'])); ?>
                                    </td>
                                    <td class="px-6 py-4"><?php echo formatRupiah($payment['nominal']); ?></td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $status_class = [
                                            'lunas' => 'bg-green-100 text-green-800',
                                            'belum' => 'bg-yellow-100 text-yellow-800',
                                            'nunggak' => 'bg-red-100 text-red-800'
                                        ];
                                        ?>
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $status_class[$payment['status']]; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php echo $payment['tanggal_bayar'] ? date('d/m/Y', strtotime($payment['tanggal_bayar'])) : '-'; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fa-solid fa-inbox text-3xl mb-2 block"></i>
                                        Belum ada riwayat pembayaran
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800 flex items-start">
                <i class="fa-solid fa-circle-info mt-0.5 mr-2"></i>
                <p>Laporan ini adalah ringkasan keuangan untuk bulan <b><?php echo date('F Y'); ?></b>. Untuk detail laporan bulan sebelumnya atau detail transaksi, silakan hubungi pengurus RT atau Bendahara.</p>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>