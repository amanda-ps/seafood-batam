<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Silakan masuk terlebih dahulu untuk menghapus ulasan.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
    exit;
}

$current_user_id = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0;
$id_ulasan = intval($_POST['id_ulasan'] ?? 0);
$restaurant_id = intval($_POST['restaurant_id'] ?? 0);

if ($id_ulasan <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID ulasan tidak valid.']);
    exit;
}

try {
    $pdo = get_db();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT id_user, id_restoran FROM ulasan WHERE id_ulasan = ?");
        $stmt->execute([$id_ulasan]);
        $rev = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rev) {
            echo json_encode(['status' => 'error', 'message' => 'Ulasan tidak ditemukan di database.']);
            exit;
        }

        // Cek kepemilikan ulasan (atau admin)
        if ($rev['id_user'] != $current_user_id && !is_admin()) {
            echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus ulasan ini.']);
            exit;
        }

        $id_resto = $rev['id_restoran'];

        // Hapus foto ulasan terlebih dahulu
        $pdo->prepare("DELETE FROM foto_ulasan WHERE id_ulasan = ?")->execute([$id_ulasan]);

        // Hapus ulasan
        $pdo->prepare("DELETE FROM ulasan WHERE id_ulasan = ?")->execute([$id_ulasan]);

        // Perbarui rata-rata rating restoran
        $pdo->prepare("
            UPDATE restoran r 
            SET r.rating = (
                SELECT COALESCE(ROUND(AVG(u.rating), 1), 0) 
                FROM ulasan u 
                WHERE u.id_restoran = r.id_restoran
            ) 
            WHERE r.id_restoran = ?
        ")->execute([$id_resto]);
    }

    // Sinkronkan juga dengan file reviews.json jika ada
    $json_reviews = read_json('reviews.json');
    if (!empty($json_reviews)) {
        $filtered = array_filter($json_reviews, function($item) use ($id_ulasan) {
            return ($item['id'] ?? 0) != $id_ulasan;
        });
        write_json('reviews.json', array_values($filtered));
    }

    echo json_encode(['status' => 'success', 'message' => 'Ulasan Anda berhasil dihapus!']);
    exit;

} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    exit;
}
