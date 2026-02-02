<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rawinda_finance');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8");

// Base URL
define('BASE_URL', 'http://localhost/rawinda_pratama');

// Path for file uploads
define('UPLOAD_PATH', dirname(__DIR__) . '/assets/uploads/');

// Authentication Functions
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

if (!function_exists('isWarga')) {
    function isWarga() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'warga';
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            $_SESSION['error'] = 'Silakan login terlebih dahulu!';
            header('Location: ../index.php');
            exit();
        }
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        requireLogin();
        if (!isAdmin()) {
            $_SESSION['error'] = 'Akses ditolak! Halaman ini hanya untuk admin.';
            header('Location: ../warga/dashboard.php');
            exit();
        }
    }
}

if (!function_exists('requireWarga')) {
    function requireWarga() {
        requireLogin();
        if (!isWarga()) {
            $_SESSION['error'] = 'Akses ditolak! Halaman ini hanya untuk warga.';
            header('Location: ../admin/dashboard.php');
            exit();
        }
    }
}
?>