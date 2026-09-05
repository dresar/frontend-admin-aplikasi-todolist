<?php
require_once __DIR__ . '/../config/database.php';

class AdminModel {
    private $conn;
    private $dynamicMethods = [];
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Menambahkan metode dinamis
    public function addMethod($name, $func) {
        $this->dynamicMethods[$name] = $func->bindTo($this, get_class($this));
    }
    
    // Menangani pemanggilan metode dinamis
    public function __call($name, $arguments) {
        if (isset($this->dynamicMethods[$name])) {
            return call_user_func_array($this->dynamicMethods[$name], $arguments);
        }
        
        throw new Exception("Method $name does not exist");
    }
    
    // Mendapatkan semua admin
    public function getAllAdmins() {
        $sql = "SELECT * FROM admins ORDER BY created_at DESC";
        $result = $this->conn->query($sql);
        
        $admins = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $admins[] = $row;
            }
        }
        
        return $admins;
    }
    
    // Mendapatkan jumlah total admin
    public function getTotalAdmins() {
        $sql = "SELECT COUNT(*) as total FROM admins";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        
        return $row['total'];
    }
    
    // Memeriksa apakah email sudah digunakan oleh admin lain
    public function checkEmailExists($email, $adminId) {
        $sql = "SELECT id FROM admins WHERE email = ? AND id != ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            error_log("Prepare statement error: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("si", $email, $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = ($result && $result->num_rows > 0);
        $stmt->close();
        
        return $exists;
    }
    
    // Memeriksa apakah username sudah digunakan oleh admin lain
    public function checkUsernameExists($username, $adminId) {
        $sql = "SELECT id FROM admins WHERE username = ? AND id != ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            error_log("Prepare statement error: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("si", $username, $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = ($result && $result->num_rows > 0);
        $stmt->close();
        
        return $exists;
    }
    
    // Mendapatkan detail admin berdasarkan ID
    public function getAdminById($id) {
        $sql = "SELECT * FROM admins WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    // Mendapatkan admin berdasarkan username
    public function getAdminByUsername($username) {
        $sql = "SELECT * FROM admins WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    // Mendapatkan admin berdasarkan email
    public function getAdminByEmail($email) {
        $sql = "SELECT * FROM admins WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    // Menambahkan admin baru
    public function addAdmin($username, $email, $password, $name) {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO admins (username, email, password, name, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $email, $hashedPassword, $name);
        
        return $stmt->execute();
    }
    
    // Mengupdate informasi admin
    public function updateAdmin($id, $name, $email) {
        $sql = "UPDATE admins SET name = ?, email = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $name, $email, $id);
        
        return $stmt->execute();
    }
    
    // Mengupdate profil admin lengkap
    public function updateProfile($adminId, $username, $email, $name, $profilePhoto) {
        $sql = "UPDATE admins SET username = ?, email = ?, name = ?, profile_photo = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssi", $username, $email, $name, $profilePhoto, $adminId);
        
        return $stmt->execute();
    }
    
    // Mengubah password admin
    public function updatePassword($id, $password) {
        // Hash password baru
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE admins SET password = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $id);
        
        return $stmt->execute();
    }
    
    // Menghapus admin
    public function deleteAdmin($id) {
        $sql = "DELETE FROM admins WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    // Verifikasi login admin
    public function verifyLogin($username, $password) {
        $admin = $this->getAdminByUsername($username);
        
        if ($admin && password_verify($password, $admin['password'])) {
            return $admin;
        }
        
        return false;
    }
    
    // Verifikasi password admin berdasarkan ID
    public function verifyPassword($adminId, $password) {
        $sql = "SELECT password FROM admins WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            return password_verify($password, $admin['password']);
        }
        
        return false;
    }
    
    // Mendapatkan token admin
    public function getAdminTokens($adminId) {
        $sql = "SELECT * FROM admin_tokens WHERE admin_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $adminId);
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
    
    // Membuat token admin baru
    public function createAdminToken($adminId, $description = '') {
        // Generate token
        $token = bin2hex(random_bytes(32));
        
        $sql = "INSERT INTO admin_tokens (admin_id, token, description, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $adminId, $token, $description);
        
        if ($stmt->execute()) {
            return $token;
        }
        
        return false;
    }
    
    // Mencabut token admin
    public function revokeAdminToken($tokenId) {
        $sql = "DELETE FROM admin_tokens WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $tokenId);
        
        return $stmt->execute();
    }
    
    // Menutup koneksi database
    public function __destruct() {
        $this->conn->close();
    }
    
    // Menghapus user (admin) dan data terkait
    public function deleteUser($adminId) {
        // Hapus token admin terlebih dahulu
        $sql = "DELETE FROM tokens WHERE admin_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        
        // Hapus aktivitas admin
        $sql = "DELETE FROM activities WHERE admin_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        
        // Hapus admin
        $sql = "DELETE FROM admins WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $adminId);
        return $stmt->execute();
    }
}
?>