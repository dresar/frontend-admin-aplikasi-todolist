<?php
// Redirect ke dashboard jika sudah login, atau ke halaman login jika belum
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}