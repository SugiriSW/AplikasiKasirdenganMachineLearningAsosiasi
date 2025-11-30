<?php
require_once 'config/auth.php';
require_once 'config/database.php'; // Tambahkan ini
require_once 'models/category.php';
redirectIfNotLoggedIn();

// Initialize database connection dan Category model
$database = new Database();
$pdo = $database->getConnection(); // Dapatkan koneksi PDO
$category = new Category($pdo); // Inisialisasi Category dengan koneksi

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        // Add new category
        $data = [
            'name' => $_POST['name'],
            'icon' => $_POST['icon'],
            'parent_id' => $_POST['parent_id'] ?: null
        ];
        
        if ($category->create($data)) {
            header("Location: kategori.php?success=Kategori berhasil ditambahkan");
            exit;
        } else {
            header("Location: kategori.php?error=Gagal menambahkan kategori");
            exit;
        }
    }
    elseif (isset($_POST['edit_category'])) {
        // Edit category
        $data = [
            'id' => $_POST['id'],
            'name' => $_POST['name'],
            'icon' => $_POST['icon'],
            'parent_id' => $_POST['parent_id'] ?: null
        ];
        
        if ($category->update($data)) {
            header("Location: kategori.php?success=Kategori berhasil diupdate");
            exit;
        } else {
            header("Location: kategori.php?error=Gagal mengupdate kategori");
            exit;
        }
    }
    elseif (isset($_POST['delete_category'])) {
        // Delete category
        $id = $_POST['id'];
        
        // Check if category has subcategories
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $checkStmt->execute([$id]);
        $hasChildren = $checkStmt->fetchColumn();
        
        if ($hasChildren > 0) {
            header("Location: kategori.php?error=Tidak dapat menghapus kategori yang memiliki subkategori");
            exit;
        }
        
        if ($category->delete($id)) {
            header("Location: kategori.php?success=Kategori berhasil dihapus");
            exit;
        } else {
            header("Location: kategori.php?error=Gagal menghapus kategori");
            exit;
        }
    }
}

// Get all categories with parent names
$stmt = $pdo->query("
    SELECT c.*, p.name as parent_name 
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    ORDER BY c.parent_id IS NULL DESC, c.parent_id, c.name
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get parent categories for dropdown
$parentStmt = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name");
$parentCategories = $parentStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - GrosirMart</title>
    <link rel="stylesheet" href="assets/css/adminstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
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
                <a href="produk.php" class="menu-item">
                    <i class="fas fa-box-open"></i>
                    <span>Produk</span>
                </a>
                <a href="kategori.php" class="menu-item active">
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
                    <h1>Kelola Kategori</h1>
                    <p>Kelola kategori dan subkategori produk</p>
                </div>
                <button class="btn-add-category" onclick="openAddModal()">
                    <i class="fas fa-plus"></i>
                    Tambah Kategori
                </button>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="categories-container">
                <div class="categories-header">
                    <h2>Daftar Kategori</h2>
                </div>

                <?php if (empty($categories)): ?>
                    <div style="text-align: center; padding: 40px; color: var(--gray);">
                        <i class="fas fa-folder-open" style="font-size: 3rem; margin-bottom: 15px;"></i>
                        <h3>Belum ada kategori</h3>
                        <p>Mulai dengan menambahkan kategori pertama Anda</p>
                    </div>
                <?php else: ?>
                    <table class="categories-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Icon</th>
                                <th>Nama Kategori</th>
                                <th>Parent</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr class="<?php echo $category['parent_id'] ? 'sub-category' : 'parent-category'; ?>">
                                    <td><?php echo $category['id']; ?></td>
                                    <td class="category-icon">
                                        <?php if ($category['icon']): ?>
                                            <i class="<?php echo $category['icon']; ?>"></i>
                                        <?php else: ?>
                                            <i class="fas fa-folder"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo $category['parent_name'] ? htmlspecialchars($category['parent_name']) : '-'; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($category['created_at'])); ?></td>
                                    <td class="category-actions">
                                        <button class="btn-action btn-edit" onclick="openEditModal(
                                            <?php echo $category['id']; ?>,
                                            '<?php echo htmlspecialchars($category['name']); ?>',
                                            '<?php echo htmlspecialchars($category['icon']); ?>',
                                            <?php echo $category['parent_id'] ?: 'null'; ?>
                                        )" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-action btn-delete" onclick="openDeleteModal(<?php echo $category['id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

