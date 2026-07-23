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

$updated = false;
try {
    $pdo = get_db();
    if ($pdo) {
        $db_status = ($new_status === 'active') ? 'aktif' : 'nonaktif';
        $stmt = $pdo->prepare("UPDATE user SET status = ? WHERE id_user = ? AND role != 'admin'");
        $stmt->execute([$db_status, $user_id]);
        if ($stmt->rowCount() > 0) {
            $updated = true;
        } else {
            // Check if user exists in MySQL
            $chk = $pdo->prepare("SELECT id_user FROM user WHERE id_user = ? AND role != 'admin'");
            $chk->execute([$user_id]);
            if ($chk->fetchColumn()) $updated = true;
        }
    }
} catch (Throwable $e) {}

$users = get_users();
foreach ($users as &$u) {
    if ((int)$u['id'] === $user_id && $u['role'] !== 'admin') {
        $u['status'] = $new_status;
        $updated = true;
        break;
    }
}

if ($updated) {
    try { write_json('users.json', $users); } catch(Throwable $e){}
    $label = $new_status === 'active' ? 'diaktifkan' : 'dinonaktifkan';
    echo json_encode(['success' => true, 'message' => "Pengguna berhasil $label."]);
} else {
    echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan atau adalah admin.']);
}
