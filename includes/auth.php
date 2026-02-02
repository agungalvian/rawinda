<?php
require_once 'config.php';

class Auth {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function login($username, $password) {
        $username = mysqli_real_escape_string($this->conn, $username);
        
        $sql = "SELECT * FROM users WHERE username = '$username'";
        $result = mysqli_query($this->conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password (in production, use password_hash and password_verify)
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['no_rumah'] = $user['no_rumah'];
                
                return true;
            }
        }
        
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public function isWarga() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'warga';
    }
    
    public function logout() {
        session_destroy();
        header('Location: ../index.php');
        exit();
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ../index.php');
            exit();
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: ../warga/dashboard.php');
            exit();
        }
    }
    
    public function requireWarga() {
        $this->requireLogin();
        if (!$this->isWarga()) {
            header('Location: ../admin/dashboard.php');
            exit();
        }
    }
}

// Initialize Auth
$auth = new Auth($conn);
?>