<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Mendapatkan koneksi database
$conn = getConnection();

// Mendapatkan parameter tanggal dari request
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode([
        'success' => false,
        'message' => 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD'
    ]);
    exit;
}

// Query untuk mendapatkan aktivitas pengguna berdasarkan tanggal
$sql = "SELECT ua.*, u.username 
        FROM user_activities ua 
        LEFT JOIN users u ON ua.user_id = u.id 
        WHERE DATE(ua.created_at) = ? 
        ORDER BY ua.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $date);
$stmt->execute();

$result = $stmt->get_result();
$activities = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $activities[] = [
            'id' => $row['id'],
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'activity_type' => $row['activity_type'],
            'description' => $row['description'],
            'created_at' => $row['created_at']
        ];
    }
}

// Mengembalikan data dalam format JSON
echo json_encode([
    'success' => true,
    'date' => $date,
    'activities' => $activities,
    'total' => count($activities)
]);

// Menutup koneksi database
$conn->close();