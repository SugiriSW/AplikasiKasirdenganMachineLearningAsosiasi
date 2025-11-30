<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../models/transaction.php';
require_once '../../models/product.php';

// Ambil data JSON dari fetch()
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Data tidak valid"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$transaction = new Transaction($db);
$product = new Product($db);

try {
    // Mulai transaksi database (biar aman jika error)
    $db->beginTransaction();

    // 1. Simpan data transaksi utama
    $transaction_id = $transaction->create([
        ':transaction_code' => $data['transaction_code'],
        ':customer_id' => $data['customer_id'],
        ':user_id' => $_SESSION['user_id'],
        ':subtotal' => $data['subtotal'],
        ':discount' => $data['discount'],
        ':total' => $data['total'],
        ':payment_method' => $data['payment_method'],
        ':cash_amount' => $data['cash_amount'],
        ':change_amount' => $data['change_amount'],
        ':status' => "completed",
        ':notes' => $data['notes']
    ]);

    // 2. Simpan item transaksi
    foreach ($data['items'] as $item) {
        $transaction->addTransactionItem([
            ':transaction_id' => $transaction_id,
            ':product_id' => $item['id'],
            ':quantity' => $item['quantity'],
            ':price' => $item['price'],
            ':subtotal' => $item['quantity'] * $item['price']
        ]);

        // 3. Update stok
        $product->updateStock($item['id'], $item['quantity']);
    }

    // Commit ke database
    $db->commit();

    // Kosongkan cart setelah sukses
    unset($_SESSION['cart']);

    echo json_encode([
        "success" => true,
        "message" => "Transaksi berhasil disimpan",
        "transaction_id" => $transaction_id
    ]);
    exit;

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
    exit;
}
