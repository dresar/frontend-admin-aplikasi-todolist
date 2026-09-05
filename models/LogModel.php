<?php
require_once __DIR__ . '/../config/database.php';

class LogModel {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Mendapatkan semua log aktivitas
    public function getAllLogs($limit = 100, $offset = 0, $filters = []) {
        $sql = "SELECT l.*, a.username as admin_username 
                FROM activity_logs l 
                LEFT JOIN admins a ON l.admin_id = a.id 
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Filter berdasarkan admin_id
        if (!empty($filters['admin_id'])) {
            $sql .= " AND l.admin_id = ?";
            $params[] = $filters['admin_id'];
            $types .= "i";
        }
        
        // Filter berdasarkan action
        if (!empty($filters['action'])) {
            $sql .= " AND l.action = ?";
            $params[] = $filters['action'];
            $types .= "s";
        }
        
        // Filter berdasarkan tanggal
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(l.created_at) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(l.created_at) <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }
        
        $sql .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        
        return $logs;
    }
    
    // Mendapatkan jumlah total log
    public function getTotalLogs($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM activity_logs l WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Filter berdasarkan admin_id
        if (!empty($filters['admin_id'])) {
            $sql .= " AND l.admin_id = ?";
            $params[] = $filters['admin_id'];
            $types .= "i";
        }
        
        // Filter berdasarkan action
        if (!empty($filters['action'])) {
            $sql .= " AND l.action = ?";
            $params[] = $filters['action'];
            $types .= "s";
        }
        
        // Filter berdasarkan tanggal
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(l.created_at) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(l.created_at) <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'];
    }
    
    // Menambahkan log aktivitas
    public function addLog($admin_id, $action, $details = '') {
        $sql = "INSERT INTO activity_logs (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $admin_id, $action, $details);
        
        return $stmt->execute();
    }
    
    // Menghapus log berdasarkan ID
    public function deleteLog($id) {
        $sql = "DELETE FROM activity_logs WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    // Menghapus log yang lebih lama dari X hari
    public function deleteOldLogs($days) {
        $sql = "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $days);
        
        return $stmt->execute();
    }
    
    // Menghapus semua log
    public function deleteAllLogs() {
        $sql = "DELETE FROM activity_logs";
        return $this->conn->query($sql);
    }
    
    // Mendapatkan semua jenis aksi yang ada di log
    public function getActionTypes() {
        $sql = "SELECT DISTINCT action FROM activity_logs ORDER BY action";
        $result = $this->conn->query($sql);
        
        $actions = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $actions[] = $row['action'];
            }
        }
        
        return $actions;
    }
}