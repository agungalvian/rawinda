<?php require_once '../includes/config.php'; ?>
<aside class="w-64 bg-slate-900 text-white hidden md:flex flex-col shadow-xl z-20">
    <div class="p-6 border-b border-slate-800">
        <span class="text-xl font-bold tracking-wider text-blue-400 block">ADMIN PANEL</span>
        <span class="text-xs text-slate-400 mt-1"><?php echo $_SESSION['nama_lengkap']; ?></span>
    </div>
    <nav class="flex-1 px-4 space-y-2 mt-6">
        <a href="dashboard.php" class="admin-nav-item w-full flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> rounded-lg transition text-left">
            <i class="fa-solid fa-chart-pie w-6"></i> <span class="font-medium">Dashboard</span>
        </a>
        <a href="warga.php" class="admin-nav-item w-full flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'warga.php' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> rounded-lg transition text-left">
            <i class="fa-solid fa-users w-6"></i> <span class="font-medium">Data Warga</span>
        </a>
        <a href="rekap.php" class="admin-nav-item w-full flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'rekap.php' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> rounded-lg transition text-left">
            <i class="fa-solid fa-calendar-check w-6"></i> <span class="font-medium">Rekap Tahunan</span>
        </a>
        <a href="transaksi.php" class="admin-nav-item w-full flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> rounded-lg transition text-left group">
            <i class="fa-solid fa-receipt w-6"></i> 
            <span class="font-medium">Transaksi</span>
            <?php
            // Count pending transactions
            $pending_sql = "SELECT COUNT(*) as count FROM transaksi WHERE status = 'pending'";
            $pending_result = $conn->query($pending_sql);
            $pending_count = $pending_result->fetch_assoc()['count'];
            if ($pending_count > 0): ?>
            <span class="ml-auto bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="laporan.php" class="admin-nav-item w-full flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> rounded-lg transition text-left">
            <i class="fa-solid fa-file-invoice w-6"></i> <span class="font-medium">Laporan</span>
        </a>
        <a href="settings.php" class="admin-nav-item w-full flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> rounded-lg transition text-left">
            <i class="fa-solid fa-gears w-6"></i> <span class="font-medium">Pengaturan</span>
        </a>
    </nav>
    <div class="p-4 border-t border-slate-800">
        <a href="../logout.php" class="flex items-center w-full px-4 py-2 text-slate-400 hover:text-white transition" onclick="return confirm('Yakin ingin logout?')">
            <i class="fa-solid fa-right-from-bracket w-6"></i> Logout
        </a>
    </div>
</aside>

<!-- Mobile Header -->
<header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 md:hidden z-10">
    <span class="font-bold text-gray-800 text-lg">Admin Panel</span>
    <div class="flex items-center space-x-4">
        <div class="text-right">
            <p class="text-sm font-bold text-gray-700"><?php echo $_SESSION['nama_lengkap']; ?></p>
            <p class="text-xs text-gray-500">Admin</p>
        </div>
        <a href="../logout.php" class="text-red-500 hover:text-red-700" onclick="return confirm('Yakin ingin logout?')">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>
</header>