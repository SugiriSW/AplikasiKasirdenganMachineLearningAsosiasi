<?php
require_once __DIR__ . '/../config/auth.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();
?>
<header>
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>

        <div class="search-box">
            <input type="text" placeholder="Search...">
            <button><i class="fas fa-search"></i></button>
        </div>
    </div>

    <div class="header-right">
        <button class="btn-notif"><i class="fas fa-bell"></i></button>

        <div class="user-profile">
            <span><?php echo htmlspecialchars($_SESSION['name']); ?> (Admin)</span>
        </div>

        <button class="btn-logout" onclick="window.location.href='logout.php'">
            <i class="fas fa-sign-out-alt"></i>
        </button>
    </div>
</header>

