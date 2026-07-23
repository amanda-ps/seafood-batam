<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    try {
        $pdo = get_db();
        if ($pdo) {
            $pdo->prepare("DELETE FROM favorit WHERE id_user = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM foto_ulasan WHERE id_ulasan IN (SELECT id_ulasan FROM ulasan WHERE id_user = ?)")->execute([$id]);
            $pdo->prepare("DELETE FROM ulasan WHERE id_user = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM user WHERE id_user = ? AND role != 'admin'")->execute([$id]);
        }
    } catch (Throwable $e) {}

    $users = get_users();
    $users = array_filter($users, fn($u) => (int)$u['id'] !== $id || $u['role'] === 'admin');
    try { write_json('users.json', array_values($users)); } catch(Throwable $e){}
    $_SESSION['success'] = 'Pengguna berhasil dihapus.';
}
redirect('pengguna.php');
