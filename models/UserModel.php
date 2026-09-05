<?php
require_once __DIR__ . '/../config/database.php';

class UserModel {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
        
        // Buat tabel user_activities jika belum ada
        $this->createUserActivitiesTable();
    }
    
    // Membuat tabel user_activities jika belum ada
    private function createUserActivitiesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `user_activities` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `activity_type` VARCHAR(50) NOT NULL,
            `description` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        )";
        
        return $this->conn->query($sql);
    }
    
    // Metode untuk menambahkan metode dinamis (untuk kompatibilitas dengan profile.php)
    public function addMethod($name, $callback) {
        $this->$name = $callback->bindTo($this, get_class($this));
    }
    
    // Mendapatkan semua pengguna
    public function getAllUsers() {
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
        $result = $this->conn->query($sql);
        
        $users = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        return $users;
    }
    
    // Mendapatkan jumlah total pengguna
    public function getTotalUsers() {
        $sql = "SELECT COUNT(*) as total FROM users";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        
        return $row['total'];
    }
    
    // Mendapatkan detail pengguna berdasarkan ID
    public function getUserById($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    // Mendapatkan detail pengguna berdasarkan username
    public function getUserByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    // Mengubah status pengguna (aktif/nonaktif)
    public function updateUserStatus($id, $status) {
        $sql = "UPDATE users SET is_active = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $status, $id);
        
        return $stmt->execute();
    }
    
    // Mendapatkan token pengguna
    public function getUserTokens($userId) {
        $sql = "SELECT * FROM tokens WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $tokens = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tokens[] = $row;
            }
        }
        
        return $tokens;
    }
    
    // Mendapatkan statistik aktivitas pengguna (untuk grafik)
    public function getUserActivityStats($days = 30) {
        // Periksa apakah tabel user_activities ada
        if (!$this->tableExists('user_activities')) {
            // Jika tabel tidak ada, kembalikan array kosong
            return [];
        }
        
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM user_activities 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) 
                GROUP BY DATE(created_at) 
                ORDER BY date";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $stats = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $stats[] = $row;
            }
        }
        
        return $stats;
    }
    
    // Mendapatkan statistik aktivitas pengguna berdasarkan jenis aktivitas
    public function getActivityStatsByType($days = 30) {
        // Periksa apakah tabel user_activities ada
        if (!$this->tableExists('user_activities')) {
            return [];
        }
        
        $sql = "SELECT activity_type, COUNT(*) as count 
                FROM user_activities 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) 
                GROUP BY activity_type 
                ORDER BY count DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $stats = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $stats[] = $row;
            }
        }
        
        return $stats;
    }
    
    // Memperbarui profil pengguna
    public function updateProfile($userId, $username, $email, $name, $profilePhoto = null) {
        $sql = "UPDATE users SET username = ?, email = ?, name = ?";
        $params = [$username, $email, $name];
        $types = "sss";
        
        // Jika ada foto profil, tambahkan ke query
        if ($profilePhoto !== null) {
            $sql .= ", profile_photo = ?";
            $params[] = $profilePhoto;
            $types .= "s";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $userId;
        $types .= "i";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        return $stmt->execute();
    }
    
    // Verifikasi password pengguna
    public function verifyPassword($userId, $password) {
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            return password_verify($password, $user['password']);
        }
        
        return false;
    }
    
    // Memeriksa apakah tabel sudah ada
    private function tableExists($tableName) {
        $result = $this->conn->query("SHOW TABLES LIKE '{$tableName}'");
        return $result->num_rows > 0;
    }
    
    // Menghapus pengguna
    public function deleteUser($userId) {
        // Hapus token pengguna terlebih dahulu
        $sql = "DELETE FROM tokens WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Hapus tugas pengguna
        $sql = "DELETE FROM tasks WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Hapus aktivitas pengguna jika tabel ada
        if ($this->tableExists('user_activities')) {
            $sql = "DELETE FROM user_activities WHERE user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
        }
        
        // Hapus pengguna
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        
        return $stmt->execute();
    }
    
    // Mendapatkan pengguna baru dalam 7 hari terakhir
    public function getNewUsers($days = 7) {
        $sql = "SELECT * FROM users 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) 
                ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $users = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        return $users;
    }
    
    // Menambahkan pengguna baru
    public function addUser($username, $email, $password, $is_active = 1) {
        // Cek apakah username sudah ada
        $checkSql = "SELECT id FROM users WHERE username = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            return ['success' => false, 'message' => 'Username sudah digunakan'];
        }
        
        // Cek apakah email sudah ada
        $checkSql = "SELECT id FROM users WHERE email = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            return ['success' => false, 'message' => 'Email sudah digunakan'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate API key
        $apiKey = bin2hex(random_bytes(16));
        
        // Insert user
        $sql = "INSERT INTO users (username, email, password, is_active, api_key, role, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, 'user', NOW(), NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssss", $username, $email, $hashedPassword, $is_active, $apiKey);
        
        if ($stmt->execute()) {
            return ['success' => true, 'user_id' => $this->conn->insert_id];
        } else {
            return ['success' => false, 'message' => 'Gagal menambahkan pengguna: ' . $this->conn->error];
        }
    }
    
    // Mencatat aktivitas pengguna
    public function logUserActivity($userId, $activityType, $description = '') {
        // Periksa apakah tabel user_activities ada
        if (!$this->tableExists('user_activities')) {
            $this->createUserActivitiesTable();
        }
        
        // Catat aktivitas pengguna
        $sql = "INSERT INTO user_activities (user_id, activity_type, description, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $userId, $activityType, $description);
        
        return $stmt->execute();
    }
    
    // Mendapatkan aktivitas pengguna berdasarkan tanggal
    public function getUserActivitiesByDate($date) {
        // Periksa apakah tabel user_activities ada
        if (!$this->tableExists('user_activities')) {
            return [];
        }
        
        $sql = "SELECT ua.*, u.username 
                FROM user_activities ua 
                LEFT JOIN users u ON ua.user_id = u.id 
                WHERE DATE(ua.created_at) = ? 
                ORDER BY ua.created_at DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $activities = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
        }
        
        return $activities;
    }
    
    // Mendapatkan aktivitas pengguna berdasarkan ID pengguna
    public function getUserActivitiesByUserId($userId, $limit = 50) {
        // Periksa apakah tabel user_activities ada
        if (!$this->tableExists('user_activities')) {
            return [];
        }
        
        $sql = "SELECT ua.*, u.username 
                FROM user_activities ua 
                LEFT JOIN users u ON ua.user_id = u.id 
                WHERE ua.user_id = ? 
                ORDER BY ua.created_at DESC 
                LIMIT ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $activities = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
        }
        
        return $activities;
    }
    
    // Mendapatkan aktivitas pengguna berdasarkan jenis aktivitas
    public function getUserActivitiesByType($activityType, $limit = 50) {
        // Periksa apakah tabel user_activities ada
        if (!$this->tableExists('user_activities')) {
            return [];
        }
        
        $sql = "SELECT ua.*, u.username 
                FROM user_activities ua 
                LEFT JOIN users u ON ua.user_id = u.id 
                WHERE ua.activity_type LIKE ? 
                ORDER BY ua.created_at DESC 
                LIMIT ?";
                
        $activityTypeParam = "%$activityType%";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $activityTypeParam, $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $activities = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
        }
        
        return $activities;
    }
    
    // Menutup koneksi database
    public function __destruct() {
        $this->conn->close();
    }
}
?>