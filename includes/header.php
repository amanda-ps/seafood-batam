<?php
require_once __DIR__ . '/functions.php';
$districts = ['Batam Kota', 'Lubuk Baja', 'Batu Ampar', 'Bengkong', 'Sekupang', 'Nongsa', 'Sagulung', 'Batu Aji', 'Belakang Padang', 'Bulang', 'Galang', 'Sei Beduk'];
$theme = $_COOKIE['theme'] ?? 'light';
$current_path = $_SERVER['PHP_SELF'] ?? '';
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Panduan Seafood Batam — Temukan restoran seafood terbaik di Kota Batam. Ulasan, rating, menu, dan reservasi langsung.">
    <title>Panduan Seafood Batam — Restoran Terbaik di Batam</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        window.BASE_URL = '<?= BASE_URL ?>';
    </script>
</head>
<body>
<div id="toast-container"></div>

<header class="navbar" id="navbar">
    <div class="container nav-content">
        <a href="<?= BASE_URL ?>/index.php" class="logo">
            <i class="fa-solid fa-fish-fins"></i>
            Seafood<span> Batam</span>
        </a>

        <nav class="nav-links" id="nav-links">
            <a href="<?= BASE_URL ?>/index.php" class="<?= basename($current_path) === 'index.php' ? 'active' : '' ?>">Beranda</a>
            <a href="<?= BASE_URL ?>/restaurants.php" class="<?= basename($current_path) === 'restaurants.php' ? 'active' : '' ?>">Restoran</a>
            <a href="<?= BASE_URL ?>/about.php" class="<?= basename($current_path) === 'about.php' ? 'active' : '' ?>">Tentang</a>
            <?php if (is_logged_in()): ?>
                <a href="<?= BASE_URL ?>/profile.php" class="<?= basename($current_path) === 'profile.php' ? 'active' : '' ?>">Profil Saya</a>
                <?php if (is_admin()): ?>
                    <a href="<?= BASE_URL ?>/admin/index.php" style="color:var(--clr-orange);">Panel Admin</a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-outline" style="padding:0.45rem 1rem; font-size:0.85rem;">
                    <i class="fa-solid fa-right-from-bracket"></i> Keluar
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline" style="padding:0.45rem 1rem; font-size:0.85rem;">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk
                </a>
                <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary" style="padding:0.45rem 1rem; font-size:0.85rem;">
                    Daftar
                </a>
            <?php endif; ?>
            <button id="theme-toggle" class="icon-btn" aria-label="Toggle Dark Mode" title="Ganti Tema">
                <i class="fa-solid fa-moon" id="theme-icon"></i>
            </button>
        </nav>

        <button class="mobile-menu-btn icon-btn" id="mobile-toggle" aria-label="Buka Menu">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <!-- Mobile Nav -->
    <nav class="mobile-nav" id="mobile-nav">
        <a href="<?= BASE_URL ?>/index.php">Beranda</a>
        <a href="<?= BASE_URL ?>/restaurants.php">Restoran</a>
        <a href="<?= BASE_URL ?>/about.php">Tentang</a>
        <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL ?>/profile.php">Profil Saya</a>
            <?php if (is_admin()): ?>
                <a href="<?= BASE_URL ?>/admin/index.php">Panel Admin</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/auth/logout.php">Keluar</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/auth/login.php">Masuk</a>
            <a href="<?= BASE_URL ?>/auth/register.php">Daftar</a>
        <?php endif; ?>
    </nav>
</header>

<main>
<?php
if (isset($_SESSION['error'])) {
    echo '<div style="display:none;" id="session-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    echo '<div style="display:none;" id="session-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}
?>
