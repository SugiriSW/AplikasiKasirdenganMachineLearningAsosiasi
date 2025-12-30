<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'models/product.php';
require_once 'models/category.php';

redirectIfNotLoggedIn();
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);
$category = new Category($db);

// Handle actions
$action = $_GET['action'] ?? '';
$product_id = $_GET['id'] ?? '';

// Delete product
if ($action == 'delete' && $product_id) {
    if ($product->delete($product_id)) {
        $_SESSION['success_message'] = "Produk berhasil dihapus!";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus produk!";
    }
    header("Location: produk.php");
    exit();
}

// Get all products and categories
$products = $product->read();
$categories = $category->read();

// Handle form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $barcode = $_POST['barcode'] ?? '';

    $product_data = [
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'stock' => $stock,
        'category_id' => $category_id,
        'barcode' => $barcode
    ];

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $image_name = uploadImage($_FILES['image']);
        if ($image_name) {
            $product_data['image'] = $image_name;
        }
    }

    if (empty($id)) {
        // Add new product
        if ($product->create($product_data)) {
            $_SESSION['success_message'] = "Produk berhasil ditambahkan!";
        } else {
            $_SESSION['error_message'] = "Gagal menambahkan produk!";
        }
    } else {
        // Update existing product
        $product_data['id'] = $id;
        if ($product->update($product_data)) {
            $_SESSION['success_message'] = "Produk berhasil diperbarui!";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui produk!";
        }
    }

    header("Location: produk.php");
    exit();
}

// Function to handle image upload
function uploadImage($file) {
    $target_dir = "assets/images/products/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;

    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return false;
    }

    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return false;
    }

    // Allow certain file formats
    if (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
        return false;
    }

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $new_filename;
    }

    return false;
}

