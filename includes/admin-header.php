<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_admin();

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_titles = [
    'index'             => 'Dashboard',
    'dashboard'         => 'Dashboard',
    'restoran'          => 'Manajemen Restoran',
    'add-restaurant'    => 'Tambah Restoran',
    'edit-restaurant'   => 'Edit Restoran',
    'ulasan'            => 'Manajemen Ulasan',
    'pengguna'          => 'Manajemen Pengguna',
];
$page_title = $page_titles[$current_page] ?? 'Admin Panel';

?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — Seafood Batam Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="admin-body">

<div id="adm-toast-container"></div>

<!-- Confirmation Modal -->
<div class="adm-overlay" id="adm-confirm-overlay">
    <div class="adm-modal">
        <div class="adm-modal-icon adm-modal-icon-red" id="adm-modal-icon">
            <i class="fa-solid fa-triangle-exclamation" id="adm-modal-icon-i"></i>
        </div>
        <div class="adm-modal-title" id="adm-modal-title">Konfirmasi Hapus</div>
        <div class="adm-modal-msg" id="adm-modal-msg">Apakah Anda yakin ingin menghapus item ini? Tindakan ini tidak dapat dibatalkan.</div>
        <div class="adm-modal-actions">
            <button class="adm-btn adm-btn-outline-red adm-btn-lg" id="adm-modal-confirm">
                <i class="fa-solid fa-trash"></i> Hapus
            </button>
            <button class="adm-btn adm-btn-ghost adm-btn-lg" onclick="closeAdmModal()">Batal</button>
        </div>
    </div>
</div>

<div class="adm-layout">

    <!-- SIDEBAR -->
    <aside class="adm-sidebar" id="adm-sidebar">
        <!-- Brand -->
        <div class="adm-sidebar-brand">
            <div class="adm-brand-logo">
                <div class="adm-brand-icon"><i class="fa-solid fa-fish-fins"></i></div>
                <span class="adm-brand-name">Seafood Batam</span>
            </div>
            <span class="adm-brand-sub">Admin Panel</span>
        </div>

        <!-- Nav -->
        <nav class="adm-nav">
            <div class="adm-nav-label">Menu Utama</div>
            <a href="<?= BASE_URL ?>/admin/index.php" class="adm-nav-item <?= in_array($current_page, ['index','dashboard']) ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/admin/restoran.php" class="adm-nav-item <?= in_array($current_page, ['restoran','add-restaurant','edit-restaurant']) ? 'active' : '' ?>">
                <i class="fa-solid fa-store"></i> Restoran
            </a>
            <a href="<?= BASE_URL ?>/admin/ulasan.php" class="adm-nav-item <?= $current_page === 'ulasan' ? 'active' : '' ?>">
                <i class="fa-solid fa-comments"></i> Ulasan
            </a>
            <a href="<?= BASE_URL ?>/admin/pengguna.php" class="adm-nav-item <?= $current_page === 'pengguna' ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> Pengguna
            </a>
        </nav>

        <!-- Footer -->
        <div class="adm-sidebar-footer">
            <div class="adm-divider" style="margin-bottom:10px;"></div>
            <a href="<?= BASE_URL ?>/index.php" class="adm-nav-item" style="margin-bottom:6px; font-size:0.8rem;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Situs
            </a>

            <div class="adm-divider" style="margin: 8px 0;"></div>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="adm-nav-item danger" style="margin-top:2px;">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="adm-main">
        <!-- Topbar -->
        <header class="adm-topbar">
            <div class="adm-topbar-title"><?= htmlspecialchars($page_title) ?></div>
            <div class="adm-topbar-right">
                <div class="adm-user-info">
                    <div class="adm-user-avatar">AU</div>
                    <div>
                        <div class="adm-user-name">Administrator</div>
                        <div class="adm-user-role">Super Admin</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page content goes here -->
        <main class="adm-content">
<?php
// Show session flash messages
if (isset($_SESSION['success'])) {
    echo '<div class="adm-alert adm-alert-success"><i class="fa-solid fa-check-circle"></i>' . htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="adm-alert adm-alert-error"><i class="fa-solid fa-circle-exclamation"></i>' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}
