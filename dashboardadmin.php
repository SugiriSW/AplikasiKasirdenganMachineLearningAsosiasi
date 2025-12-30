<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'models/User.php';
require_once 'models/Product.php';

redirectIfNotLoggedIn();
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$product = new Product($db);

$users = $user->getAllUsers();
$products = $product->read();
$totalProducts = $products->rowCount();

/* =========================
   FILTER PERIODE untuk produk terlaris
========================= */
$period = $_GET['period'] ?? 'day';

if ($period === 'day') {
    $dateCondition = "DATE(t.created_at) = CURDATE()";
    $periodLabel = 'Hari Ini';
} elseif ($period === 'week') {
    $dateCondition = "YEARWEEK(t.created_at, 1) = YEARWEEK(CURDATE(), 1)";
    $periodLabel = 'Minggu Ini';
} else {
    $dateCondition = "MONTH(t.created_at) = MONTH(CURDATE()) 
                      AND YEAR(t.created_at) = YEAR(CURDATE())";
    $periodLabel = 'Bulan Ini';
}

/* =========================
   PRODUK TERLARIS
========================= */
$sqlTopProducts = "
    SELECT p.name, SUM(ti.quantity) AS total_sold
    FROM transaction_items ti
    JOIN transactions t ON ti.transaction_id = t.id
    JOIN products p ON ti.product_id = p.id
    WHERE t.status = 'completed'
    AND $dateCondition
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
";

$stmtTopProducts = $db->query($sqlTopProducts);
$topProducts = $stmtTopProducts->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   STATISTIK PENJUALAN - Sesuaikan dengan kolom 'total'
========================= */
$salesStats = [];
$salesPeriod = $_GET['sales_period'] ?? 'month';

if ($salesPeriod === 'week') {
    // Data penjualan minggu ini
    $salesSql = "SELECT DAYNAME(created_at) as day, 
                        SUM(total) as total 
                 FROM transactions 
                 WHERE WEEK(created_at) = WEEK(CURDATE()) 
                 AND YEAR(created_at) = YEAR(CURDATE())
                 AND status = 'completed'
                 GROUP BY DAYOFWEEK(created_at) 
                 ORDER BY DAYOFWEEK(created_at)";
    $salesLabel = 'Minggu Ini';
} elseif ($salesPeriod === 'year') {
    // Data penjualan tahun ini
    $salesSql = "SELECT MONTHNAME(created_at) as month, 
                        SUM(total) as total 
                 FROM transactions 
                 WHERE YEAR(created_at) = YEAR(CURDATE())
                 AND status = 'completed'
                 GROUP BY MONTH(created_at) 
                 ORDER BY MONTH(created_at)";
    $salesLabel = 'Tahun Ini';
} else {
    // Data penjualan bulan ini (default)
    $salesSql = "SELECT DATE(created_at) as date, 
                        SUM(total) as total 
                 FROM transactions 
                 WHERE MONTH(created_at) = MONTH(CURDATE()) 
                 AND YEAR(created_at) = YEAR(CURDATE())
                 AND status = 'completed'
                 GROUP BY DATE(created_at) 
                 ORDER BY DATE(created_at)";
    $salesLabel = 'Bulan Ini';
}

$stmtSales = $db->query($salesSql);
$salesStats = $stmtSales->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   TOTAL PENDAPATAN DAN TRANSAKSI HARI INI - Gunakan kolom 'total'
========================= */
$todayRevenueSql = "SELECT SUM(total) as total FROM transactions 
                    WHERE DATE(created_at) = CURDATE() 
                    AND status = 'completed'";
$todayRevenue = $db->query($todayRevenueSql)->fetch(PDO::FETCH_ASSOC);

$todayTransactionsSql = "SELECT COUNT(*) as total FROM transactions 
                         WHERE DATE(created_at) = CURDATE() 
                         AND status = 'completed'";
$todayTransactions = $db->query($todayTransactionsSql)->fetch(PDO::FETCH_ASSOC);

