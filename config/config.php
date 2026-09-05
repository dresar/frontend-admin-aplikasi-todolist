<?php
// Mulai output buffering untuk menghindari error header
ob_start();

// Konfigurasi Admin Panel
define('ADMIN_SECRET_CODE', 'admin123');
define('APP_NAME', 'Admin Panel Todolist');
define('BASE_URL', 'http://localhost/ADMIN');

// Konfigurasi Session
session_start();

// Fungsi untuk memeriksa apakah admin sudah login
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Fungsi untuk redirect
function redirect($url) {
    header("Location: " . BASE_URL . "/" . $url);
    exit();
}

// Fungsi untuk menampilkan pesan
function setMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

// Fungsi untuk mendapatkan pesan
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

// Fungsi untuk mencatat log aktivitas
function logActivity($admin_id, $action, $details = '') {
    try {
        require_once __DIR__ . '/../models/LogModel.php';
        $logModel = new LogModel();
        
        // Pastikan admin_id valid
        if (!$admin_id || !is_numeric($admin_id)) {
            // Jika admin_id tidak valid, gunakan ID admin default (1)
            $admin_id = 1;
        }
        
        return $logModel->addLog($admin_id, $action, $details);
    } catch (Exception $e) {
        // Tangani error tanpa menghentikan aplikasi
        error_log('Error saat mencatat aktivitas: ' . $e->getMessage());
        return false;
    }
}
?>