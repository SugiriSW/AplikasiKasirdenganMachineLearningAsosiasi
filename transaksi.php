<?php
require_once 'config/auth.php';
require_once 'config/database.php';
redirectIfNotLoggedIn();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get filter parameters
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $status = $_GET['status'] ?? '';
    $payment_method = $_GET['payment_method'] ?? '';

    // Build query with filters
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

    // Get unique payment methods for filter dropdown
    $payment_methods_stmt = $db->query("SELECT DISTINCT payment_method FROM transactions WHERE payment_method IS NOT NULL");
    $payment_methods = $payment_methods_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get unique statuses for filter dropdown
    $statuses_stmt = $db->query("SELECT DISTINCT status FROM transactions WHERE status IS NOT NULL");
    $statuses = $statuses_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - GrosirMart</title>
    <link rel="stylesheet" href="assets/css/adminstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/headeradmin.php'; ?>
        
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-store-alt"></i>
                <h2>Grosir<span>Mart</span></h2>
            </div>
            <div class="sidebar-menu">
                <a href="dashboardadmin.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="produk.php" class="menu-item">
                    <i class="fas fa-box-open"></i>
                    <span>Produk</span>
                </a>
                <a href="kategori.php" class="menu-item">
                    <i class="fas fa-tags"></i>
                    <span>Kategori</span>
                </a>
                <a href="transaksi.php" class="menu-item active">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Transaksi</span>
                </a>
                <a href="logout.php" class="menu-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <main>
            <div class="page-header">
                <div class="page-title">
                    <h1>Daftar Transaksi</h1>
                    <p>Kelola dan pantau semua transaksi penjualan</p>
                </div>
                <button class="btn-filter" onclick="toggleFilter()">
                    <i class="fas fa-filter"></i>
                    Filter Data
                </button>
            </div>

            <!-- Filter Section -->
            <div class="filter-section" id="filterSection" style="display: none;">
                <div class="filter-header">
                    <h3><i class="fas fa-filter"></i> Filter Transaksi</h3>
                </div>
                
                <!-- Active Filters -->
                <?php if (!empty($_GET)): ?>
                <div class="active-filters">
                    <?php foreach ($_GET as $key => $value): ?>
                        <?php if (!empty($value) && $value !== 'all'): ?>
                            <div class="filter-badge">
                                <?= ucfirst(str_replace('_', ' ', $key)) ?>: <?= $value ?>
                                <a href="?" class="remove" title="Hapus filter">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label class="filter-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="filter-input" 
                               value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Tanggal Akhir</label>
                        <input type="date" name="end_date" class="filter-input" 
                               value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select name="status" class="filter-select">
                            <option value="all">Semua Status</option>
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Metode Bayar</label>
                        <select name="payment_method" class="filter-select">
                            <option value="all">Semua Metode</option>
                            <?php foreach ($payment_methods as $pm): ?>
                                <option value="<?= $pm ?>" <?= $payment_method === $pm ? 'selected' : '' ?>>
                                    <?= ucfirst($pm) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-apply">
                            <i class="fas fa-check"></i>
                            Terapkan Filter
                        </button>
                        <a href="?" class="btn-reset">
                            <i class="fas fa-times"></i>
                            Reset Filter
                        </a>
                    </div>
                </form>
            </div>

            <div class="transactions-container">
                <div class="transactions-header">
                    <h2>Riwayat Transaksi</h2>
                    <div class="header-actions">
                        <?php if (!empty($transactions)): ?>
                        <a href="export_excel.php?<?= http_build_query($_GET) ?>" class="btn-export">
                            <i class="fas fa-file-excel"></i>
                            Export ke Excel
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (empty($transactions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <h3><?= empty($_GET) ? 'Belum ada transaksi' : 'Tidak ada transaksi yang sesuai' ?></h3>
                        <p><?= empty($_GET) ? 'Transaksi yang dilakukan akan muncul di sini' : 'Coba ubah filter pencarian Anda' ?></p>
                        <?php if (!empty($_GET)): ?>
                            <a href="?" class="btn btn-primary" style="margin-top: 15px;">
                                <i class="fas fa-times"></i>
                                Reset Filter
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="transactions-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Kode Transaksi</th>
                                    <th>Customer</th>
                                    <th>Subtotal</th>
                                    <th>Diskon</th>
                                    <th>Total</th>
                                    <th>Bayar</th>
                                    <th>Kembali</th>
                                    <th>Metode Bayar</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $trx): ?>
                                <tr>
                                    <td><?= $trx['id'] ?></td>
                                    <td>
                                        <strong class="text-primary"><?= $trx['transaction_code'] ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($trx['customer_id']): ?>
                                            <span class="badge">Customer #<?= $trx['customer_id'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Walk-in</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="amount">Rp <?= number_format($trx['subtotal'], 0, ',', '.') ?></td>
                                    <td class="text-danger">Rp <?= number_format($trx['discount'], 0, ',', '.') ?></td>
                                    <td class="amount text-success">Rp <?= number_format($trx['total'], 0, ',', '.') ?></td>
                                    <td class="amount">Rp <?= number_format($trx['cash_amount'], 0, ',', '.') ?></td>
                                    <td class="amount text-info">Rp <?= number_format($trx['change_amount'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="payment-method">
                                            <?php 
                                            $payment_icons = [
                                                'cash' => 'fas fa-money-bill-wave',
                                                'debit' => 'fas fa-credit-card',
                                                'credit' => 'fas fa-credit-card',
                                                'qris' => 'fas fa-qrcode',
                                                'transfer' => 'fas fa-university'
                                            ];
                                            $icon = $payment_icons[strtolower($trx['payment_method'])] ?? 'fas fa-money-bill-wave';
                                            ?>
                                            <i class="<?= $icon ?>"></i>
                                            <?= ucfirst($trx['payment_method']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = [
                                            'completed' => 'status-completed',
                                            'pending' => 'status-pending',
                                            'cancelled' => 'status-cancelled'
                                        ];
                                        $status_icon = [
                                            'completed' => 'fas fa-check-circle',
                                            'pending' => 'fas fa-clock',
                                            'cancelled' => 'fas fa-times-circle'
                                        ];
                                        $class = $status_class[strtolower($trx['status'])] ?? 'status-pending';
                                        $icon = $status_icon[strtolower($trx['status'])] ?? 'fas fa-clock';
                                        ?>
                                        <span class="status-badge <?= $class ?>">
                                            <i class="<?= $icon ?>"></i>
                                            <?= ucfirst($trx['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($trx['notes']): ?>
                                            <span title="<?= htmlspecialchars($trx['notes']) ?>">
                                                <i class="fas fa-sticky-note text-muted"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Toggle filter section
        function toggleFilter() {
            const filterSection = document.getElementById('filterSection');
            filterSection.style.display = filterSection.style.display === 'none' ? 'block' : 'none';
        }

        // Auto show filter section if there are active filters
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.toString() !== '') {
                document.getElementById('filterSection').style.display = 'block';
            }
        });

        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.getElementById('sidebar');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768) {
                    if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                        sidebar.classList.remove('active');
                    }
                }
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>