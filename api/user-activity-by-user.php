<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';

header('Content-Type: application/json');

// Mendapatkan parameter user_id dari request
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

// Validasi user_id
if ($userId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID pengguna tidak valid'
    ]);
    exit;
}

// Inisialisasi model
$userModel = new UserModel();

// Mendapatkan data pengguna
$user = $userModel->getUserById($userId);
if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => 'Pengguna tidak ditemukan'
    ]);
    exit;
}

// Mendapatkan aktivitas pengguna
$activities = $userModel->getUserActivitiesByUserId($userId, $limit);

// Mengembalikan data dalam format JSON
echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'name' => $user['name'] ?? '',
        'email' => $user['email']
    ],
    'activities' => $activities,
    'total' => count($activities),
    'limit' => $limit
]);