// Get product data for editing
$edit_product = null;
if ($action == 'edit' && $product_id) {
    $edit_product = $product->getById($product_id);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - GrosirMart</title>
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

        /* Products Container Responsive */
        .products-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
        }

        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .products-header h2 {
            color: var(--dark);
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .btn-add-product {
            background: var(--primary);
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

        .btn-add-product:hover {
            background: var(--primary-dark);
        }

        /* Table Styles */
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .products-table th,
        .products-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }

        .products-table th {
            font-weight: 600;
            color: var(--gray);
            font-size: 0.9rem;
            background: #f8f9fa;
        }

        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 5px;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray);
            transition: all 0.3s;
            padding: 6px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .btn-action:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .btn-edit {
            color: var(--info);
        }

        .btn-edit:hover {
            color: white;
            background-color: var(--info);
        }

        .btn-delete {
            color: var(--danger);
        }

        .btn-delete:hover {
            color: white;
            background-color: var(--danger);
        }

        /* Stock Badge Styles */
        .stock-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .stock-in {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
            border: 1px solid rgba(76, 201, 240, 0.3);
        }

        .stock-low {
            background-color: rgba(248, 149, 30, 0.1);
            color: var(--warning);
            border: 1px solid rgba(248, 149, 30, 0.3);
        }

        .stock-out {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            border: 1px solid rgba(247, 37, 133, 0.3);
        }

        /* Alert Styles */
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Empty State */
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

            .products-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .products-container {
                padding: 15px;
                overflow-x: auto;
            }

            .products-table {
                min-width: 900px;
                font-size: 0.85rem;
            }

            .products-table th,
            .products-table td {
                padding: 10px 12px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-action {
                width: 28px;
                height: 28px;
            }

            .product-image {
                width: 40px;
                height: 40px;
            }

            .search-box {
                width: 100%;
            }

            .search-box input {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .products-table {
                min-width: 800px;
            }

            .btn-add-product {
                width: 100%;
                justify-content: center;
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

            .search-box input {
                width: 100%;
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
        }

        /* Animation untuk better UX */
        .products-table tr {
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
                <a href="produk.php" class="menu-item active">
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
                    <h1>Manajemen Produk</h1>
                    <p>Kelola produk GrosirMart Anda</p>
                </div>
                <button class="btn-add-product" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    Tambah Produk
                </button>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="products-container">
                <div class="products-header">
                    <h2>Daftar Produk</h2>
                    <div class="mobile-actions">
                        <div class="search-box">
                            <input type="text" id="searchInput" placeholder="Cari produk...">
                            <button><i class="fas fa-search"></i></button>
                        </div>
                        <button class="btn-refresh" onclick="refreshPage()" title="Refresh">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>

                <?php if ($products->rowCount() == 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>Belum ada produk</h3>
                        <p>Mulai dengan menambahkan produk pertama Anda</p>
                        <button class="btn-add-product" onclick="openModal()" style="margin-top: 15px;">
                            <i class="fas fa-plus"></i>
                            Tambah Produk Pertama
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="products-table" id="productsTable">
                            <thead>
                                <tr>
                                    <th>Gambar</th>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $products->fetch(PDO::FETCH_ASSOC)): 
                                    $stock_class = $row['stock'] > 20 ? 'stock-in' : ($row['stock'] > 0 ? 'stock-low' : 'stock-out');
                                    $stock_text = $row['stock'] > 20 ? 'Tersedia' : ($row['stock'] > 0 ? 'Hampir Habis' : 'Stok Kosong');
                                ?>
                                <tr>
                                    <td>
                                        <div class="product-image">
                                            <img src="assets/images/products/<?php echo $row['image'] ?? 'default-product.png'; ?>" 
                                                 alt="<?php echo $row['name']; ?>" 
                                                 onerror="this.src='assets/images/default-product.png'">
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                        <?php if (!empty($row['barcode'])): ?>
                                            <br><small style="color: var(--gray);"><?php echo $row['barcode']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo $row['stock']; ?></td>
                                    <td>
                                        <span class="stock-badge <?php echo $stock_class; ?>">
                                            <?php echo $stock_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-edit" onclick="editProduct(<?php echo $row['id']; ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action btn-delete" onclick="deleteProduct(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal (tetap sama) -->
    <!-- Modal Add/Edit Product -->
    <div id="productModal" class="modal-produk">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">
                    <i class="fas fa-box"></i>
                    Tambah Produk Baru
                </h3>
                <button class="close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="productForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="productId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name" class="form-label">
                                <i class="fas fa-tag"></i>
                                Nama Produk *
                            </label>
                            <input type="text" id="name" name="name" class="form-input" required 
                                   placeholder="Masukkan nama produk">
                        </div>
                        <div class="form-group">
                            <label for="category_id" class="form-label">
                                <i class="fas fa-folder"></i>
                                Kategori *
                            </label>
                            <select id="category_id" name="category_id" class="form-select" required>
                                <option value="">Pilih Kategori</option>
                                <?php 
                                $categories->execute();
                                while ($cat = $categories->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price" class="form-label">
                                <i class="fas fa-money-bill-wave"></i>
                                Harga *
                            </label>
                            <div class="price-input-container">
                                <input type="number" id="price" name="price" class="form-input price-input" 
                                       min="0" step="100" required placeholder="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="stock" class="form-label">
                                <i class="fas fa-boxes"></i>
                                Stok *
                            </label>
                            <input type="number" id="stock" name="stock" class="form-input" 
                                   min="0" required placeholder="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="barcode" class="form-label">
                            <i class="fas fa-barcode"></i>
                            Barcode (Opsional)
                        </label>
                        <input type="text" id="barcode" name="barcode" class="form-input" 
                               placeholder="Kode barcode produk">
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">
                            <i class="fas fa-align-left"></i>
                            Deskripsi Produk
                        </label>
                        <textarea id="description" name="description" class="form-textarea" 
                                  rows="3" placeholder="Deskripsi singkat tentang produk"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-image"></i>
                            Gambar Produk
                        </label>
                        
                        <!-- Image Upload Area -->
                        <div class="image-upload-container" id="uploadArea">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="upload-text">
                                <h4>Upload Gambar Produk</h4>
                                <p>Drag & drop file atau klik untuk memilih</p>
                            </div>
                            <input type="file" id="image" name="image" accept="image/*" 
                                   style="display: none;" onchange="handleImageSelect(event)">
                            <button type="button" class="btn-upload" onclick="document.getElementById('image').click()">
                                <i class="fas fa-folder-open"></i>
                                Pilih File
                            </button>
                        </div>

                        <!-- Image Preview -->
                        <div id="imagePreviewContainer" class="image-preview-container" style="display: none;">
                            <div class="image-preview">
                                <img id="previewImg" src="" alt="Preview">
                                <div class="image-preview-actions">
                                    <button type="button" class="btn-image-action" onclick="removeImage()" title="Hapus gambar">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">
                            <i class="fas fa-times"></i>
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
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

        // Modal functions
        function openModal() {
            document.getElementById('productModal').style.display = 'block';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-box"></i> Tambah Produk Baru';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('imagePreviewContainer').style.display = 'none';
            document.getElementById('uploadArea').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        function editProduct(id) {
            window.location.href = 'produk.php?action=edit&id=' + id;
        }

        function deleteProduct(id, name) {
            if (confirm('Apakah Anda yakin ingin menghapus produk "' + name + '"?')) {
                window.location.href = 'produk.php?action=delete&id=' + id;
            }
        }

        function refreshPage() {
            window.location.reload();
        }

        // Image upload functions
        function handleImageSelect(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreviewContainer').style.display = 'grid';
                    document.getElementById('uploadArea').style.display = 'none';
                }
                
                reader.readAsDataURL(file);
            }
        }

        function removeImage() {
            document.getElementById('image').value = '';
            document.getElementById('imagePreviewContainer').style.display = 'none';
            document.getElementById('uploadArea').style.display = 'block';
        }

        // Drag and drop functionality
        const uploadArea = document.getElementById('uploadArea');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('image').files = files;
                handleImageSelect({ target: document.getElementById('image') });
            }
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#productsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // If in edit mode, open modal and populate data
        <?php if ($edit_product): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const product = <?php echo json_encode($edit_product); ?>;
            document.getElementById('productModal').style.display = 'block';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Produk';
            document.getElementById('productId').value = product.id;
            document.getElementById('name').value = product.name;
            document.getElementById('category_id').value = product.category_id;
            document.getElementById('price').value = product.price;
            document.getElementById('stock').value = product.stock;
            document.getElementById('barcode').value = product.barcode || '';
            document.getElementById('description').value = product.description || '';
            
            // Show image preview if exists
            if (product.image) {
                document.getElementById('previewImg').src = 'assets/images/products/' + product.image;
                document.getElementById('imagePreviewContainer').style.display = 'grid';
                document.getElementById('uploadArea').style.display = 'none';
            }
        });
        <?php endif; ?>

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);

        // Add loading state untuk actions
        document.querySelectorAll('.btn-action').forEach(btn => {
            btn.addEventListener('click', function() {
                this.classList.add('loading');
                setTimeout(() => {
                    this.classList.remove('loading');
                }, 1000);
            });
        });
    </script>

    <style>
        @media (max-width: 768px) {
            .mobile-actions {
                display: flex;
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