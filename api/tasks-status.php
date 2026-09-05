<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/TaskModel.php';

header('Content-Type: application/json');

// Inisialisasi model
$taskModel = new TaskModel();

// Mendapatkan statistik tugas berdasarkan status
$taskStats = $taskModel->getTaskCountByStatus();

// Mengembalikan data dalam format JSON
echo json_encode([
    'completed' => (int)$taskStats['completed'],
    'in_progress' => (int)$taskStats['in_progress'],
    'not_started' => (int)$taskStats['not_started'],
    'pending' => (int)$taskStats['pending'],
    'cancelled' => (int)$taskStats['cancelled']
]);