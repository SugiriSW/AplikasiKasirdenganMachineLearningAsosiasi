<?php
class Category {
    private $conn;
    private $table = 'categories';

    public $id;
    public $name;
    public $icon;
    public $parent_id;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  SET name = :name, icon = :icon, parent_id = :parent_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':icon', $data['icon']);
        $stmt->bindParam(':parent_id', $data['parent_id']);
        
        return $stmt->execute();
    }

    public function update($data) {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name, icon = :icon, parent_id = :parent_id
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':icon', $data['icon']);
        $stmt->bindParam(':parent_id', $data['parent_id']);
        $stmt->bindParam(':id', $data['id']);
        
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }
}
?>