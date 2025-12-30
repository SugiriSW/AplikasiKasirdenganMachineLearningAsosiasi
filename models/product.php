<?php
class Product {
    private $conn;
    private $table = 'products';

    public $id;
    public $name;
    public $description;
    public $price;
    public $stock;
    public $category_id;
    public $image;
    public $barcode;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Method yang sudah ada
    public function read() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.is_active = 1 
                  ORDER BY p.name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readByCategory($category_id) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.category_id = :category_id AND p.is_active = 1 
                  ORDER BY p.name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        return $stmt;
    }

    public function getCategories() {
        $query = "SELECT * FROM categories WHERE parent_id IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getSubcategories($parent_id) {
        $query = "SELECT * FROM categories WHERE parent_id = :parent_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':parent_id', $parent_id);
        $stmt->execute();
        return $stmt;
    }

    // Method CRUD baru
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  SET name = :name, description = :description, price = :price, 
                      stock = :stock, category_id = :category_id, barcode = :barcode, 
                      image = :image";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':stock', $data['stock']);
        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':barcode', $data['barcode']);
        $stmt->bindParam(':image', $data['image']);
        
        return $stmt->execute();
    }

    public function update($data) {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name, description = :description, price = :price, 
                      stock = :stock, category_id = :category_id, barcode = :barcode";
        
        // Add image to query if provided
        if (!empty($data['image'])) {
            $query .= ", image = :image";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':stock', $data['stock']);
        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':barcode', $data['barcode']);
        $stmt->bindParam(':id', $data['id']);
        
        if (!empty($data['image'])) {
            $stmt->bindParam(':image', $data['image']);
        }
        
        return $stmt->execute();
    }

    public function delete($id) {
        // Soft delete - set is_active to 0
        $query = "UPDATE " . $this->table . " SET is_active = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }
    // Update stok produk
public function updateStock($productId, $qty) {
    $query = "UPDATE products 
              SET stock = CASE WHEN stock - :qty >= 0 THEN stock - :qty ELSE 0 END
              WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    return $stmt->execute([
        ':qty' => $qty,
        ':id' => $productId
    ]);
}

// Optional: cek stok saat ini
public function getStock($productId) {
    $query = "SELECT stock FROM products WHERE id = :id LIMIT 1";
    $stmt = $this->conn->prepare($query);
    $stmt->execute([':id' => $productId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['stock'] : null;
}
}

?>