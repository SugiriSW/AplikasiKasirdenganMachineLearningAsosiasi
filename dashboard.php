<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'models/product.php';

redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$products = $product->read();
$categories = $product->getCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grosir Kasir Modern</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/headeruser.php'; ?>

        <div class="main-content">
            <aside class="sidebar">
                <div class="sidebar-header">
                    <h3>Kategori Produk</h3>
                </div>
                <ul class="category-menu">
                    <?php while ($category = $categories->fetch(PDO::FETCH_ASSOC)): ?>
                    <li class="category-item has-submenu">
                        <div class="category-title">
                            <i class="<?php echo $category['icon']; ?>"></i>
                            <span><?php echo $category['name']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <ul class="submenu">
                            <?php 
                            $subcategories = $product->getSubcategories($category['id']);
                            while ($subcategory = $subcategories->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <li class="submenu-item" data-category="<?php echo $subcategory['id']; ?>">
                                <i class="<?php echo $subcategory['icon']; ?>"></i>
                                <span><?php echo $subcategory['name']; ?></span>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </aside>

            <section class="product-grid">
                <div class="grid-header">
                    <h2>Produk Tersedia</h2>
                    <div class="view-options">
                        <button class="btn-view active"><i class="fas fa-th-large"></i></button>
                        <button class="btn-view"><i class="fas fa-th-list"></i></button>
                    </div>
                </div>
                <div class="products-container">
                    <?php while ($row = $products->fetch(PDO::FETCH_ASSOC)): 
                        $stockClass = $row['stock'] > 20 ? 'in-stock' : ($row['stock'] > 0 ? 'low-stock' : 'out-of-stock');
                        $stockText = $row['stock'] > 20 ? 'Tersedia' : ($row['stock'] > 0 ? 'Hampir Habis' : 'Stok Kosong');
                    // Debug image path
        $image_path = "assets/images/products/" . $row['image'];
        $image_exists = file_exists($image_path);
                    ?>
                    <div class="product-card" data-category="<?php echo $row['category_id']; ?>">
                        <?php if ($row['stock'] < 5 && $row['stock'] > 0): ?>
                            <span class="product-badge">HOT</span>
                        <?php endif; ?>
                        
                        <div class="product-image">
                            <img src="<?php echo $image_exists ? $image_path : 'assets/images/default-product.png'; ?>" 
                                alt="<?php echo $row['name']; ?>">
                        </div>
                                <div class="product-info">
                        <h3 class="product-name"><?php echo $row['name']; ?></h3>
                        <p class="product-price">Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></p>
                        <p class="product-stock <?php echo $stockClass; ?>"><?php echo $stockText; ?> (<?php echo $row['stock']; ?>)</p>
                        <div class="product-actions">
                            <button class="btn-add" data-id="<?php echo $row['id']; ?>" 
                                    <?php echo $row['stock'] === 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-cart-plus"></i> Tambah
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            </section>

            <aside class="cart-sidebar">
                <div class="cart-header">
                    <h3>Keranjang Belanja</h3>
                    <button class="btn-clear"><i class="fas fa-trash"></i></button>
                </div>
                <div class="cart-items">
                    <p class="empty-cart">Keranjang belanja kosong</p>
                </div>
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span class="subtotal">Rp 0</span>
                    </div>
                    <div class="summary-row">
                        <span>Diskon</span>
                        <span class="discount">Rp 0</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span class="total-price">Rp 0</span>
                    </div>
                    <button class="btn-checkout">Proses Pembayaran <i class="fas fa-arrow-right"></i></button>
                </div>
            </aside>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>