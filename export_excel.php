<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get filter parameters from URL
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $status = $_GET['status'] ?? '';
    $payment_method = $_GET['payment_method'] ?? '';

    // Build query with filters (same as transaksi.php)
    $query = "SELECT * FROM transactions WHERE 1=1";
    $params = [];

    if (!empty($start_date)) {
        $query .= " AND DATE(created_at) >= ?";
        $params[] = $start_date;
    }

    if (!empty($end_date)) {
        $query .= " AND DATE(created_at) <= ?";
        $params[] = $end_date;
    }

    if (!empty($status) && $status !== 'all') {
        $query .= " AND status = ?";
        $params[] = $status;
    }

    if (!empty($payment_method) && $payment_method !== 'all') {
        $query .= " AND payment_method = ?";
        $params[] = $payment_method;
    }

    $query .= " ORDER BY id DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="transaksi_' . date('Y-m-d') . '.xls"');
    
    // Create Excel content
    echo "ID\tKode Transaksi\tCustomer\tSubtotal\tDiskon\tTotal\tBayar\tKembali\tMetode Bayar\tStatus\tCatatan\tTanggal\n";
    
    foreach ($transactions as $trx) {
        echo $trx['id'] . "\t";
        echo $trx['transaction_code'] . "\t";
        echo ($trx['customer_id'] ? 'Customer #' . $trx['customer_id'] : 'Walk-in') . "\t";
        echo number_format($trx['subtotal'], 0, ',', '.') . "\t";
        echo number_format($trx['discount'], 0, ',', '.') . "\t";
        echo number_format($trx['total'], 0, ',', '.') . "\t";
        echo number_format($trx['cash_amount'], 0, ',', '.') . "\t";
        echo number_format($trx['change_amount'], 0, ',', '.') . "\t";
        echo $trx['payment_method'] . "\t";
        echo $trx['status'] . "\t";
        echo $trx['notes'] . "\t";
        echo $trx['created_at'] . "\n";
    }
    
    exit;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>