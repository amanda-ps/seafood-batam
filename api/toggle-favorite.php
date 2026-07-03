<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan masuk (login) terlebih dahulu untuk menyimpan favorit.']);
    exit;
}

$pdo = get_db();
$id_user = $_SESSION['id_user'];

$id_restoran = 0;
if (isset($_POST['id_restoran'])) {
    $id_restoran = (int)$_POST['id_restoran'];
} elseif (isset($_POST['restaurant_id'])) {
    $id_restoran = (int)$_POST['restaurant_id'];
} elseif (isset($_POST['id'])) {
    $id_restoran = (int)$_POST['id'];
} else {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $id_restoran = (int)($json['id_restoran'] ?? $json['restaurant_id'] ?? $json['id'] ?? 0);
        }
    }
}

if ($id_restoran <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID restoran tidak valid']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_favorit FROM favorit WHERE id_user = ? AND id_restoran = ?");
    $stmt->execute([$id_user, $id_restoran]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("DELETE FROM favorit WHERE id_favorit = ?");
        $stmt->execute([$existing['id_favorit']]);
        echo json_encode(['success' => true, 'status' => 'removed', 'message' => 'Restoran dihapus dari favorit']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO favorit (id_user, id_restoran) VALUES (?, ?)");
        $stmt->execute([$id_user, $id_restoran]);
        echo json_encode(['success' => true, 'status' => 'added', 'message' => 'Restoran disimpan ke favorit']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada database: ' . $e->getMessage()]);
}