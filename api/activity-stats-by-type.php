<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../includes/functions.php';

// Mengatur header untuk respons JSON
header('Content-Type: application/json');

// Memeriksa apakah pengguna sudah login
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Mendapatkan parameter dari request
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;

// Batasi jumlah hari maksimum untuk mencegah overload
if ($days > 365) {
    $days = 365;
}

// Inisialisasi model
$userModel = new UserModel();

// Mendapatkan statistik aktivitas berdasarkan jenis
$activityStats = $userModel->getActivityStatsByType($days);

// Memformat data untuk respons
$formattedStats = [];
foreach ($activityStats as $stat) {
    $formattedStats[] = [
        'activity_type' => $stat['activity_type'],
        'count' => (int)$stat['count']
    ];
}

// Mengembalikan data dalam format JSON
echo json_encode([
    'success' => true,
    'data' => $formattedStats,
    'days' => $days
]);