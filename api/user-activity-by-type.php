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
$activityType = isset($_GET['type']) ? $_GET['type'] : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

// Validasi parameter
if (empty($activityType)) {
    echo json_encode(['error' => 'Activity type is required']);
    exit;
}

// Batasi limit maksimum untuk mencegah overload
if ($limit > 100) {
    $limit = 100;
}

// Inisialisasi model
$userModel = new UserModel();

// Mendapatkan aktivitas berdasarkan jenis
$activities = $userModel->getUserActivitiesByType($activityType, $limit);

// Memformat data untuk respons
$formattedActivities = [];
foreach ($activities as $activity) {
    $formattedActivities[] = [
        'id' => $activity['id'],
        'user_id' => $activity['user_id'],
        'username' => $activity['username'] ?? 'Unknown',
        'activity_type' => $activity['activity_type'],
        'description' => $activity['description'],
        'ip_address' => $activity['ip_address'],
        'user_agent' => $activity['user_agent'],
        'created_at' => $activity['created_at']
    ];
}

// Mengembalikan data dalam format JSON
echo json_encode([
    'success' => true,
    'data' => $formattedActivities,
    'count' => count($formattedActivities),
    'type' => $activityType
]);