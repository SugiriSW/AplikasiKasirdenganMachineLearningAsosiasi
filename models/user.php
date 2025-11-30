<?php
class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $name;
    public $email;
    public $phone;
    public $password;
    public $role;
    public $is_active;
    public $avatar;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($email, $password) {
        // Hapus kondisi is_active dari query sementara
        $query = "SELECT id, name, email, password, role FROM " . $this->table . " 
                  WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Untuk testing, gunakan password default 'password'
            // atau verifikasi password yang di-hash
            if ($password === 'password' || password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->name = $row['name'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                return true;
            }
        }
        return false;
    }

    public function register($name, $email, $phone, $password) {
        // Cek apakah email sudah terdaftar
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return false; // Email sudah terdaftar
        }

        // Insert user baru
        $query = "INSERT INTO " . $this->table . " 
                  (name, email, phone, password, role) 
                  VALUES (:name, :email, :phone, :password, 'kasir')";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':password', $hashed_password);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getAllUsers() {
        $query = "SELECT id, name, email, phone, role, created_at FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Method untuk menambahkan user admin default
    public function createDefaultAdmin() {
        // Cek apakah sudah ada admin
        $query = "SELECT id FROM " . $this->table . " WHERE role = 'admin' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            // Buat admin default
            $query = "INSERT INTO " . $this->table . " 
                      (name, email, password, role) 
                      VALUES (:name, :email, :password, 'admin')";
            
            $stmt = $this->conn->prepare($query);
            
            $name = "Administrator";
            $email = "admin@grosirmart.com";
            $password = password_hash("admin123", PASSWORD_DEFAULT);
            
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            
            return $stmt->execute();
        }
        return true;
    }
}
?>