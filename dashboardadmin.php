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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GrosirMart</title>
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
                    <p>Selamat datang kembali, <?php echo $_SESSION['name']; ?></p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Produk</h3>
                        <h2><?php echo $totalProducts; ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total User</h3>
                        <h2><?php echo $users->rowCount(); ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Pendapatan Hari Ini</h3>
                        <h2>Rp 0</h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Transaksi Hari Ini</h3>
                        <h2>0</h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Statistik Penjualan</h3>
                    </div>
                    <div class="chart-container">
                        <p style="text-align: center; color: var(--gray-color); padding: 50px;">
                            Grafik Penjualan akan ditampilkan di sini
                        </p>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Produk Terpopuler</h3>
                    </div>
                    <div class="chart-container">
                        <p style="text-align: center; color: var(--gray-color); padding: 50px;">
                            Grafik Produk Terpopuler akan ditampilkan di sini
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    

    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>