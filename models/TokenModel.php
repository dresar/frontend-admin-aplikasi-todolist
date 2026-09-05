<?php
require_once __DIR__ . '/../config/database.php';

class TokenModel {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Memeriksa apakah tabel sudah ada
    private function tableExists($tableName) {
        $result = $this->conn->query("SHOW TABLES LIKE '{$tableName}'");
        return $result->num_rows > 0;
    }
    
    // Membuat tabel tokens jika belum ada
    private function createTokensTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `tokens` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `token` VARCHAR(255) NOT NULL,
            `token_type` ENUM('jwt', 'api_key') DEFAULT 'jwt',
            `description` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        )";
        
        return $this->conn->query($sql);
    }
    
    // Membuat tabel admin_tokens jika belum ada
    private function createAdminTokensTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `admin_tokens` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `admin_id` INT NOT NULL,
            `token` VARCHAR(255) NOT NULL,
            `token_type` ENUM('jwt', 'api_key') DEFAULT 'jwt',
            `description` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
        )";
        
        return $this->conn->query($sql);
    }
    
    // Mendapatkan semua token
    public function getAllTokens() {
        // Cek apakah tabel tokens sudah ada
        if (!$this->tableExists('tokens')) {
            $this->createTokensTable();
        }
        
        // Admin tidak dapat memiliki token, jadi hanya menampilkan token pengguna
        $sql = "SELECT t.*, u.username as user_username, NULL as admin_username, 
                'user' as type, token_type 
                FROM tokens t 
                LEFT JOIN users u ON t.user_id = u.id 
                ORDER BY t.created_at DESC";
        
        $result = $this->conn->query($sql);
        $tokens = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tokens[] = $row;
            }
        }
        
        return $tokens;
    }
    
    // Mendapatkan semua token pengguna
    public function getAllUserTokens() {
        // Cek apakah tabel tokens sudah ada
        if (!$this->tableExists('tokens')) {
            $this->createTokensTable();
        }
        
        $sql = "SELECT t.*, u.username 
                FROM tokens t 
                LEFT JOIN users u ON t.user_id = u.id 
                ORDER BY t.created_at DESC";
        
        $result = $this->conn->query($sql);
        $tokens = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tokens[] = $row;
            }
        }
        
        return $tokens;
    }
    
    // Mendapatkan semua token admin - fungsi ini dipertahankan untuk kompatibilitas tetapi selalu mengembalikan array kosong
    public function getAllAdminTokens() {
        // Admin tidak dapat memiliki token
        return [];
    }
    
    // Mendapatkan jumlah total token
    public function getTotalTokens() {
        // Cek apakah tabel tokens sudah ada
        if (!$this->tableExists('tokens')) {
            $this->createTokensTable();
        }
        
        // Admin tidak dapat memiliki token, jadi hanya menghitung token pengguna
        $sql = "SELECT COUNT(*) as total FROM tokens";
        
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['total'];
        }
        
        return 0;
    }
    
    // Mendapatkan jumlah total token pengguna
    public function getTotalUserTokens() {
        // Cek apakah tabel tokens sudah ada
        if (!$this->tableExists('tokens')) {
            $this->createTokensTable();
            return 0;
        }
        
        $sql = "SELECT COUNT(*) as total FROM tokens";
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['total'];
        }
        
        return 0;
    }
    
    // Mendapatkan jumlah total token admin - fungsi ini dipertahankan untuk kompatibilitas tetapi selalu mengembalikan 0
    public function getTotalAdminTokens() {
        // Admin tidak dapat memiliki token
        return 0;
    }
    
    // Mendapatkan token pengguna berdasarkan ID
    public function getUserTokenById($id) {
        // Cek apakah tabel tokens sudah ada
        if (!$this->tableExists('tokens')) {
            return null;
        }
        
        $sql = "SELECT t.*, u.username 
                FROM tokens t 
                JOIN users u ON t.user_id = u.id 
                WHERE t.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            error_log("Prepare statement error: " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $token = $result->fetch_assoc();
            $stmt->close();
            return $token;
        }
        
        $stmt->close();
        return null;
    }
    
    // Mendapatkan token admin berdasarkan ID - fungsi ini dipertahankan untuk kompatibilitas tetapi selalu mengembalikan null
    public function getAdminTokenById($id) {
        // Admin tidak dapat memiliki token
        return null;
    }
    
    // Membuat token pengguna baru
    public function createUserToken($userId, $description = '', $tokenType = 'jwt') {
        // Cek apakah tabel tokens sudah ada
        if (!$this->tableExists('tokens')) {
            $this->createTokensTable();
        }
        
        // Jika tipe token adalah api_key, periksa apakah pengguna sudah memiliki API key
        if ($tokenType === 'api_key') {
            $sql = "SELECT COUNT(*) as count FROM tokens WHERE user_id = ? AND token_type = 'api_key'";
            $stmt = $this->conn->prepare($sql);
            
            if ($stmt === false) {
                error_log("Prepare statement error: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                // Pengguna sudah memiliki API key, tidak diizinkan membuat yang baru
                $stmt->close();
                return false;
            }
            
            $stmt->close();
        }
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        
        $sql = "INSERT INTO tokens (user_id, token, token_type, description, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            error_log("Prepare statement error: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("isss", $userId, $token, $tokenType, $description);
        
        if ($stmt->execute()) {
            $stmt->close();
            return $token;
        }
        
        $stmt->close();
        return false;
    }
    
    // Membuat token admin baru - fungsi ini tidak lagi digunakan karena admin tidak dapat memiliki token
    public function createAdminToken($adminId, $description = '', $tokenType = 'api_key') {
        // Fungsi ini tidak lagi digunakan karena admin tidak dapat memiliki token
        error_log("Attempt to create admin token - this function is deprecated");
        return false;
    }
    
    // Mencabut token pengguna
    public function revokeUserToken($tokenId) {
        // Cek apakah tabel tokens sudah ada
        if (!$this->tableExists('tokens')) {
            return false;
        }
        
        $sql = "DELETE FROM tokens WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            error_log("Prepare statement error: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("i", $tokenId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    // Mencabut token admin - fungsi ini tidak lagi digunakan karena admin tidak dapat memiliki token
    public function revokeAdminToken($tokenId) {
        // Fungsi ini tidak lagi digunakan karena admin tidak dapat memiliki token
        error_log("Attempt to revoke admin token - this function is deprecated");
        return false;
    }
    
    // Mendapatkan detail penggunaan token
    public function getTokenUsage($token, $type = 'user') {
        // Admin tidak dapat memiliki token, jadi hanya mengembalikan penggunaan token pengguna
        if ($type !== 'user') {
            return [];
        }
        
        $tableName = 'token_usage';
        
        // Cek apakah tabel penggunaan token sudah ada
        if (!$this->tableExists($tableName)) {
            // Buat tabel penggunaan token jika belum ada
            $this->createTokenUsageTable('user');
            return [];
        }
        
        $sql = "SELECT * FROM token_usage WHERE token = ? ORDER BY used_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            error_log("Prepare statement error: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $usage = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $usage[] = $row;
            }
        }
        
        $stmt->close();
        return $usage;
    }
    
    // Membuat tabel penggunaan token
    private function createTokenUsageTable($type = 'user') {
        // Admin tidak dapat memiliki token, jadi hanya membuat tabel penggunaan token pengguna
        $sql = "CREATE TABLE IF NOT EXISTS `token_usage` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `token` VARCHAR(255) NOT NULL,
            `endpoint` VARCHAR(255) NOT NULL,
            `ip_address` VARCHAR(45) NOT NULL,
            `user_agent` TEXT,
            `used_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`token`)
        )";
        
        return $this->conn->query($sql);
    }
    
    // Menutup koneksi database
    public function __destruct() {
        $this->conn->close();
    }
}
?>