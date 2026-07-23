<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    try {
        $pdo = get_db();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT id_restoran FROM ulasan WHERE id_ulasan = ?");
            $stmt->execute([$id]);
            $rest_id = $stmt->fetchColumn();
            
            $pdo->prepare("DELETE FROM foto_ulasan WHERE id_ulasan = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM ulasan WHERE id_ulasan = ?")->execute([$id]);
            
            if ($rest_id) {
                $pdo->prepare("
                    UPDATE restoran r 
                    SET r.rating = (SELECT COALESCE(ROUND(AVG(u.rating), 1), 0) FROM ulasan u WHERE u.id_restoran = r.id_restoran) 
                    WHERE r.id_restoran = ?
                ")->execute([$rest_id]);
            }
        }
    } catch (Throwable $e) {}

    $reviews = read_json('reviews.json');
    $reviews = array_filter($reviews, fn($r) => (int)$r['id'] !== $id);
    try { write_json('reviews.json', array_values($reviews)); } catch(Throwable $e){}
    $_SESSION['success'] = 'Ulasan berhasil dihapus.';
}
redirect('ulasan.php');
