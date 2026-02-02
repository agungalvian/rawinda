<?php
require_once '../includes/config.php';
requireAdmin();

$page_title = "Laporan Keuangan";

// Get filter parameters
$bulan = $_GET['bulan'] ?? date('n');
$tahun = $_GET['tahun'] ?? date('Y');

// Validate bulan and tahun
$bulan = intval($bulan);
$tahun = intval($tahun);
if ($bulan < 1 || $bulan > 12) $bulan = date('n');
if ($tahun < 2020 || $tahun > 2030) $tahun = date('Y');

// Get categories
$categories_sql = "SELECT * FROM kategori_kas ORDER BY id";
$categories_result = $conn->query($categories_sql);

// Get monthly report data
$report_data = [];
$total_masuk = 0;
$total_keluar = 0;

while ($category = $categories_result->fetch_assoc()) {
    // Get transactions for this category and month
    $transaksi_sql = "SELECT 
                        jenis,
                        SUM(nominal) as total
                      FROM transaksi 
                      WHERE kategori_id = ? 
                      AND MONTH(tanggal) = ? 
                      AND YEAR(tanggal) = ?
                      AND status = 'verified'
                      GROUP BY jenis";
    
    $stmt = $conn->prepare($transaksi_sql);
    $stmt->bind_param("iii", $category['id'], $bulan, $tahun);
    $stmt->execute();
    $transaksi_result = $stmt->get_result();
    
    $masuk = 0;
    $keluar = 0;
    
    while ($transaksi = $transaksi_result->fetch_assoc()) {
        if ($transaksi['jenis'] == 'masuk') {
            $masuk = $transaksi['total'];
            $total_masuk += $masuk;
        } else {
            $keluar = $transaksi['total'];
            $total_keluar += $keluar;
        }
    }
    
    $saldo_awal = $category['saldo'] - $masuk + $keluar;
    
    $report_data[] = [
        'id' => $category['id'],
        'nama' => $category['nama_kategori'],
        'warna' => $category['warna'],
        'masuk' => $masuk,
        'keluar' => $keluar,
        'saldo_awal' => $saldo_awal,
        'saldo_akhir' => $category['saldo']
    ];
}

// Get detailed transactions
$detail_sql = "SELECT 
                t.*,
                k.nama_kategori,
                k.warna,
                u.no_rumah,
                u.nama_lengkap
              FROM transaksi t
              LEFT JOIN kategori_kas k ON t.kategori_id = k.id
              LEFT JOIN users u ON t.warga_id = u.id
              WHERE MONTH(t.tanggal) = ? 
              AND YEAR(t.tanggal) = ?
              AND t.status = 'verified'
              ORDER BY t.tanggal DESC";
$stmt = $conn->prepare($detail_sql);
$stmt->bind_param("ii", $bulan, $tahun);
$stmt->execute();
$detail_result = $stmt->get_result();

// Month names in Indonesian
$bulan_names = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<?php include '../includes/header.php'; ?>

