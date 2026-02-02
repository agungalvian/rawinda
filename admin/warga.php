<?php
require_once '../includes/config.php';
requireAdmin();

$page_title = "Data Warga";

// Get all warga
$sql = "SELECT * FROM users WHERE role = 'warga' ORDER BY no_rumah";
$result = $conn->query($sql);

// Handle form submission for adding/editing warga
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $no_rumah = sanitize($_POST['no_rumah']);
    $nama_lengkap = sanitize($_POST['nama_lengkap']);
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $status_rumah = $_POST['status_rumah'];
    
    if ($id > 0) {
        // Update existing warga
        $sql = "UPDATE users SET no_rumah = ?, nama_lengkap = ?, username = ?, status_rumah = ? ";
        $params = [$no_rumah, $nama_lengkap, $username, $status_rumah];
        
        if (!empty($password)) {
            $sql .= ", password = ? ";
            $params[] = $password;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($params) - 1) . 'i', ...$params);
        
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
            $_SESSION['success'] = 'Data warga berhasil ditambahkan!';
        } else {
            $_SESSION['error'] = 'Gagal menambahkan data warga!';
        }
    }
    
    header('Location: warga.php');
    exit();
}

// Handle delete request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Check if warga has transactions
    $check_sql = "SELECT COUNT(*) as count FROM pembayaran_iuran WHERE warga_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $check_result = $stmt->get_result();
    $count = $check_result->fetch_assoc()['count'];
    
    if ($count > 0) {
        $_SESSION['error'] = 'Warga tidak dapat dihapus karena memiliki riwayat pembayaran!';
    } else {
        $sql = "DELETE FROM users WHERE id = ? AND role = 'warga'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Data warga berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus data warga!';
        }
    }
    
    header('Location: warga.php');
    exit();
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex-1 flex h-full">
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col h-full overflow-hidden bg-gray-100">
        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Data Warga</h1>
                    <p class="text-gray-500 text-sm mt-1">Total: <?php echo $result->num_rows; ?> warga terdaftar</p>
                </div>
                <button onclick="openWargaModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition flex items-center">
                    <i class="fa-solid fa-plus mr-1"></i> Tambah Warga
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                            <tr>
                                <th class="px-6 py-3">No. Rumah</th>
                                <th class="px-6 py-3">Nama KK</th>
                                <th class="px-6 py-3">Username</th>
                                <th class="px-6 py-3">Password</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php while ($warga = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-bold"><?php echo $warga['no_rumah']; ?></td>
                                <td class="px-6 py-4"><?php echo $warga['nama_lengkap']; ?></td>
                                <td class="px-6 py-4">
                                    <code class="text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded"><?php echo $warga['username']; ?></code>
                                </td>
                                <td class="px-6 py-4">
                                    <code class="text-xs text-gray-400">••••••</code>
                                </td>
                                <td class="px-6 py-4">
                                    <?php 
                                    $status_class = [
                                        'dihuni' => 'bg-green-100 text-green-800',
                                        'kosong' => 'bg-gray-100 text-gray-800',
                                        'sewa' => 'bg-yellow-100 text-yellow-800'
                                    ];
                                    ?>
                                    <span class="text-xs px-2 py-1 rounded-full <?php echo $status_class[$warga['status_rumah']] ?? 'bg-gray-100'; ?>">
                                        <?php echo ucfirst($warga['status_rumah']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button onclick="editWarga(<?php echo $warga['id']; ?>, '<?php echo $warga['no_rumah']; ?>', '<?php echo $warga['nama_lengkap']; ?>', '<?php echo $warga['username']; ?>', '<?php echo $warga['status_rumah']; ?>')" 
                                            class="text-blue-500 hover:text-blue-700 transition mr-2">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <a href="?delete=<?php echo $warga['id']; ?>" 
                                       class="text-red-500 hover:text-red-700 transition"
                                       onclick="return confirm('Hapus warga <?php echo $warga['nama_lengkap']; ?>?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
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

<!-- Modal for Add/Edit Warga -->
<div id="wargaModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center rounded-t-xl sticky top-0">
            <h3 class="text-white font-bold text-lg" id="modalTitle">Tambah Warga Baru</h3>
            <button onclick="closeModal()" class="text-white hover:text-gray-200">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6">
            <input type="hidden" name="id" id="wargaId" value="0">
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-700">No. Rumah</label>
                    <input type="text" name="no_rumah" id="wargaNo" required 
                           class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-blue-500 focus:border-blue-500 uppercase"
                           placeholder="A1-01">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-700">Status</label>
                    <select name="status_rumah" id="wargaStatus" 
                            class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="dihuni">Dihuni</option>
                        <option value="kosong">Kosong</option>
                        <option value="sewa">Sewa</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-2 text-gray-700">Nama Kepala Keluarga</label>
                <input type="text" name="nama_lengkap" id="wargaNama" required 
                       class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Nama Lengkap">
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-700">Username</label>
                    <input type="text" name="username" id="wargaUsername" required 
                           class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                           placeholder="warga_a101">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-700">Password</label>
                    <input type="text" name="password" id="wargaPassword" 
                           class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Kosongkan jika tidak diubah">
                </div>
            </div>
            
            <div class="flex space-x-3">
                <button type="submit" class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">
                    Simpan Data
                </button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-300 text-gray-800 font-bold py-3 rounded-lg hover:bg-gray-400 transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openWargaModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Warga Baru';
    document.getElementById('wargaId').value = '0';
    document.getElementById('wargaNo').value = '';
    document.getElementById('wargaNama').value = '';
    document.getElementById('wargaUsername').value = '';
    document.getElementById('wargaPassword').value = '';
    document.getElementById('wargaStatus').value = 'dihuni';
    document.getElementById('wargaModal').classList.remove('hidden');
}

function editWarga(id, noRumah, nama, username, status) {
    document.getElementById('modalTitle').textContent = 'Edit Data Warga (' + noRumah + ')';
    document.getElementById('wargaId').value = id;
    document.getElementById('wargaNo').value = noRumah;
    document.getElementById('wargaNama').value = nama;
    document.getElementById('wargaUsername').value = username;
    document.getElementById('wargaPassword').value = '';
    document.getElementById('wargaPassword').placeholder = 'Kosongkan jika tidak diubah';
    document.getElementById('wargaStatus').value = status;
    document.getElementById('wargaModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('wargaModal').classList.add('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>