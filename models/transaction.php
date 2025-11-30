<?php
class Transaction {
    private $conn;
    private $table = "transactions";
    private $table_items = "transaction_items";
    
    public function __construct($db) {
        $this->conn = $db;
    }

    // Insert transaksi utama
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
            (transaction_code, customer_id, user_id, subtotal, discount, total, payment_method, cash_amount, change_amount, status, notes) 
            VALUES (:transaction_code, :customer_id, :user_id, :subtotal, :discount, :total, :payment_method, :cash_amount, :change_amount, :status, :notes)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($data);

        return $this->conn->lastInsertId();
    }

    // Insert item transaksi
    public function addTransactionItem($data) {
        $query = "INSERT INTO " . $this->table_items . "
            (transaction_id, product_id, quantity, price, subtotal)
            VALUES (:transaction_id, :product_id, :quantity, :price, :subtotal)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    // Get daftar pelanggan untuk dropdown
    public function getCustomers() {
        $query = "SELECT * FROM customers ORDER BY name ASC";
        return $this->conn->query($query);
    }
}
