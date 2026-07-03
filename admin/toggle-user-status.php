<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_admin();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$user_id  = intval($input['user_id'] ?? 0);
$new_status = ($input['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
    exit;
}

$users = get_users();
$found = false;
foreach ($users as &$u) {
    if ($u['id'] === $user_id && $u['role'] !== 'admin') {
        $u['status'] = $new_status;
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
    exit;
}

try {
    $pdo = get_db();
    if ($pdo) {
        $db_status = ($new_status === 'active') ? 'aktif' : 'nonaktif';
        $pdo->prepare("UPDATE user SET status = ? WHERE id_user = ? AND role != 'admin'")->execute([$db_status, $user_id]);
    }
} catch (Throwable $e) {}

write_json('users.json', $users);
$label = $new_status === 'active' ? 'diaktifkan' : 'dinonaktifkan';
echo json_encode(['success' => true, 'message' => "Pengguna berhasil $label."]);
