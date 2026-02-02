<?php
require_once '../includes/config.php';
requireAdmin();

$page_title = "Pengaturan Sistem";

// Get current settings
$settings_sql = "SELECT * FROM settings";
$settings_result = $conn->query($settings_sql);
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get categories for fee settings
$categories_sql = "SELECT * FROM kategori_kas ORDER BY id";
$categories_result = $conn->query($categories_sql);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_fees'])) {
        // Save category fees
        foreach ($_POST['biaya_iuran'] as $category_id => $biaya) {
            $biaya = floatval(str_replace(['.', ','], '', $biaya));
            $sql = "UPDATE kategori_kas SET biaya_iuran = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $biaya, $category_id);
            $stmt->execute();
        }
        
        $_SESSION['success'] = 'Tarif iuran berhasil diperbarui!';
        header('Location: settings.php');
        exit();
    }
    
    if (isset($_POST['save_bank'])) {
        // Save bank settings
        $bank_name = sanitize($_POST['bank_name']);
        $bank_number = sanitize($_POST['bank_number']);
        $bank_holder = sanitize($_POST['bank_holder']);
        
        // Update or insert settings
        $settings_to_update = [
            'bank_name' => $bank_name,
            'bank_number' => $bank_number,
            'bank_holder' => $bank_holder
        ];
        
        foreach ($settings_to_update as $key => $value) {
            $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
        }
        
        $_SESSION['success'] = 'Data rekening berhasil diperbarui!';
        header('Location: settings.php');
        exit();
    }
    
    if (isset($_POST['save_general'])) {
        // Save general settings
        $app_name = sanitize($_POST['app_name']);
        $app_year = intval($_POST['app_year']);
        
        $settings_to_update = [
            'app_name' => $app_name,
            'app_year' => $app_year
        ];
        
        foreach ($settings_to_update as $key => $value) {
            $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
        }
        
        $_SESSION['success'] = 'Pengaturan umum berhasil diperbarui!';
        header('Location: settings.php');
        exit();
    }
    
    if (isset($_POST['add_admin'])) {
        // Add new admin
        $username = sanitize($_POST['new_username']);
        $password = $_POST['new_password'];
        $nama_lengkap = sanitize($_POST['new_nama']);
        
        // Check if username exists
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['error'] = 'Username sudah digunakan!';
        } else {
            $sql = "INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, 'admin')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $username, $password, $nama_lengkap);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Admin berhasil ditambahkan!';
            } else {
                $_SESSION['error'] = 'Gagal menambahkan admin!';
            }
        }
        
        header('Location: settings.php');
        exit();
    }
}

// Get admin users (excluding current user)
$current_user_id = $_SESSION['user_id'];
$admins_sql = "SELECT id, username, nama_lengkap FROM users WHERE role = 'admin' AND id != ?";
$stmt = $conn->prepare($admins_sql);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$admins_result = $stmt->get_result();

// Calculate total iuran
$total_iuran_sql = "SELECT SUM(biaya_iuran) as total FROM kategori_kas";
$total_iuran_result = $conn->query($total_iuran_sql);
$total_iuran = $total_iuran_result->fetch_assoc()['total'];
?>

<?php include '../includes/header.php'; ?>