/* =========================
   STATISTIK TAMBAHAN
========================= */
// Total pendapatan bulan ini
$monthRevenueSql = "SELECT SUM(total) as total FROM transactions 
                    WHERE MONTH(created_at) = MONTH(CURDATE()) 
                    AND YEAR(created_at) = YEAR(CURDATE())
                    AND status = 'completed'";
$monthRevenue = $db->query($monthRevenueSql)->fetch(PDO::FETCH_ASSOC);

// Total transaksi bulan ini
$monthTransactionsSql = "SELECT COUNT(*) as total FROM transactions 
                         WHERE MONTH(created_at) = MONTH(CURDATE()) 
                         AND YEAR(created_at) = YEAR(CURDATE())
                         AND status = 'completed'";
$monthTransactions = $db->query($monthTransactionsSql)->fetch(PDO::FETCH_ASSOC);

/* =========================
   HASIL ML (JSON)
========================= */
$mlFile = 'ml/hasil_asosiasi.json';
$mlRules = [];

if (file_exists($mlFile)) {
    $mlContent = file_get_contents($mlFile);
    $mlRules = json_decode($mlContent, true);
    
    // Jika decoding gagal atau file kosong
    if ($mlRules === null || empty($mlRules)) {
        $mlRules = [];
    }
}

