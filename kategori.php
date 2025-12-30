<?php
require_once 'config/auth.php';
require_once 'config/database.php';
require_once 'models/category.php';
redirectIfNotLoggedIn();

// Initialize database connection dan Category model
$database = new Database();
$pdo = $database->getConnection();
$category = new Category($pdo);

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

        /* Categories Container Responsive */
        .categories-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
        }

        .categories-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .categories-header h2 {
            color: var(--dark);
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .btn-add-category {
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

        .btn-add-category:hover {
            background: var(--primary-dark);
        }

        /* Table Styles */
        .categories-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .categories-table th,
        .categories-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }

        .categories-table th {
            font-weight: 600;
            color: var(--gray);
            font-size: 0.9rem;
            background: #f8f9fa;
        }

        .category-icon {
            font-size: 1.2rem;
            width: 30px;
            text-align: center;
            color: var(--primary);
        }

        .category-actions {
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

        /* Hierarchy Styles */
        .sub-category {
            padding-left: 30px;
            background: #f8f9fa;
        }

        .parent-category {
            font-weight: 600;
            background: #e9ecef;
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

            .categories-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .categories-container {
                padding: 15px;
                overflow-x: auto;
            }

            .categories-table {
                min-width: 800px;
                font-size: 0.85rem;
            }

            .categories-table th,
            .categories-table td {
                padding: 10px 12px;
            }

            .category-actions {
                flex-direction: column;
            }

            .btn-action {
                width: 28px;
                height: 28px;
            }

            .sub-category {
                padding-left: 20px;
            }
        }

        @media (max-width: 480px) {
            .categories-table {
                min-width: 700px;
            }

            .btn-add-category {
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
                width: 150px;
            }

            .user-profile span {
                display: none;
            }
        }

        /* Animation untuk better UX */
        .categories-table tr {
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
                    <div class="mobile-actions">
                        <button class="btn-refresh" onclick="refreshPage()" title="Refresh">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>

                <?php if (empty($categories)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>Belum ada kategori</h3>
                        <p>Mulai dengan menambahkan kategori pertama Anda</p>
                        <button class="btn-add-category" onclick="openAddModal()" style="margin-top: 15px;">
                            <i class="fas fa-plus"></i>
                            Tambah Kategori Pertama
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
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
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modals (tetap sama) -->
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

        function refreshPage() {
            window.location.reload();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal-kategori');
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