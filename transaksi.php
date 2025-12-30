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
    <style>
        /* Mobile Overlay untuk sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
        }

        .sidebar-overlay.active {
            display: block;
        }

        .sidebar-close {
            display: none;
            background: none;
            border: none;
            color: var(--gray);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
        }

        /* Transactions Container Responsive */
        .transactions-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
        }

        .transactions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .transactions-header h2 {
            color: var(--dark);
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-export {
            background: var(--success);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
        }

        .btn-export:hover {
            background: #3aa8c9;
            transform: translateY(-1px);
        }

        .btn-filter {
            background: var(--info);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .btn-filter:hover {
            background: #3a7bd5;
            transform: translateY(-1px);
        }

        /* Filter Styles */
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid var(--light-gray);
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .filter-header h3 {
            margin: 0;
            color: var(--dark);
            font-size: 1.1rem;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--dark);
            font-size: 0.9rem;
        }

        .filter-input, .filter-select {
            padding: 10px 12px;
            border: 1px solid var(--light-gray);
            border-radius: 5px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .filter-input:focus, .filter-select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-apply {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-apply:hover {
            background: var(--primary-dark);
        }

        .btn-reset {
            background: var(--gray);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-reset:hover {
            background: #5a6268;
        }

        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .filter-badge {
            background: var(--primary);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .filter-badge .remove {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 0;
            margin-left: 5px;
        }

        /* Table Styles */
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .transactions-table th,
        .transactions-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }

        .transactions-table th {
            font-weight: 600;
            color: var(--gray);
            font-size: 0.9rem;
            background: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-completed {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
            border: 1px solid rgba(76, 201, 240, 0.3);
        }

        .status-pending {
            background-color: rgba(248, 149, 30, 0.1);
            color: var(--warning);
            border: 1px solid rgba(248, 149, 30, 0.3);
        }

        .status-cancelled {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            border: 1px solid rgba(247, 37, 133, 0.3);
        }

        .payment-method {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .amount {
            font-weight: 600;
            color: var(--dark);
        }

        .text-success {
            color: var(--success) !important;
        }

        .text-danger {
            color: var(--danger) !important;
        }

        .text-warning {
            color: var(--warning) !important;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--light-gray);
        }

        .empty-state h3 {
            margin-bottom: 10px;
            font-weight: 500;
        }

        /* Mobile Actions */
        .mobile-actions {
            display: none;
            gap: 10px;
            width: 100%;
            justify-content: space-between;
        }

        .btn-refresh {
            background: var(--primary);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-refresh:hover {
            background: var(--primary-dark);
            transform: rotate(180deg);
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                grid-template-rows: 70px 1fr;
                grid-template-areas: 
                    "header"
                    "main";
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                height: 100vh;
                z-index: 100;
                transition: all 0.3s ease;
                box-shadow: 2px 0 15px rgba(0, 0, 0, 0.2);
            }

            .sidebar.active {
                left: 0;
            }

            .sidebar-close {
                display: block;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .transactions-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .transactions-container {
                padding: 15px;
                overflow-x: auto;
            }

            .transactions-table {
                min-width: 1200px;
                font-size: 0.85rem;
            }

            .transactions-table th,
            .transactions-table td {
                padding: 10px 12px;
            }

            .header-actions {
                flex-direction: column;
                width: 100%;
                gap: 10px;
            }

            .btn-export, .btn-filter {
                width: 100%;
                justify-content: center;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                flex-direction: column;
            }

            .btn-apply, .btn-reset {
                width: 100%;
                justify-content: center;
            }

            .mobile-actions {
                display: flex;
            }
        }

        @media (max-width: 480px) {
            .transactions-table {
                min-width: 1100px;
            }

            .empty-state {
                padding: 40px 15px;
            }

            .empty-state i {
                font-size: 3rem;
            }

            header {
                padding: 0 15px;
            }

            .user-profile span {
                display: none;
            }

            .page-title h1 {
                font-size: 1.5rem;
            }

            .page-title p {
                font-size: 0.8rem;
            }

            .filter-section {
                padding: 15px;
            }

            .active-filters {
                gap: 8px;
            }

            .filter-badge {
                font-size: 0.75rem;
                padding: 4px 10px;
            }
        }

        /* Animation untuk better UX */
        .transactions-table tr {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Touch improvements */
        .menu-item, .btn, button {
            -webkit-tap-highlight-color: transparent;
        }

        /* Loading state untuk actions */
        .btn-action.loading {
            pointer-events: none;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <!-- Overlay untuk mobile sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container">
        <?php include 'includes/headeradmin.php'; ?>
        
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-store-alt"></i>
                <h2>Grosir<span>Mart</span></h2>
                <button class="sidebar-close" id="sidebarClose">
                    <i class="fas fa-times"></i>
                </button>
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
                <div class="mobile-actions">
                    <button class="btn-filter" onclick="toggleFilter()">
                        <i class="fas fa-filter"></i>
                        Filter Data
                    </button>
                    <button class="btn-refresh" onclick="refreshPage()" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section" id="filterSection" style="display: none;">
                <div class="filter-header">
                    <h3><i class="fas fa-filter"></i> Filter Transaksi</h3>
                    <button class="filter-close" onclick="toggleFilter()">
                        <i class="fas fa-times"></i>
                    </button>
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
                        <button class="btn-filter" onclick="toggleFilter()">
                            <i class="fas fa-filter"></i>
                            Filter Data
                        </button>
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
        // Mobile Sidebar Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const sidebarClose = document.getElementById('sidebarClose');

            // Toggle sidebar
            function toggleSidebar() {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            }

            // Event listeners
            if (menuToggle) {
                menuToggle.addEventListener('click', toggleSidebar);
            }

            if (sidebarClose) {
                sidebarClose.addEventListener('click', toggleSidebar);
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', toggleSidebar);
            }

            // Close sidebar when clicking on menu items (mobile)
            const menuItems = document.querySelectorAll('.sidebar-menu .menu-item');
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        toggleSidebar();
                    }
                });
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        // Toggle filter section
        function toggleFilter() {
            const filterSection = document.getElementById('filterSection');
            filterSection.style.display = filterSection.style.display === 'none' ? 'block' : 'none';
        }

        function refreshPage() {
            window.location.reload();
        }

        // Auto show filter section if there are active filters
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.toString() !== '') {
                document.getElementById('filterSection').style.display = 'block';
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>

    <style>
        .filter-close {
            display: none;
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            padding: 5px;
        }

        @media (max-width: 768px) {
            .filter-close {
                display: block;
            }
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .rotating {
            animation: rotate 1s linear infinite;
        }
    </style>
</body>
</html>