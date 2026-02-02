<?php
require_once '../includes/config.php';
requireWarga();

$page_title = "Pembayaran Iuran";

$user_id = $_SESSION['user_id'];
$current_month = date('m');
$current_year = date('Y');

// Get unpaid bills
$bills_sql = "SELECT * FROM pembayaran_iuran 
              WHERE warga_id = ? 
              AND (status = 'belum' OR status = 'nunggak')
              ORDER BY tahun, bulan";
$stmt = $conn->prepare($bills_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bills_result = $stmt->get_result();

// Get paid bills for reference
$paid_sql = "SELECT * FROM pembayaran_iuran 
             WHERE warga_id = ? 
             AND status = 'lunas'
             ORDER BY tahun DESC, bulan DESC LIMIT 6";
$stmt = $conn->prepare($paid_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$paid_result = $stmt->get_result();

// Get settings for bank info
$settings_sql = "SELECT * FROM settings WHERE setting_key IN ('bank_name', 'bank_number', 'bank_holder')";
$settings_result = $conn->query($settings_sql);
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get total iuran per month
$iuran_sql = "SELECT SUM(biaya_iuran) as total_iuran FROM kategori_kas";
$iuran_result = $conn->query($iuran_sql);
$total_iuran = $iuran_result->fetch_assoc()['total_iuran'];
?>

<?php include '../includes/header.php'; ?>

<div class="flex-1 flex flex-col h-full bg-gray-50">
    <?php include '../includes/warga_navbar.php'; ?>
    
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-50">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Pembayaran Iuran</h1>

            <!-- Payment Method Info -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg mb-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-white bg-opacity-20 rounded-full mr-4">
                        <i class="fa-solid fa-building-columns text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">Transfer Bank</h3>
                        <p class="text-blue-100">Gunakan informasi berikut untuk transfer</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                        <p class="text-xs text-blue-200 uppercase font-bold mb-1">Bank</p>
                        <p class="text-lg font-bold"><?php echo $settings['bank_name'] ?? 'BCA'; ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                        <p class="text-xs text-blue-200 uppercase font-bold mb-1">Nomor Rekening</p>
                        <p class="text-lg font-bold font-mono"><?php echo $settings['bank_number'] ?? '1234567890'; ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                        <p class="text-xs text-blue-200 uppercase font-bold mb-1">Atas Nama</p>
                        <p class="text-lg font-bold"><?php echo $settings['bank_holder'] ?? 'Paguyuban Rawinda'; ?></p>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-blue-400">
                    <p class="text-sm">
                        <i class="fa-solid fa-circle-info mr-2"></i>
                        Total iuran per bulan: <span class="font-bold"><?php echo formatRupiah($total_iuran); ?></span>
                    </p>
                </div>
            </div>

            <!-- Unpaid Bills -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 font-bold text-gray-700">
                    <i class="fa-solid fa-clock mr-2 text-yellow-500"></i> Tagihan Belum Dibayar
                </div>
                <div class="p-6">
                    <?php if ($bills_result->num_rows > 0): ?>
                        <form id="paymentForm" method="POST" action="../process/bayar_proses.php" enctype="multipart/form-data">
                            <div class="space-y-3 mb-6 max-h-80 overflow-y-auto pr-2 custom-scrollbar">
                                <?php while ($bill = $bills_result->fetch_assoc()): 
                                    $periode = date('F Y', mktime(0, 0, 0, $bill['bulan'], 1, $bill['tahun']));
                                    $status_class = $bill['status'] == 'nunggak' ? 'border-l-red-500 bg-red-50' : 'border-l-yellow-500 bg-yellow-50';
                                ?>
                                <label class="flex items-center justify-between p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition border-l-4 <?php echo $status_class; ?>">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="bulan[]" value="<?php echo $bill['bulan'] . '-' . $bill['tahun']; ?>" 
                                               class="bill-checkbox w-5 h-5 text-blue-600 rounded focus:ring-blue-500" 
                                               data-nominal="<?php echo $total_iuran; ?>"
                                               onchange="calculateTotal()">
                                        <div class="ml-4">
                                            <p class="text-sm font-bold text-gray-800"><?php echo $periode; ?></p>
                                            <p class="text-xs <?php echo $bill['status'] == 'nunggak' ? 'text-red-600' : 'text-yellow-600'; ?> font-medium">
                                                <?php echo $bill['status'] == 'nunggak' ? 'Tunggakan' : 'Belum Dibayar'; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-600"><?php echo formatRupiah($total_iuran); ?></span>
                                </label>
                                <?php endwhile; ?>
                            </div>
                            
                            <div class="bg-gray-100 p-4 rounded-lg mb-6 border border-gray-200">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-sm font-bold text-gray-600">Total Transfer:</p>
                                        <p class="text-xs text-gray-500">Pilih bulan yang akan dibayar</p>
                                    </div>
                                    <div class="text-right">
                                        <p id="selectedMonths" class="text-xs text-gray-500 mb-1">0 bulan terpilih</p>
                                        <p id="totalDisplay" class="text-2xl font-extrabold text-blue-600">Rp 0</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-gray-700">Tanggal Transfer</label>
                                    <input type="date" name="tanggal_transfer" required 
                                           class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                           value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-gray-700">Upload Bukti Transfer</label>
                                    <input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf" required
                                           class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 transition cursor-pointer bg-gray-50 rounded-lg border border-gray-300">
                                    <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, atau PDF (max 2MB)</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-gray-700">Catatan (Opsional)</label>
                                    <textarea name="catatan" rows="2" 
                                              class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                              placeholder="Contoh: Transfer via mobile banking, ref: 123456"></textarea>
                                </div>
                                
                                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3.5 rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition flex items-center justify-center">
                                    <i class="fa-solid fa-paper-plane mr-2"></i> Kirim Bukti Pembayaran
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fa-solid fa-check-circle text-4xl text-green-500 mb-4"></i>
                            <h3 class="text-lg font-bold text-gray-700 mb-2">Semua Tagihan Sudah Lunas!</h3>
                            <p class="text-gray-500">Tidak ada tagihan yang perlu dibayar saat ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Payments -->
            <?php if ($paid_result->num_rows > 0): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 font-bold text-gray-700">
                    <i class="fa-solid fa-history mr-2 text-green-500"></i> Riwayat Pembayaran Terakhir
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                            <tr>
                                <th class="px-6 py-3">Periode</th>
                                <th class="px-6 py-3">Tanggal Bayar</th>
                                <th class="px-6 py-3">Nominal</th>
                                <th class="px-6 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php while ($paid = $paid_result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium">
                                    <?php echo date('F Y', mktime(0, 0, 0, $paid['bulan'], 1, $paid['tahun'])); ?>
                                </td>
                                <td class="px-6 py-4"><?php echo date('d/m/Y', strtotime($paid['tanggal_bayar'])); ?></td>
                                <td class="px-6 py-4"><?php echo formatRupiah($paid['nominal']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                        <i class="fa-solid fa-check mr-1"></i> Lunas
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
function calculateTotal() {
    const checkboxes = document.querySelectorAll('.bill-checkbox:checked');
    const totalIuran = <?php echo $total_iuran; ?>;
    const total = checkboxes.length * totalIuran;
    
    document.getElementById('totalDisplay').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('selectedMonths').textContent = checkboxes.length + ' bulan terpilih';
}
</script>

<?php include '../includes/footer.php'; ?>