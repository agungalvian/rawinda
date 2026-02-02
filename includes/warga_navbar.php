<?php require_once '../includes/config.php'; ?>
<nav class="bg-white border-b border-gray-200 shadow-sm z-30 sticky top-0">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <span class="text-blue-600 text-lg font-bold mr-6">
                    <i class="fa-solid fa-house-user mr-2"></i>Rawinda App
                </span>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex space-x-1">
                    <a href="dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'; ?> transition">
                        <i class="fa-solid fa-house mr-1"></i> Beranda
                    </a>
                    <a href="laporan.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'; ?> transition">
                        <i class="fa-solid fa-file-invoice-dollar mr-1"></i> Laporan Keuangan
                    </a>
                    <a href="bayar.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'bayar.php' ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'; ?> transition">
                        <i class="fa-solid fa-credit-card mr-1"></i> Pembayaran
                    </a>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-700"><?php echo $_SESSION['nama_lengkap']; ?></p>
                    <p class="text-xs text-gray-500"><?php echo $_SESSION['no_rumah'] ?? 'Warga'; ?></p>
                </div>
                <a href="../logout.php" class="text-red-500 hover:text-red-700 text-sm font-semibold ml-2" onclick="return confirm('Yakin ingin logout?')">
                    <i class="fa-solid fa-right-from-bracket mr-1"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Mobile Menu -->
    <div class="md:hidden border-t border-gray-100 grid grid-cols-3 text-center bg-gray-50">
        <a href="dashboard.php" class="py-3 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-blue-600 border-b-2 border-blue-600 font-bold' : 'text-gray-500 border-b-2 border-transparent'; ?>">
            <i class="fa-solid fa-house block mx-auto mb-1"></i> Beranda
        </a>
        <a href="laporan.php" class="py-3 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'text-blue-600 border-b-2 border-blue-600 font-bold' : 'text-gray-500 border-b-2 border-transparent'; ?>">
            <i class="fa-solid fa-file-invoice-dollar block mx-auto mb-1"></i> Laporan
        </a>
        <a href="bayar.php" class="py-3 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'bayar.php' ? 'text-blue-600 border-b-2 border-blue-600 font-bold' : 'text-gray-500 border-b-2 border-transparent'; ?>">
            <i class="fa-solid fa-credit-card block mx-auto mb-1"></i> Bayar
        </a>
    </div>
</nav>