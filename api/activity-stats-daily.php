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

// Mendapatkan statistik aktivitas harian
$activityStats = $userModel->getUserActivityStats($days);

// Memformat data untuk respons
$formattedStats = [];

// Mengisi data untuk jumlah hari tertentu
for ($i = $days - 1; $i >= 0; $i--) {
    $currentDate = date('Y-m-d', strtotime("-$i days"));
    $count = 0;
    
    // Mencari data untuk tanggal ini
    foreach ($activityStats as $stat) {
        if ($stat['date'] == $currentDate) {
            $count = (int)$stat['count'];
            break;
        }
    }
    
    $formattedStats[] = [
        'date' => $currentDate,
        'formatted_date' => date('d/m/Y', strtotime($currentDate)),
        'count' => $count
    ];
}

// Mengembalikan data dalam format JSON
echo json_encode([
    'success' => true,
    'data' => $formattedStats,
    'days' => $days
]);