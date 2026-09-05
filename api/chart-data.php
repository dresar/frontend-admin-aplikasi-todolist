<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';

header('Content-Type: application/json');

// Inisialisasi model
$userModel = new UserModel();

// Mendapatkan data aktivitas pengguna untuk 30 hari terakhir
$activityStats = $userModel->getUserActivityStats(30);

// Inisialisasi array untuk label dan nilai
$labels = [];
$values = [];

// Mengisi data untuk 30 hari terakhir
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = 0;
    
    // Mencari data untuk tanggal ini
    foreach ($activityStats as $stat) {
        if ($stat['date'] == $date) {
            $count = (int)$stat['count'];
            break;
        }
    }
    
    // Format tanggal untuk label
    if ($i == 0) {
        $labels[] = 'Hari Ini';
    } else if ($i == 1) {
        $labels[] = '1 Hari Lalu';
    } else {
        $labels[] = "$i Hari Lalu";
    }
    
    $values[] = $count;
}

// Mengembalikan data dalam format JSON
echo json_encode([
    'labels' => array_reverse($labels),
    'values' => array_reverse($values)
]);