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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - GrosirMart</title>
    <link rel="stylesheet" href="assets/css/adminstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-layout">
    <div class="container admin-layout">
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
                <button class="btn-add-category" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    Tambah Produk
                </button>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div style="background-color: rgba(76, 201, 240, 0.1); color: var(--success-color); padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; border: 1px solid rgba(76, 201, 240, 0.3);">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div style="background-color: rgba(247, 37, 133, 0.1); color: var(--danger-color); padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; border: 1px solid rgba(247, 37, 133, 0.3);">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="categories-container">
                <div class="categories-header">
                    <h2>Daftar Produk</h2>
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Cari produk...">
                        <button><i class="fas fa-search"></i></button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="productsTable">
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
                                    <div style="width: 50px; height: 50px; border-radius: 5px; overflow: hidden;">
                                        <img src="assets/images/products/<?php echo $row['image'] ?? 'default-product.png'; ?>" 
                                             alt="<?php echo $row['name']; ?>" 
                                             style="width: 100%; height: 100%; object-fit: cover;"
                                             onerror="this.src='assets/images/default-product.png'">
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                    <?php if (!empty($row['barcode'])): ?>
                                        <br><small style="color: var(--gray-color);"><?php echo $row['barcode']; ?></small>
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
                                        <button class="btn-action btn-edit" onclick="editProduct(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <button class="btn-action btn-delete" onclick="deleteProduct(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

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
                            $categories_list = $category->read();
                            while ($cat = $categories_list->fetch(PDO::FETCH_ASSOC)): 
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
        // Redirect to edit mode
        window.location.href = 'produk.php?action=edit&id=' + id;
    }

    function deleteProduct(id, name) {
        if (confirm('Apakah Anda yakin ingin menghapus produk "' + name + '"?')) {
            window.location.href = 'produk.php?action=delete&id=' + id;
        }
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
</script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>