<div class="flex-1 flex h-full">
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col h-full overflow-hidden bg-gray-100">
        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Laporan Keuangan</h1>
                    <p class="text-gray-500 text-sm mt-1">Periode: <span class="font-bold text-blue-600"><?php echo $bulan_names[$bulan] . ' ' . $tahun; ?></span></p>
                </div>
                
                <!-- Filter Form -->
                <form method="GET" action="" class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fa-regular fa-calendar text-gray-400"></i>
                        </div>
                        <select name="bulan" class="bg-white border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5">
                            <?php foreach ($bulan_names as $key => $name): ?>
                            <option value="<?php echo $key; ?>" <?php echo $bulan == $key ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <select name="tahun" class="bg-white border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <?php for ($y = 2024; $y <= 2027; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                    
                    <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-5 py-2.5 transition flex items-center">
                        <i class="fa-solid fa-filter mr-2"></i> Tampilkan
                    </button>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fa-solid fa-arrow-down text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total Pemasukan</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo formatRupiah($total_masuk); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                            <i class="fa-solid fa-arrow-up text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total Pengeluaran</p>
                            <p class="text-2xl font-bold text-red-600"><?php echo formatRupiah($total_keluar); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fa-solid fa-balance-scale text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Saldo Bersih</p>
                            <p class="text-2xl font-bold text-blue-600"><?php echo formatRupiah($total_masuk - $total_keluar); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Reports -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <?php foreach ($report_data as $report): ?>
                <div class="border border-<?php echo $report['warna']; ?>-100 rounded-lg p-5 bg-<?php echo $report['warna']; ?>-50/50">
                    <h3 class="font-bold text-<?php echo $report['warna']; ?>-700 mb-3 border-b border-<?php echo $report['warna']; ?>-200 pb-2 flex items-center justify-between">
                        <?php echo $report['nama']; ?>
                        <i class="fa-solid fa-<?php echo $report['nama'] == 'Kas Perumahan' ? 'city' : ($report['nama'] == 'Dana Sosial' ? 'hand-holding-heart' : 'users'); ?> text-<?php echo $report['warna']; ?>-300"></i>
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Pemasukan</span>
                            <span class="font-bold text-gray-800"><?php echo formatRupiah($report['masuk']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Pengeluaran</span>
                            <span class="font-bold text-red-600"><?php echo formatRupiah($report['keluar']); ?></span>
                        </div>
                        <div class="border-t border-<?php echo $report['warna']; ?>-200 pt-2 mt-2 space-y-1">
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>Saldo Awal</span>
                                <span><?php echo formatRupiah($report['saldo_awal']); ?></span>
                            </div>
                            <div class="flex justify-between font-bold text-base">
                                <span>Saldo Akhir</span>
                                <span class="text-<?php echo $report['warna']; ?>-700"><?php echo formatRupiah($report['saldo_akhir']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Grand Total Summary -->
            <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white rounded-lg p-6 flex flex-col md:flex-row justify-between items-center shadow-lg mb-8">
                <div class="text-center md:text-left mb-4 md:mb-0">
                    <p class="text-gray-400 text-xs uppercase font-bold tracking-widest mb-1">Total Saldo Gabungan</p>
                    <p class="text-4xl font-extrabold tracking-tight">
                        <?php 
                        $total_saldo = 0;
                        foreach ($report_data as $report) {
                            $total_saldo += $report['saldo_akhir'];
                        }
                        echo formatRupiah($total_saldo);
                        ?>
                    </p>
                </div>
                <div class="text-right text-sm space-y-1">
                    <p class="text-gray-300">Total Pemasukan: <span class="text-green-400 font-bold">+ <?php echo formatRupiah($total_masuk); ?></span></p>
                    <p class="text-gray-300">Total Pengeluaran: <span class="text-red-400 font-bold">- <?php echo formatRupiah($total_keluar); ?></span></p>
                    <p class="text-gray-300 mt-2 pt-2 border-t border-gray-700">Surplus/Defisit: 
                        <span class="font-bold <?php echo ($total_masuk - $total_keluar) >= 0 ? 'text-green-400' : 'text-red-400'; ?>">
                            <?php echo ($total_masuk - $total_keluar) >= 0 ? '+' : ''; ?><?php echo formatRupiah($total_masuk - $total_keluar); ?>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Detailed Transactions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 font-bold text-gray-700">
                    Rincian Transaksi <?php echo $bulan_names[$bulan]; ?> <?php echo $tahun; ?>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3">Tgl</th>
                                <th class="px-4 py-3">Uraian</th>
                                <th class="px-4 py-3">Kategori</th>
                                <th class="px-4 py-3">Warga</th>
                                <th class="px-4 py-3">Tipe</th>
                                <th class="px-4 py-3 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if ($detail_result->num_rows > 0): ?>
                                <?php while ($transaksi = $detail_result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2"><?php echo date('d/m', strtotime($transaksi['tanggal'])); ?></td>
                                    <td class="px-4 py-2 font-medium text-gray-800"><?php echo $transaksi['keterangan']; ?></td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 text-xs rounded-full bg-<?php echo $transaksi['warna']; ?>-100 text-<?php echo $transaksi['warna']; ?>-800">
                                            <?php echo $transaksi['nama_kategori']; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <?php if ($transaksi['no_rumah']): ?>
                                        <span class="text-xs text-gray-600"><?php echo $transaksi['no_rumah']; ?></span>
                                        <?php else: ?>
                                        <span class="text-gray-400 text-xs">Umum</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="<?php echo $transaksi['jenis'] == 'masuk' ? 'text-green-600' : 'text-red-600'; ?> font-semibold">
                                            <?php echo $transaksi['jenis'] == 'masuk' ? 'Masuk' : 'Keluar'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right font-bold <?php echo $transaksi['jenis'] == 'masuk' ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $transaksi['jenis'] == 'masuk' ? '+' : '-'; ?> <?php echo formatRupiah($transaksi['nominal']); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        <i class="fa-solid fa-inbox text-3xl mb-2 block"></i>
                                        Tidak ada transaksi pada periode ini
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Print Button -->
            <div class="mt-8 flex justify-center">
                <button onclick="printReport()" class="border-2 border-gray-800 text-gray-800 py-3 px-8 rounded-lg hover:bg-gray-800 hover:text-white transition font-bold flex items-center justify-center group">
                    <i class="fa-solid fa-print mr-2 group-hover:animate-bounce"></i> Cetak Laporan
                </button>
            </div>
        </main>
    </div>
</div>

<script>
function printReport() {
    window.print();
}
</script>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    
    body {
        background: white !important;
        font-size: 12pt;
    }
    
    .bg-gray-100 {
        background: white !important;
    }
    
    .shadow-sm, .shadow-lg {
        box-shadow: none !important;
    }
    
    .border {
        border: 1px solid #ddd !important;
    }
    
    table {
        border-collapse: collapse;
        width: 100%;
    }
    
    th, td {
        border: 1px solid #ddd !important;
        padding: 8px !important;
    }
    
    th {
        background-color: #f9f9f9 !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>