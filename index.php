<?php
session_start();
require_once 'includes/config.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'warga/dashboard.php'));
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Untuk demo, password disimpan plaintext
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['no_rumah'] = $user['no_rumah'];
            
            header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'warga/dashboard.php'));
            exit();
        }
    }
    $error = 'Username atau Password salah!';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Keuangan Rawinda Pratama - Login</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .fade-in { animation: fadeIn 0.4s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="text-gray-800 antialiased h-screen flex flex-col overflow-hidden">
    <section class="flex-1 flex flex-col justify-center items-center p-6 bg-slate-900 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden opacity-20">
            <i class="fa-solid fa-money-bill-wave text-9xl absolute -top-10 -left-10 text-white transform rotate-12"></i>
            <i class="fa-solid fa-chart-pie text-9xl absolute bottom-10 right-10 text-white transform -rotate-12"></i>
        </div>

        <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md z-10 fade-in">
            <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
                <i class="fa-solid fa-circle-exclamation mr-2"></i><?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <div class="text-center mb-8">
                <div class="h-16 w-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/50">
                    <i class="fa-solid fa-house-chimney text-3xl text-white"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Rawinda Pratama</h1>
                <p class="text-gray-500 text-sm mt-1">Sistem Manajemen Keuangan Terpadu</p>
            </div>

            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Username / No. Rumah</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <input type="text" name="username" required class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition" placeholder="Contoh: admin" value="admin">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" required class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition" placeholder="••••••••" value="admin">
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition shadow-lg shadow-blue-500/30">
                    Masuk Aplikasi
                </button>
            </form>

            <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-100 text-xs text-blue-800">
                <p class="font-bold mb-1"><i class="fa-solid fa-circle-info mr-1"></i> Info Login (Default):</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li><b>Admin Utama:</b> User: <code>admin</code> | Pass: <code>admin</code></li>
                    <li><b>Warga:</b> User: <code>warga</code> | Pass: <code>warga</code></li>
                </ul>
            </div>
        </div>
        <p class="text-slate-500 text-xs mt-8 z-10">&copy; <?php echo date('Y'); ?> Rawinda Pratama Digital</p>
    </section>
</body>
</html>