<?php
header('Content-Type: application/json');

try {
    // Load database
    require_once '../config/database.php';
    require_once '../models/product.php'; // <-- WAJIB
    $database = new Database();
    $db = $database->getConnection();

    // Ambil data JSON dari request
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !isset($data['transaction_data']) || !isset($data['items'])) {
        echo json_encode(['success' => false, 'message' => 'Data transaksi tidak valid!']);
        exit;
    }

    $trx = $data['transaction_data'];
    $items = $data['items'];

    $productModel = new Product($db); // <-- WAJIB

    // Mulai transaksi database
    $db->beginTransaction();

    // INSERT transaksi utama
    $stmt = $db->prepare("
        INSERT INTO transactions 
        (transaction_code, customer_id, user_id, subtotal, discount, total, payment_method, cash_amount, change_amount, status, created_at) 
        VALUES 
        (:transaction_code, NULL, NULL, :subtotal, :discount, :total, :payment_method, :cash_amount, :change_amount, 'completed', NOW())
    ");

    $stmt->execute([
        ':transaction_code' => $trx['transaction_code'],
        ':subtotal' => $trx['subtotal'],
        ':discount' => $trx['discount'],
        ':total' => $trx['total'],
        ':payment_method' => $trx['payment_method'],
        ':cash_amount' => $trx['cash_amount'],
        ':change_amount' => $trx['change_amount']
    ]);

    $transaction_id = $db->lastInsertId();

    // INSERT item transaksi + update stok
    $itemStmt = $db->prepare("
        INSERT INTO transaction_items 
        (transaction_id, product_id, price, quantity, subtotal) 
        VALUES 
        (:transaction_id, :product_id, :price, :quantity, :subtotal)
    ");

    foreach ($items as $item) {

        $currentStock = $productModel->getStock($item['id']);
        if ($currentStock === null) {
            throw new Exception("Produk ID {$item['id']} tidak ditemukan.");
        }
        if ($currentStock < $item['quantity']) {
            throw new Exception("Stok tidak cukup untuk produk '{$item['name']}'. Stok tersedia: {$currentStock}");
        }

        $itemStmt->execute([
            ':transaction_id' => $transaction_id,
            ':product_id' => $item['id'],
            ':price' => $item['price'],
            ':quantity' => $item['quantity'],
            ':subtotal' => ($item['price'] * $item['quantity'])
        ]);

        if (!$productModel->updateStock($item['id'], $item['quantity'])) {
            throw new Exception("Gagal update stok ID {$item['id']}");
        }
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transaksi berhasil disimpan!',
        'transaction_code' => $trx['transaction_code']
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
