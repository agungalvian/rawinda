<?php
require_once '../includes/config.php';
requireAdmin();

$page_title = "Dashboard Admin";

// Get statistics
$total_kas = 0;
$kategori_kas = [];
$stats_sql = "SELECT * FROM kategori_kas";
$result = $conn->query($stats_sql);
while ($row = $result->fetch_assoc()) {
    $total_kas += $row['saldo'];
    $kategori_kas[] = $row;
}

// Get recent transactions
$transaksi_sql = "SELECT t.*, k.nama_kategori, k.warna, u.nama_lengkap 
                  FROM transaksi t 
                  LEFT JOIN kategori_kas k ON t.kategori_id = k.id 
                  LEFT JOIN users u ON t.warga_id = u.id 
                  WHERE t.status = 'verified'
                  ORDER BY t.tanggal DESC LIMIT 10";
$transaksi_result = $conn->query($transaksi_sql);

// Get pending transactions count
$pending_sql = "SELECT COUNT(*) as count FROM transaksi WHERE status = 'pending'";
$pending_result = $conn->query($pending_sql);
$pending_count = $pending_result->fetch_assoc()['count'];

// Get total warga
$warga_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'warga'";
$warga_result = $conn->query($warga_sql);
$total_warga = $warga_result->fetch_assoc()['count'];
?>

<?php include '../includes/header.php'; ?>

<div class="flex-1 flex h-full">
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col h-full overflow-hidden bg-gray-100">
        <main class="flex-1 overflow-y-auto p-6 md:p-8 relative">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Ringkasan Keuangan</h1>
                    <p class="text-gray-500 text-sm mt-1"><?php echo date('l, d F Y'); ?></p>
                </div>
                <div class="flex space-x-3">
                    <a href="transaksi.php?action=income" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg shadow-lg flex items-center transition">
                        <i class="fa-solid fa-circle-plus mr-2"></i> Input Pemasukan
                    </a>
                    <a href="transaksi.php?action=expense" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg shadow-lg flex items-center transition">
                        <i class="fa-solid fa-circle-minus mr-2"></i> Input Pengeluaran
                    </a>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <p class="text-xs font-bold text-gray-400 uppercase">Total Kas Tunai</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo formatRupiah($total_kas); ?></p>
                </div>
                <div class="bg-blue-50 p-6 rounded-xl border border-blue-100">
                    <p class="text-xs font-bold text-blue-500 uppercase">Kas Perumahan</p>
                    <p class="text-2xl font-bold text-blue-900 mt-2"><?php echo formatRupiah($kategori_kas[0]['saldo'] ?? 0); ?></p>
                </div>
                <div class="bg-green-50 p-6 rounded-xl border border-green-100">
                    <p class="text-xs font-bold text-green-500 uppercase">Dana Sosial</p>
                    <p class="text-2xl font-bold text-green-900 mt-2"><?php echo formatRupiah($kategori_kas[1]['saldo'] ?? 0); ?></p>
                </div>
                <div class="bg-purple-50 p-6 rounded-xl border border-purple-100">
                    <p class="text-xs font-bold text-purple-500 uppercase">Kas RT</p>
                    <p class="text-2xl font-bold text-purple-900 mt-2"><?php echo formatRupiah($kategori_kas[2]['saldo'] ?? 0); ?></p>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fa-solid fa-users text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total Warga</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $total_warga; ?> Orang</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                            <i class="fa-solid fa-clock text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Menunggu Verifikasi</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $pending_count; ?> Transaksi</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fa-solid fa-calendar-check text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Bulan Ini</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo date('F Y'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fa-solid fa-chart-line mr-2 text-blue-600"></i> Statistik Saldo Keuangan
                </h2>
                <div class="h-80 w-full">
                    <canvas id="financeChart"></canvas>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 font-bold text-gray-700 flex justify-between items-center">
                    <span>Transaksi Terbaru</span>
                    <a href="transaksi.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Lihat Semua →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                            <tr>
                                <th class="px-6 py-3">Tanggal</th>
                                <th class="px-6 py-3">Keterangan</th>
                                <th class="px-6 py-3">Kategori</th>
                                <th class="px-6 py-3">Jenis</th>
                                <th class="px-6 py-3 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php while ($transaksi = $transaksi_result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4"><?php echo date('d/m/Y', strtotime($transaksi['tanggal'])); ?></td>
                                <td class="px-6 py-4 font-medium text-gray-800"><?php echo $transaksi['keterangan']; ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full bg-<?php echo $transaksi['warna']; ?>-100 text-<?php echo $transaksi['warna']; ?>-800">
                                        <?php echo $transaksi['nama_kategori']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $transaksi['jenis'] == 'masuk' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $transaksi['jenis'] == 'masuk' ? 'Pemasukan' : 'Pengeluaran'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-bold <?php echo $transaksi['jenis'] == 'masuk' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $transaksi['jenis'] == 'masuk' ? '+' : '-'; ?> <?php echo formatRupiah($transaksi['nominal']); ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('financeChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [
                {
                    label: 'Kas Perumahan',
                    data: [8000000, 10750000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Dana Sosial',
                    data: [2500000, 3200000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(34, 197, 94, 0.7)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Kas RT',
                    data: [1200000, 1500000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(168, 85, 247, 0.7)',
                    borderColor: 'rgba(168, 85, 247, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': Rp ' + context.parsed.y.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>