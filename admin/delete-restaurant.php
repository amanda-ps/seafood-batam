<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $restaurants = get_restaurants();
    $restaurants = array_filter($restaurants, fn($r) => $r['id'] !== $id);
    write_json('restaurants.json', array_values($restaurants));
    $_SESSION['success'] = 'Restoran berhasil dihapus.';
}
redirect('/admin/restoran.php');