<!-- Add/Edit Modal -->
<div id="categoryModal" class="modal-kategori">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Kategori</h3>
            <button class="close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="categoryForm" method="POST">
            <div class="modal-body">
                <input type="hidden" id="categoryId" name="id">
                
                <div class="form-group">
                    <label for="name" class="form-label">
                        <i class="fas fa-tag"></i>
                        Nama Kategori *
                    </label>
                    <input type="text" id="name" name="name" class="form-input" required maxlength="50" 
                           placeholder="Masukkan nama kategori">
                    <div class="form-hint">Maksimal 50 karakter</div>
                </div>

                <div class="form-group">
                    <label for="icon" class="form-label">
                        <i class="fas fa-icons"></i>
                        Icon Font Awesome
                    </label>
                    <div class="input-with-preview">
                        <input type="text" id="icon" name="icon" class="form-input" 
                               placeholder="fas fa-icon-name" maxlength="30"
                               oninput="updateIconPreview(this.value)">
                        <div class="icon-preview-container">
                            <div class="icon-preview" id="iconPreview">
                                <i class="fas fa-folder"></i>
                            </div>
                        </div>
                    </div>
                    <div class="form-hint">
                        Contoh: <code>fas fa-utensils</code>, <code>fas fa-coffee</code>, <code>fas fa-shopping-basket</code>
                    </div>
                </div>

                <div class="form-group">
                    <label for="parent_id" class="form-label">
                        <i class="fas fa-sitemap"></i>
                        Parent Kategori
                    </label>
                    <select id="parent_id" name="parent_id" class="form-select">
                        <option value="">-- Pilih Parent Kategori (Opsional) --</option>
                        <?php foreach ($parentCategories as $parent): ?>
                            <option value="<?php echo $parent['id']; ?>">
                                <?php echo htmlspecialchars($parent['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint">Kosongkan jika ini adalah kategori utama</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                    Batal
                </button>
                <button type="submit" class="btn btn-primary" name="add_category" id="submitButton">
                    <i class="fas fa-save"></i>
                    Simpan Kategori
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal-kategori">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3>
                <i class="fas fa-exclamation-triangle text-warning"></i>
                Hapus Kategori
            </h3>
            <button class="close" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="deleteForm" method="POST">
            <div class="modal-body">
                <input type="hidden" id="deleteId" name="id">
                <div class="delete-warning">
                    <div class="warning-icon">
                        <i class="fas fa-trash"></i>
                    </div>
                    <div class="warning-content">
                        <h4>Apakah Anda yakin?</h4>
                        <p>Kategori yang dihapus tidak dapat dikembalikan. Pastikan kategori tidak memiliki subkategori sebelum menghapus.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                    Batal
                </button>
                <button type="submit" class="btn btn-danger" name="delete_category">
                    <i class="fas fa-trash"></i>
                    Ya, Hapus
                </button>
            </div>
        </form>
    </div>
</div>

    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Kategori';
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryId').value = '';
            document.getElementById('submitButton').name = 'add_category';
            document.getElementById('iconPreview').className = 'fas fa-folder';
            document.getElementById('categoryModal').style.display = 'block';
        }

        function openEditModal(id, name, icon, parentId) {
            document.getElementById('modalTitle').textContent = 'Edit Kategori';
            document.getElementById('categoryId').value = id;
            document.getElementById('name').value = name;
            document.getElementById('icon').value = icon;
            document.getElementById('parent_id').value = parentId;
            document.getElementById('submitButton').name = 'edit_category';
            
            // Update icon preview
            updateIconPreview(icon);
            
            document.getElementById('categoryModal').style.display = 'block';
        }

        function openDeleteModal(id) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function updateIconPreview(iconValue) {
            const iconPreview = document.getElementById('iconPreview');
            if (iconValue) {
                iconPreview.className = iconValue;
            } else {
                iconPreview.className = 'fas fa-folder';
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

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