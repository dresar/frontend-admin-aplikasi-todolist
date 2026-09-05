<?php
require_once __DIR__ . '/../config/database.php';

class TaskModel {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Mendapatkan semua tugas
    public function getAllTasks($limit = 100, $offset = 0, $userId = null) {
        if ($userId) {
            // Jika user_id diberikan, hanya tampilkan tugas dengan kategori milik user tersebut atau kategori global
            $sql = "SELECT t.*, c.name as category_name, u.username as user_username 
                    FROM tasks t 
                    LEFT JOIN categories c ON t.category_id = c.id 
                    LEFT JOIN users u ON t.user_id = u.id 
                    WHERE t.user_id = ? OR c.user_id IS NULL OR c.user_id = t.user_id 
                    ORDER BY t.created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $userId, $limit, $offset);
        } else {
            $sql = "SELECT t.*, c.name as category_name, u.username as user_username 
                    FROM tasks t 
                    LEFT JOIN categories c ON t.category_id = c.id 
                    LEFT JOIN users u ON t.user_id = u.id 
                    ORDER BY t.created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        
        $result = $stmt->get_result();
        $tasks = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }
        }
        
        return $tasks;
    }
    
    // Mendapatkan jumlah total tugas
    public function getTotalTasks($categoryId = null) {
        if ($categoryId) {
            $sql = "SELECT COUNT(*) as total FROM tasks WHERE category_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql = "SELECT COUNT(*) as total FROM tasks";
            $result = $this->conn->query($sql);
        }
        
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    
    // Mendapatkan jumlah tugas berdasarkan status
    public function getTaskCountByStatus($categoryId = null) {
        $counts = [
            'completed' => 0,
            'in_progress' => 0,
            'pending' => 0,
            'cancelled' => 0,
            'not_started' => 0  // Menambahkan status not_started
        ];
        
        if ($categoryId) {
            $sql = "SELECT status, COUNT(*) as count FROM tasks WHERE category_id = ? GROUP BY status";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql = "SELECT status, COUNT(*) as count FROM tasks GROUP BY status";
            $result = $this->conn->query($sql);
        }
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $counts[$row['status']] = (int)$row['count'];
            }
        }
        
        return $counts;
    }
    
    // Mendapatkan detail tugas berdasarkan ID
    public function getTaskById($id) {
        $sql = "SELECT t.*, c.name as category_name, u.username as user_username 
                FROM tasks t 
                LEFT JOIN categories c ON t.category_id = c.id 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE t.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    // Mendapatkan tugas berdasarkan pengguna
    public function getTasksByUser($userId, $limit = 100) {
        // Tampilkan tugas milik user dan hanya kategori yang dimiliki user atau kategori global
        $sql = "SELECT t.*, c.name as category_name 
                FROM tasks t 
                LEFT JOIN categories c ON t.category_id = c.id 
                WHERE t.user_id = ? AND (c.user_id IS NULL OR c.user_id = ?) 
                ORDER BY t.created_at DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $userId, $userId, $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $tasks = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }
        }
        
        return $tasks;
    }
    
    // Mendapatkan tugas berdasarkan kategori
    public function getTasksByCategory($categoryId, $limit = 100) {
        $sql = "SELECT t.*, u.username as user_username 
                FROM tasks t 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE t.category_id = ? 
                ORDER BY t.created_at DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $categoryId, $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $tasks = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }
        }
        
        return $tasks;
    }
    
    // Menambahkan tugas baru
    public function addTask($userId, $title, $description, $categoryId, $dueDate, $priority, $status = 'pending') {
        // Verifikasi bahwa kategori tersedia untuk pengguna ini (kategori global atau milik pengguna)
        $sql = "SELECT id, user_id FROM categories WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $category = $result->fetch_assoc();
            // Jika kategori memiliki user_id dan tidak sama dengan user_id saat ini, gunakan kategori default
            if ($category['user_id'] !== null && $category['user_id'] != $userId) {
                // Cari kategori default (tanpa user_id)
                $sql = "SELECT id FROM categories WHERE user_id IS NULL LIMIT 1";
                $result = $this->conn->query($sql);
                if ($result->num_rows > 0) {
                    $defaultCategory = $result->fetch_assoc();
                    $categoryId = $defaultCategory['id'];
                }
            }
        }
        
        $sql = "INSERT INTO tasks (user_id, title, description, category_id, due_date, priority, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ississs", $userId, $title, $description, $categoryId, $dueDate, $priority, $status);
        
        return $stmt->execute();
    }
    
    // Mengupdate tugas
    public function updateTask($id, $title, $description, $categoryId, $dueDate, $priority, $status) {
        $sql = "UPDATE tasks 
                SET title = ?, description = ?, category_id = ?, due_date = ?, priority = ?, status = ? 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssisssi", $title, $description, $categoryId, $dueDate, $priority, $status, $id);
        
        return $stmt->execute();
    }
    
    // Mengubah status tugas
    public function updateTaskStatus($id, $status) {
        $sql = "UPDATE tasks SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        
        return $stmt->execute();
    }
    
    // Menghapus tugas
    public function deleteTask($id) {
        $sql = "DELETE FROM tasks WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    // Mendapatkan tugas yang hampir jatuh tempo (dalam 3 hari)
    public function getUpcomingTasks($days = 3) {
        $sql = "SELECT t.*, c.name as category_name, u.username as user_username 
                FROM tasks t 
                LEFT JOIN categories c ON t.category_id = c.id 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE t.status != 'completed' 
                AND t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY) 
                ORDER BY t.due_date ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $tasks = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }
        }
        
        return $tasks;
    }
    
    // Mendapatkan tugas yang sudah melewati tenggat waktu
    public function getOverdueTasks() {
        $sql = "SELECT t.*, c.name as category_name, u.username as user_username 
                FROM tasks t 
                LEFT JOIN categories c ON t.category_id = c.id 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE t.status != 'completed' 
                AND t.due_date < CURDATE() 
                ORDER BY t.due_date ASC";
        
        $result = $this->conn->query($sql);
        $tasks = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }
        }
        
        return $tasks;
    }
    
    // Menutup koneksi database
    public function __destruct() {
        $this->conn->close();
    }
}
?>