if (isset($_SESSION['ml_result'])) {
    $mlResult = $_SESSION['ml_result'];
    // ... kode untuk menampilkan alert/notifikasi
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GrosirMart</title>
    <link rel="stylesheet" href="assets/css/adminstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Responsive improvements */
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

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }

            .charts-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .chart-card {
                padding: 15px;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-info h2 {
                font-size: 1.5rem;
            }

            .stat-icon {
                width: 45px;
                height: 45px;
                font-size: 1rem;
            }

            .ml-table {
                font-size: 0.8rem;
            }

            .ml-table th,
            .ml-table td {
                padding: 6px 4px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .page-title h1 {
                font-size: 1.5rem;
            }

            .page-title p {
                font-size: 0.8rem;
            }

            .stat-card {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            .stat-info, .stat-icon {
                justify-content: center;
            }

            .chart-container {
                height: 250px;
            }

            header {
                padding: 0 15px;
            }

            .search-box input {
                width: 150px;
            }

            .user-profile span {
                display: none;
            }

            .ml-rules-card {
                overflow-x: auto;
            }
        }

        /* Animation untuk stats cards */
        .stat-card {
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Hover effects untuk mobile */
        @media (hover: hover) {
            .stat-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            }
        }

        /* Touch improvements untuk mobile */
        .menu-item {
            -webkit-tap-highlight-color: transparent;
        }

        .btn, button {
            -webkit-tap-highlight-color: transparent;
        }

        .btn-generate {
            background: #6c5ce7;
            color: white;
            border: none;
            padding: 14px 22px;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-generate:hover {
            background: #5a4bdc;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 92, 231, 0.3);
        }

        .btn-generate i {
            font-size: 1.1rem;
        }

        /* Style untuk tabel */
        .ml-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 0.9rem;
        }

        .ml-table th {
            background-color: #f8f9fa;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }

        .ml-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e9ecef;
        }

        .ml-table tr:hover {
            background-color: #f8f9fa;
        }

        .confidence-cell {
            font-weight: bold;
            color: #28a745;
        }

        .no-data {
            text-align: center;
            color: #6c757d;
            padding: 30px;
            font-style: italic;
        }

        .chart-container canvas {
            max-height: 250px;
        }

        .chart-actions form {
            display: inline-block;
        }

        .chart-period {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 0.85rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .chart-period:hover {
            border-color: #6c5ce7;
        }

        .chart-period:focus {
            outline: none;
            border-color: #6c5ce7;
            box-shadow: 0 0 0 2px rgba(108, 92, 231, 0.25);
        }

        .ml-rules-card {
            margin-top: 20px;
        }

        .ml-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

         /* Stats card color variations */
        .stat-card:nth-child(1) {
            border-left: 4px solid #6c5ce7;
        }
        
        .stat-card:nth-child(2) {
            border-left: 4px solid #00b894;
        }
        
        .stat-card:nth-child(3) {
            border-left: 4px solid #fd79a8;
        }
        
        .stat-card:nth-child(4) {
            border-left: 4px solid #fdcb6e;
        }
        
        .stat-card:nth-child(5) {
            border-left: 4px solid #0984e3;
        }
        
        .stat-card:nth-child(6) {
            border-left: 4px solid #a29bfe;
        }
    </style>
</head>
<body>
    <!-- Overlay untuk mobile sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container">
        <?php 
        // Include header dengan parameter mobile
        $isMobile = true;
        include 'includes/headeradmin.php'; 
        ?>

        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-store-alt"></i>
                <h2>Grosir<span>Mart</span></h2>
                <button class="sidebar-close" id="sidebarClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="sidebar-menu">
                <a href="dashboardadmin.php" class="menu-item active">
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
                <a href="transaksi.php" class="menu-item">
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
                    <h1>Dashboard Admin</h1>
                    <p>Selamat datang kembali, <?php echo htmlspecialchars($_SESSION['name']); ?></p>
                </div>
                <div class="mobile-actions">
                    <button class="btn-refresh" id="refreshBtn" title="Refresh Data">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>

            <div style="margin: 25px 0;">
                <form action="generate_asosiasi.php" method="post" style="display: inline-block;">
                    <button type="submit" class="btn-generate">
                        <i class="fas fa-brain"></i>
                        Generate Analisis Asosiasi
                    </button>
                </form>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Produk</h3>
                        <h2><?php echo $totalProducts; ?></h2>
                        <small>Total produk tersedia</small>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total User</h3>
                        <h2><?php echo $users->rowCount(); ?></h2>
                        <small>User terdaftar</small>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Pendapatan Hari Ini</h3>
                        <h2>Rp <?php echo number_format($todayRevenue['total'] ?? 0, 0, ',', '.'); ?></h2>
                        <small><?php echo ($todayRevenue['total'] ?? 0) > 0 ? 'Penjualan hari ini' : 'Belum ada transaksi'; ?></small>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Transaksi Hari Ini</h3>
                        <h2><?php echo $todayTransactions['total'] ?? 0; ?></h2>
                        <small><?php echo ($todayTransactions['total'] ?? 0) > 0 ? 'transaksi berhasil' : 'Menunggu penjualan'; ?></small>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Pendapatan Bulan Ini</h3>
                        <h2>Rp <?php echo number_format($monthRevenue['total'] ?? 0, 0, ',', '.'); ?></h2>
                        <small>Total bulan <?php echo date('F'); ?></small>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Transaksi Bulan Ini</h3>
                        <h2><?php echo $monthTransactions['total'] ?? 0; ?></h2>
                        <small>Transaksi bulan <?php echo date('F'); ?></small>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>

            <div class="charts-grid">
                <!-- Chart Statistik Penjualan -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Statistik Penjualan (<?php echo $salesLabel; ?>)</h3>
                        <div class="chart-actions">
                            <form method="get" style="display: inline-block;">
                                <select name="sales_period" class="chart-period" onchange="this.form.submit()">
                                    <option value="week" <?= $salesPeriod=='week'?'selected':'' ?>>Minggu Ini</option>
                                    <option value="month" <?= $salesPeriod=='month'?'selected':'' ?>>Bulan Ini</option>
                                    <option value="year" <?= $salesPeriod=='year'?'selected':'' ?>>Tahun Ini</option>
                                </select>
                            </form>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
                
                <!-- Produk Terlaris -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Produk Terlaris (<?php echo $periodLabel; ?>)</h3>
                        <div class="chart-actions">
                            <form method="get" style="display: inline-block;">
                                <select name="period" class="chart-period" onchange="this.form.submit()">
                                    <option value="day" <?= $period=='day'?'selected':'' ?>>Hari Ini</option>
                                    <option value="week" <?= $period=='week'?'selected':'' ?>>Minggu Ini</option>
                                    <option value="month" <?= $period=='month'?'selected':'' ?>>Bulan Ini</option>
                                </select>
                            </form>
                        </div>
                    </div>
                    
                    <?php if (empty($topProducts)): ?>
                        <div class="no-data">
                            <i class="fas fa-shopping-cart fa-2x" style="margin-bottom: 10px;"></i>
                            <p>Belum ada data penjualan untuk periode ini</p>
                            <small>Transaksi akan muncul setelah penjualan</small>
                        </div>
                    <?php else: ?>
                        <table class="ml-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Produk</th>
                                    <th>Total Terjual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 1; ?>
                                <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><strong><?php echo $product['total_sold']; ?></strong> unit</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div style="margin-top: 10px; font-size: 0.8rem; color: #666;">
                            <i class="fas fa-info-circle"></i> Berdasarkan jumlah penjualan periode <?php echo strtolower($periodLabel); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hasil Analisis Asosiasi ML -->
            <div class="chart-card ml-rules-card">
                <div class="ml-header">
                    <div>
                        <h3>Hasil Analisis Asosiasi Produk</h3>
                        <small>Rekomendasi produk yang sering dibeli bersama</small>
                    </div>
                    <?php if (!empty($mlRules)): ?>
                    <div style="font-size: 0.8rem; color: #28a745;">
                        <i class="fas fa-check-circle"></i> 
                        <?php echo count($mlRules); ?> pola ditemukan
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($mlRules)): ?>
                    <div class="no-data">
                        <i class="fas fa-robot fa-2x" style="margin-bottom: 10px;"></i>
                        <p>Analisis asosiasi belum dijalankan</p>
                        <small>Klik tombol "Generate Analisis Asosiasi" untuk memulai</small>
                    </div>
                <?php else: ?>
                    <table class="ml-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Jika Membeli</th>
                                <th>Maka Membeli</th>
                                <th>Confidence</th>
                                <th>Support</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; ?>
                            <?php foreach ($mlRules as $rule): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($rule['if_buy'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($rule['then_buy'] ?? 'N/A'); ?></td>
                                <td class="confidence-cell">
                                    <?php 
                                    if (isset($rule['confidence'])) {
                                        echo round($rule['confidence'] * 100, 2) . '%';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if (isset($rule['support'])) {
                                        echo round($rule['support'] * 100, 2) . '%';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 15px; font-size: 0.85rem; color: #6c757d; background: #f8f9fa; padding: 10px; border-radius: 5px;">
                        <p><i class="fas fa-lightbulb"></i> 
                        <strong>Tips:</strong> Gunakan hasil analisis ini untuk strategi penempatan produk dan bundling penjualan.</p>
                        <p style="margin-top: 5px;">
                            <strong>Confidence</strong>: Probabilitas jika membeli A maka membeli B |
                            <strong>Support</strong>: Frekuensi kemunculan pola dalam seluruh transaksi
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions untuk Mobile -->
            <div class="quick-actions-mobile">
                <h3>Aksi Cepat</h3>
                <div class="action-buttons">
                    <a href="produk.php" class="action-btn">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Produk</span>
                    </a>
                    <a href="transaksi.php" class="action-btn">
                        <i class="fas fa-receipt"></i>
                        <span>Lihat Transaksi</span>
                    </a>
                    <a href="kategori.php" class="action-btn">
                        <i class="fas fa-tags"></i>
                        <span>Kelola Kategori</span>
                    </a>
                    <a href="generate_asosiasi.php" class="action-btn">
                        <i class="fas fa-brain"></i>
                        <span>Analisis ML</span>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <style>
        /* Quick Actions untuk Mobile */
        .quick-actions-mobile {
            display: none;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
        }

        .quick-actions-mobile h3 {
            margin: 0 0 15px 0;
            color: var(--dark);
            font-size: 1.1rem;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px 10px;
            background: var(--light);
            border-radius: 8px;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.3s ease;
            text-align: center;
        }

        .action-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .action-btn i {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .action-btn span {
            font-size: 0.8rem;
            font-weight: 500;
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

        .mobile-actions {
            display: none;
            gap: 10px;
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

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 15px;
        }

        @media (max-width: 768px) {
            .quick-actions-mobile {
                display: block;
            }

            .sidebar-close {
                display: block;
            }

            .mobile-actions {
                display: flex;
            }

            .chart-actions {
                flex-direction: column;
            }

            .chart-period {
                width: 100%;
            }

            .chart-container {
                height: 250px;
            }
        }
    </style>

    <script>
        // Mobile Sidebar Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const sidebarClose = document.getElementById('sidebarClose');
            const refreshBtn = document.getElementById('refreshBtn');

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

            // Refresh button functionality
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function() {
                    this.classList.add('rotating');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                });
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
            
            // Inisialisasi chart penjualan
            initializeSalesChart();
        });

        // Fungsi untuk refresh data ML
        function refreshMLData() {
            const refreshBtn = document.querySelector('.refresh-ml-btn');
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                refreshBtn.disabled = true;
                
                // Reload halaman setelah 2 detik
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        }

        // Inisialisasi Chart.js untuk statistik penjualan
        function initializeSalesChart() {
            const ctx = document.getElementById('salesChart');
            if (!ctx) return;
            
            const salesPeriod = "<?php echo $salesPeriod; ?>";
            const salesData = <?php echo json_encode($salesStats); ?>;
            
            let labels = [];
            let data = [];
            
            if (salesPeriod === 'week') {
                // Data untuk minggu
                const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                const weekData = Array(7).fill(0);
                
                salesData.forEach(item => {
                    const dayIndex = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
                        .indexOf(item.day);
                    if (dayIndex !== -1) {
                        weekData[dayIndex] = parseFloat(item.total) || 0;
                    }
                });
                
                labels = days;
                data = weekData;
                
            } else if (salesPeriod === 'year') {
                // Data untuk tahun
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 
                               'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                const yearData = Array(12).fill(0);
                
                salesData.forEach(item => {
                    const monthIndex = ['January', 'February', 'March', 'April', 'May', 'June',
                                       'July', 'August', 'September', 'October', 'November', 'December']
                        .indexOf(item.month);
                    if (monthIndex !== -1) {
                        yearData[monthIndex] = parseFloat(item.total) || 0;
                    }
                });
                
                labels = months;
                data = yearData;
                
            } else {
                // Data untuk bulan (default)
                if (salesData.length > 0) {
                    salesData.forEach(item => {
                        const date = new Date(item.date);
                        labels.push(date.getDate() + ' ' + ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 
                                                           'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][date.getMonth()]);
                        data.push(parseFloat(item.total) || 0);
                    });
                } else {
                    // Data dummy jika tidak ada data
                    labels = ['1', '5', '10', '15', '20', '25', '30'];
                    data = [0, 0, 0, 0, 0, 0, 0];
                }
            }
            
            // Buat chart
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Penjualan (Rp)',
                        data: data,
                        borderColor: '#6c5ce7',
                        backgroundColor: 'rgba(108, 92, 231, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    }
                }
            });
        }

        // Add rotation animation for refresh button
        const style = document.createElement('style');
        style.textContent = `
            @keyframes rotate {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            .rotating {
                animation: rotate 1s linear infinite;
            }
            
            .fa-spin {
                animation: fa-spin 2s linear infinite;
            }
            
            @keyframes fa-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
        // Cek jika ada pesan hasil ML
<?php if (isset($_SESSION['ml_result'])): ?>
window.onload = function() {
    const result = <?php echo json_encode($_SESSION['ml_result']); ?>;
    
    if (result.success) {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            html: `<b>${result.message}</b><br><br>
                   <b>📊 ${result.count} pola asosiasi ditemukan</b><br>
                   <b>💳 Dari ${result.transactions_count} transaksi</b><br><br>
                   <small>Hasil akan ditampilkan di bawah</small>`,
            confirmButtonText: 'OK',
            timer: 5000
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            html: `<b>${result.message}</b><br><br>
                   ${result.count ? `<b>Transaksi saat ini: ${result.count} (minimal 10)</b><br>` : ''}
                   <small>Silakan tambahkan transaksi terlebih dahulu</small>`,
            confirmButtonText: 'OK'
        });
    }
    
    // Hapus session message
    <?php unset($_SESSION['ml_result']); ?>
};
<?php endif; ?>
    </script>
</body>
</html>