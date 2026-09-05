<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/activity_logger.php';

// Log aktivitas logout jika admin sudah login
if (isLoggedIn()) {
    // Log aktivitas logout pengguna
    logLogout($_SESSION['admin_id']);
    // Log aktivitas admin
    logActivity($_SESSION['admin_id'], 'logout', 'Admin logout');
}

// Hapus semua data session
session_unset();
session_destroy();

// Redirect ke halaman login
redirect('login.php');
?>