<div class="flex-1 flex h-full">
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col h-full overflow-hidden bg-gray-100">
        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Pengaturan Sistem</h1>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column -->
                <div class="flex flex-col gap-8">
                    <!-- Pengaturan Iuran -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Atur Biaya Iuran</h2>
                        <form method="POST" action="">
                            <div class="space-y-4">
                                <?php while ($category = $categories_result->fetch_assoc()): ?>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                                        <?php echo $category['nama_kategori']; ?>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold">Rp</span>
                                        <input type="text" name="biaya_iuran[<?php echo $category['id']; ?>]" 
                                               value="<?php echo number_format($category['biaya_iuran'], 0, ',', '.'); ?>"
                                               class="w-full pl-10 p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                               oninput="calculateTotalIuran()"
                                               data-category-id="<?php echo $category['id']; ?>">
                                    </div>
                                </div>
                                <?php endwhile; ?>
                                
                                <div class="bg-blue-50 p-3 rounded-lg border border-blue-100 mt-4">
                                    <label class="block text-xs font-bold text-blue-800 mb-1 uppercase">Total Tagihan Warga per Bulan</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-blue-600 font-bold">Rp</span>
                                        <input type="text" id="total_iuran" 
                                               value="<?php echo number_format($total_iuran, 0, ',', '.'); ?>" 
                                               readonly 
                                               class="w-full pl-10 p-2 border-0 bg-transparent font-mono text-xl font-extrabold text-blue-700 focus:ring-0">
                                    </div>
                                </div>
                                
                                <button type="submit" name="save_fees" class="w-full bg-blue-600 text-white font-bold py-2.5 rounded-lg hover:bg-blue-700 transition">
                                    Simpan Tarif Iuran
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Pengaturan Rekening -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Pengaturan Rekening Pembayaran</h2>
                        <form method="POST" action="">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Bank</label>
                                    <input type="text" name="bank_name" 
                                           value="<?php echo $settings['bank_name'] ?? 'BCA'; ?>"
                                           class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Contoh: BCA">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor Rekening</label>
                                    <input type="text" name="bank_number" 
                                           value="<?php echo $settings['bank_number'] ?? '1234567890'; ?>"
                                           class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-mono"
                                           placeholder="Contoh: 1234567890">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Atas Nama</label>
                                    <input type="text" name="bank_holder" 
                                           value="<?php echo $settings['bank_holder'] ?? 'Paguyuban Rawinda'; ?>"
                                           class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Contoh: Paguyuban Warga">
                                </div>
                                <button type="submit" name="save_bank" class="w-full bg-gray-800 text-white font-bold py-2.5 rounded-lg hover:bg-gray-900 transition">
                                    Simpan Rekening
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="flex flex-col gap-8">
                    <!-- Manajemen Admin -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h2 class="text-lg font-bold text-gray-800">Manajemen Akses Admin</h2>
                            <button type="button" onclick="openAdminModal()" 
                                    class="text-xs bg-gray-800 text-white px-3 py-1.5 rounded hover:bg-gray-700 transition flex items-center">
                                <i class="fa-solid fa-user-plus mr-1"></i> Tambah
                            </button>
                        </div>
                        
                        <div class="overflow-hidden">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                    <tr>
                                        <th class="py-2 px-2">Username</th>
                                        <th class="py-2 px-2">Nama</th>
                                        <th class="py-2 px-2 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if ($admins_result->num_rows > 0): ?>
                                        <?php while ($admin = $admins_result->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 px-2 font-mono text-gray-600"><?php echo $admin['username']; ?></td>
                                            <td class="py-3 px-2 font-medium text-gray-800"><?php echo $admin['nama_lengkap']; ?></td>
                                            <td class="py-3 px-2 text-right">
                                                <a href="?delete_admin=<?php echo $admin['id']; ?>" 
                                                   class="text-red-500 hover:text-red-700 text-xs font-bold border border-red-200 bg-red-50 px-2 py-1 rounded"
                                                   onclick="return confirm('Hapus akses admin <?php echo $admin['nama_lengkap']; ?>?')">
                                                    Hapus
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="py-4 text-center text-gray-500">
                                                Tidak ada admin lain
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-gray-400 mt-4 italic">*Admin utama tidak dapat dihapus.</p>
                    </div>

                    <!-- Pengaturan Umum -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Pengaturan Umum</h2>
                        <form method="POST" action="">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Aplikasi</label>
                                    <input type="text" name="app_name" 
                                           value="<?php echo $settings['app_name'] ?? 'Sistem Keuangan Rawinda Pratama'; ?>"
                                           class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tahun Berjalan</label>
                                    <input type="number" name="app_year" 
                                           value="<?php echo $settings['app_year'] ?? date('Y'); ?>"
                                           min="2024" max="2030"
                                           class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="pt-2">
                                    <button type="submit" name="save_general" class="w-full bg-green-600 text-white font-bold py-2.5 rounded-lg hover:bg-green-700 transition">
                                        Simpan Pengaturan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- System Information -->
                    <div class="bg-gray-800 text-white p-6 rounded-xl shadow-sm">
                        <h2 class="text-lg font-bold mb-4 border-b border-gray-700 pb-2">Informasi Sistem</h2>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Versi</span>
                                <span class="font-mono">1.0.0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">PHP Version</span>
                                <span><?php echo phpversion(); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Database</span>
                                <span>MySQL</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Total Warga</span>
                                <span>
                                    <?php
                                    $count_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'warga'";
                                    $count_result = $conn->query($count_sql);
                                    echo $count_result->fetch_assoc()['count'] . ' KK';
                                    ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Total Kas</span>
                                <span class="font-bold text-green-400">
                                    <?php
                                    $total_kas_sql = "SELECT SUM(saldo) as total FROM kategori_kas";
                                    $total_kas_result = $conn->query($total_kas_sql);
                                    echo formatRupiah($total_kas_result->fetch_assoc()['total']);
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal for Add Admin -->
<div id="adminModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm">
        <div class="bg-gray-800 px-6 py-4 flex justify-between items-center rounded-t-xl">
            <h3 class="text-white font-bold"><i class="fa-solid fa-user-shield mr-2"></i>Tambah Admin</h3>
            <button onclick="closeAdminModal()" class="text-gray-400 hover:text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Nama Lengkap</label>
                    <input type="text" name="new_nama" required 
                           class="w-full border p-2 rounded focus:ring-gray-500 focus:border-gray-500"
                           placeholder="Contoh: Sekretaris RT">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Username</label>
                    <input type="text" name="new_username" required 
                           class="w-full border p-2 rounded bg-gray-50 focus:ring-gray-500 focus:border-gray-500"
                           placeholder="username">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-1">Password</label>
                    <input type="password" name="new_password" required 
                           class="w-full border p-2 rounded bg-gray-50 focus:ring-gray-500 focus:border-gray-500"
                           placeholder="••••••">
                </div>
                <button type="submit" name="add_admin" class="w-full bg-gray-800 text-white font-bold py-2.5 rounded hover:bg-gray-900 transition">
                    Simpan Admin
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function calculateTotalIuran() {
    let total = 0;
    const inputs = document.querySelectorAll('input[name^="biaya_iuran"]');
    
    inputs.forEach(input => {
        const value = input.value.replace(/[^0-9]/g, '');
        total += parseInt(value) || 0;
    });
    
    document.getElementById('total_iuran').value = total.toLocaleString('id-ID');
}

function openAdminModal() {
    document.getElementById('adminModal').classList.remove('hidden');
}

function closeAdminModal() {
    document.getElementById('adminModal').classList.add('hidden');
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    calculateTotalIuran();
});
</script>

<?php include '../includes/footer.php'; ?>