<?php
require_once __DIR__ . '/../models/UserModel.php';

/**
 * Fungsi untuk mencatat aktivitas pengguna
 * 
 * @param int $userId ID pengguna yang melakukan aktivitas
 * @param string $activityType Jenis aktivitas (login, logout, create, update, delete, dll)
 * @param string $description Deskripsi aktivitas
 * @return bool True jika berhasil, false jika gagal
 */
function logUserActivity($userId, $activityType, $description = '') {
    $userModel = new UserModel();
    return $userModel->logUserActivity($userId, $activityType, $description);
}

/**
 * Fungsi untuk mencatat aktivitas login
 * 
 * @param int $userId ID pengguna yang login
 * @return bool True jika berhasil, false jika gagal
 */
function logLogin($userId) {
    return logUserActivity($userId, 'login', 'Pengguna berhasil login');
}

/**
 * Fungsi untuk mencatat aktivitas logout
 * 
 * @param int $userId ID pengguna yang logout
 * @return bool True jika berhasil, false jika gagal
 */
function logLogout($userId) {
    return logUserActivity($userId, 'logout', 'Pengguna berhasil logout');
}

/**
 * Fungsi untuk mencatat aktivitas pembuatan data
 * 
 * @param int $userId ID pengguna yang membuat data
 * @param string $dataType Jenis data yang dibuat (user, task, category, dll)
 * @param string $details Detail data yang dibuat
 * @return bool True jika berhasil, false jika gagal
 */
function logCreate($userId, $dataType, $details = '') {
    return logUserActivity($userId, 'create_' . $dataType, 'Membuat ' . $dataType . ': ' . $details);
}

/**
 * Fungsi untuk mencatat aktivitas pembaruan data
 * 
 * @param int $userId ID pengguna yang memperbarui data
 * @param string $dataType Jenis data yang diperbarui (user, task, category, dll)
 * @param string $details Detail data yang diperbarui
 * @return bool True jika berhasil, false jika gagal
 */
function logUpdate($userId, $dataType, $details = '') {
    return logUserActivity($userId, 'update_' . $dataType, 'Memperbarui ' . $dataType . ': ' . $details);
}

/**
 * Fungsi untuk mencatat aktivitas penghapusan data
 * 
 * @param int $userId ID pengguna yang menghapus data
 * @param string $dataType Jenis data yang dihapus (user, task, category, dll)
 * @param string $details Detail data yang dihapus
 * @return bool True jika berhasil, false jika gagal
 */
function logDelete($userId, $dataType, $details = '') {
    return logUserActivity($userId, 'delete_' . $dataType, 'Menghapus ' . $dataType . ': ' . $details);
}