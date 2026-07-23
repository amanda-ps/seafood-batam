<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    try {
        $pdo = get_db();
        if ($pdo) {
            $pdo->prepare("DELETE FROM foto_ulasan WHERE id_ulasan IN (SELECT id_ulasan FROM ulasan WHERE id_restoran = ?)")->execute([$id]);
            $pdo->prepare("DELETE FROM foto_restoran WHERE id_restoran = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM menu WHERE id_restoran = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM favorit WHERE id_restoran = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM ulasan WHERE id_restoran = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM restoran WHERE id_restoran = ?")->execute([$id]);
        }
    } catch (Throwable $e) {}

    $restaurants = get_restaurants();
    $restaurants = array_filter($restaurants, fn($r) => (int)$r['id'] !== $id);
    try { write_json('restaurants.json', array_values($restaurants)); } catch(Throwable $e){}
    $_SESSION['success'] = 'Restoran berhasil dihapus.';
}
redirect('restoran.php');
