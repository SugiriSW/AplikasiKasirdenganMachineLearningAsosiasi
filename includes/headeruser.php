<?php
require_once __DIR__ . '/../config/auth.php';
redirectIfNotLoggedIn();
?>
<header>
    <div class="logo">
        <i class="fas fa-store-alt"></i>
            <h1>GSG - <span>Access</span></h1>
    </div>

    <div class="search-box">
        <input type="text" placeholder="Cari produk...">
        <button><i class="fas fa-search"></i></button>
    </div>

    <div class="user-actions">
        <button class="btn-notif"><i class="fas fa-bell"></i></button>
        
        <button class="btn-cart" style="position: relative;">
            <i class="fas fa-shopping-cart"></i>
            <span class="cart-badge">0</span>
        </button>
        
        <div class="user-profile">
            <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
        </div>

        <button class="btn-logout" onclick="window.location.href='logout.php'">
            <i class="fas fa-sign-out-alt"></i>
        </button>
    </div>
</header>
