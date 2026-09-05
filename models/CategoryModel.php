<?php
require_once __DIR__ . '/../config/database.php';

class CategoryModel {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Mendapatkan semua kategori
    public function getAllCategories($userId = null) {
        if ($userId) {
            $sql = "SELECT c.*, COUNT(t.id) as task_count 
                    FROM categories c 
                    LEFT JOIN tasks t ON c.id = t.category_id 
                    WHERE c.user_id = ? 
                    GROUP BY c.id 
                    ORDER BY c.name ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            $result = $stmt->get_result();
        } else {
            $sql = "SELECT c.*, COUNT(t.id) as task_count 
                    FROM categories c 
                    LEFT JOIN tasks t ON c.id = t.category_id 
                    GROUP BY c.id 
                    ORDER BY c.id DESC";
            
            $result = $this->conn->query($sql);
        }
        
        $categories = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        
        return $categories;
    }
    
    // Mendapatkan kategori berdasarkan user_id
    public function getCategoriesByUserId($userId) {
        $sql = "SELECT c.*, COUNT(t.id) as task_count 
                FROM categories c 
                LEFT JOIN tasks t ON c.id = t.category_id 
                WHERE c.user_id = ? 
                GROUP BY c.id 
                ORDER BY c.name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $categories = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        
        return $categories;
    }
    
    // Mendapatkan jumlah total kategori
    public function getTotalCategories() {
        $sql = "SELECT COUNT(*) as total FROM categories";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        
        return $row['total'];
    }
    
    // Mendapatkan detail kategori berdasarkan ID
    public function getCategoryById($id) {
        $sql = "SELECT c.*, COUNT(t.id) as task_count 
                FROM categories c 
                LEFT JOIN tasks t ON c.id = t.category_id 
                WHERE c.id = ? 
                GROUP BY c.id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    // Menambahkan kategori baru
    public function addCategory($name, $description = '', $userId = null, $color = '#3498db') {
        $sql = "INSERT INTO categories (name, description, user_id, color, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssis", $name, $description, $userId, $color);
        
        return $stmt->execute();
    }
    
    // Mengupdate kategori
    public function updateCategory($id, $name, $description, $color = null, $userId = null) {
        // Debug: Log nilai parameter sebelum diproses
        error_log("BEFORE - ID: $id, Name: $name, Description: $description, Color: $color, UserID: $userId");
        
        // Konversi user_id menjadi NULL jika string kosong atau 0
        if ($userId === '' || $userId === '0' || $userId === 0) {
            $userId = null;
        }
        
        // Konversi color menjadi default jika kosong atau 0
        if ($color === '' || $color === '0' || $color === 0 || $color === null) {
            $color = '#3498db'; // Warna default
        }
        
        // Debug: Log nilai parameter setelah diproses
        error_log("AFTER - ID: $id, Name: $name, Description: $description, Color: $color, UserID: $userId");
        
        // Verifikasi data sebelum update
        $checkSql = "SELECT * FROM categories WHERE id = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $oldData = $result->fetch_assoc();
        error_log("OLD DATA - ID: {$oldData['id']}, Name: {$oldData['name']}, Color: {$oldData['color']}");
        
        // Coba update hanya warna terlebih dahulu
        $colorUpdateSql = "UPDATE categories SET color = ? WHERE id = ?";
        $colorUpdateStmt = $this->conn->prepare($colorUpdateSql);
        $colorUpdateStmt->bind_param("si", $color, $id);
        $colorUpdateResult = $colorUpdateStmt->execute();
        error_log("Color update result: " . ($colorUpdateResult ? 'success' : 'failed') . ", Error: " . $this->conn->error);
        
        // Kemudian update data lainnya
        if ($userId === null) {
            $sql = "UPDATE categories SET name = ?, description = ?, user_id = NULL WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssi", $name, $description, $id);
        } else {
            $sql = "UPDATE categories SET name = ?, description = ?, user_id = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssii", $name, $description, $userId, $id);
        }
        
        error_log("SQL Query: $sql");
        $result = $stmt->execute();
        error_log("Update result: " . ($result ? 'success' : 'failed') . ", Error: " . $this->conn->error);
        
        // Verifikasi data setelah update
        $checkSql = "SELECT * FROM categories WHERE id = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result2 = $checkStmt->get_result();
        $newData = $result2->fetch_assoc();
        error_log("NEW DATA AFTER UPDATE - ID: {$newData['id']}, Name: {$newData['name']}, Color: {$newData['color']}");
        
        return $colorUpdateResult && $result;
    }
    
    // Menghapus kategori
    public function deleteCategory($id, $userId = null) {
        // Cek apakah kategori memiliki tugas
        $sql = "SELECT COUNT(*) as count FROM tasks WHERE category_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            // Kategori memiliki tugas, tidak bisa dihapus
            return false;
        }
        
        // Hapus kategori jika tidak memiliki tugas
        if ($userId) {
            // Hanya hapus kategori milik user tertentu
            $sql = "DELETE FROM categories WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $id, $userId);
        } else {
            $sql = "DELETE FROM categories WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
        }
        
        return $stmt->execute();
    }
    
    // Mendapatkan kategori dengan jumlah tugas terbanyak
    public function getTopCategories($limit = 5) {
        $sql = "SELECT c.*, COUNT(t.id) as task_count 
                FROM categories c 
                LEFT JOIN tasks t ON c.id = t.category_id 
                GROUP BY c.id 
                ORDER BY task_count DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $categories = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        
        return $categories;
    }
    
    // Menutup koneksi database
    public function __destruct() {
        $this->conn->close();
    }
}
?>