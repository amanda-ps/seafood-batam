<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

if (!defined('BASE_URL')) {
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $dir = str_replace('\\', '/', dirname(__DIR__));
    $base = str_ireplace($docRoot, '', $dir);
    define('BASE_URL', rtrim($base, '/'));
}

$data_dir = __DIR__ . '/../data';
$reviews_img_dir = __DIR__ . '/../assets/images/reviews';

function read_json($filename) {
    global $data_dir;
    $filepath = "$data_dir/$filename";
    if (!file_exists($filepath)) {
        return [];
    }
    $json = file_get_contents($filepath);
    return json_decode($json, true) ?: [];
}

function write_json($filename, $data) {
    global $data_dir;
    $filepath = "$data_dir/$filename";
    file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
}

function get_restaurants() {
    $pdo = get_db();
    $stmt = $pdo->query("
        SELECT r.*, k.nama_kecamatan, 
               (SELECT COUNT(*) FROM ulasan u WHERE u.id_restoran = r.id_restoran) as reviews_count
        FROM restoran r 
        LEFT JOIN kecamatan k ON r.id_kecamatan = k.id_kecamatan
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $restaurants = [];
    foreach ($rows as $row) {
        $restaurants[] = [
            'id' => $row['id_restoran'],
            'name' => $row['nama_restoran'],
            'description' => $row['deskripsi'],
            'district' => $row['nama_kecamatan'],
            'rating' => (float)$row['rating'],
            'reviews_count' => (int)$row['reviews_count'],
            'address' => $row['alamat'],
            'hours' => $row['jam_operasional_weekday'],
            'maps_link' => $row['maps'],
            'latitude' => $row['latitude'] ?? $row['lat'] ?? $row['koordinat_lat'] ?? $row['maps_lat'] ?? null,
            'longitude' => $row['longitude'] ?? $row['lng'] ?? $row['koordinat_lng'] ?? $row['maps_lng'] ?? null,
            'whatsapp' => $row['no_wa'],
            'photos' => !empty($row['foto_utama']) ? [$row['foto_utama']] : [],
            'menus' => []
        ];
    }
    return $restaurants;
}

function get_restaurant($id) {
    $pdo = get_db();
    $stmt = $pdo->prepare("
        SELECT r.*, k.nama_kecamatan, 
               (SELECT COUNT(*) FROM ulasan u WHERE u.id_restoran = r.id_restoran) as reviews_count
        FROM restoran r 
        LEFT JOIN kecamatan k ON r.id_kecamatan = k.id_kecamatan
        WHERE r.id_restoran = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) return null;
    
    $stmtPhotos = $pdo->prepare("SELECT url_foto FROM foto_restoran WHERE id_restoran = ?");
    $stmtPhotos->execute([$id]);
    $photoRows = $stmtPhotos->fetchAll(PDO::FETCH_COLUMN);
    
    $photos = !empty($row['foto_utama']) ? [$row['foto_utama']] : [];
    foreach ($photoRows as $p) {
        if ($p !== $row['foto_utama']) {
            $photos[] = $p;
        }
    }
    if (empty($photos)) {
        $photos[] = 'https://images.unsplash.com/photo-1565557623262-b51c2513a641';
    }

    return [
        'id' => $row['id_restoran'],
        'name' => $row['nama_restoran'],
        'description' => $row['deskripsi'],
        'district' => $row['nama_kecamatan'],
        'rating' => (float)$row['rating'],
        'reviews_count' => (int)$row['reviews_count'],
        'address' => $row['alamat'],
        'hours' => $row['jam_operasional_weekday'],
        'maps_link' => $row['maps'],
        'latitude' => $row['latitude'] ?? $row['lat'] ?? $row['koordinat_lat'] ?? $row['maps_lat'] ?? null,
        'longitude' => $row['longitude'] ?? $row['lng'] ?? $row['koordinat_lng'] ?? $row['maps_lng'] ?? null,
        'whatsapp' => $row['no_wa'],
        'photos' => $photos,
        'menus' => []
    ];
}

function get_reviews($restaurant_id = null) {
    try {
        $pdo = get_db();
        if ($pdo) {
            $sql = "
                SELECT u.id_ulasan as id,
                       u.id_user as user_id,
                       u.id_restoran as restaurant_id,
                       u.rating,
                       u.isi_ulasan as comment,
                       u.tanggal_ulasan as date,
                       COALESCE(usr.username, 'Anonim') as username,
                       usr.foto_profile as user_avatar
                FROM ulasan u
                LEFT JOIN user usr ON u.id_user = usr.id_user
            ";
            $params = [];
            if ($restaurant_id) {
                $sql .= " WHERE u.id_restoran = ?";
                $params[] = $restaurant_id;
            }
            $sql .= " ORDER BY u.tanggal_ulasan DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $reviews = [];
            foreach ($rows as $row) {
                $stmt_foto = $pdo->prepare("SELECT url_foto FROM foto_ulasan WHERE id_ulasan = ?");
                $stmt_foto->execute([$row['id']]);
                $photos = $stmt_foto->fetchAll(PDO::FETCH_COLUMN) ?: [];
                
                $media = [];
                foreach ($photos as $p) {
                    $media[] = ['type' => 'image', 'path' => $p];
                }
                
                $row['photos'] = $photos;
                $row['media']  = $media;
                $reviews[] = $row;
            }
            return $reviews;
        }
    } catch (Throwable $e) {}

    $reviews = read_json('reviews.json');
    if ($restaurant_id) {
         return array_values(array_filter($reviews, function($r) use ($restaurant_id) {
             return $r['restaurant_id'] == $restaurant_id;
         }));
    }
    return $reviews;
}

function get_users() {
    try {
        $pdo = get_db();
        if ($pdo) {
            $stmt = $pdo->query("SELECT id_user as id, COALESCE(nama_lengkap, username) as display_name, nama_lengkap, username, nohp as whatsapp, nohp as phone, email, password as password_hash, role, foto_profile as avatar, jenis_kelamin, status FROM user");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                foreach ($rows as &$r) {
                    $r['gender'] = ($r['jenis_kelamin'] === 'Laki-laki') ? 'male' : (($r['jenis_kelamin'] === 'Perempuan') ? 'female' : $r['jenis_kelamin']);
                    $r['status'] = ($r['status'] === 'aktif' || $r['status'] === 'active') ? 'active' : 'inactive';
                }
                return $rows;
            }
        }
    } catch (Throwable $e) {}
    return read_json('users.json');
}

function get_user($id) {
    $users = get_users();
    foreach ($users as $u) {
        if ($u['id'] == $id) return $u;
    }
    return null;
}

function ensure_admin_exists() {
    $users = get_users();
    $admin_exists = false;
    foreach ($users as $u) {
        if ($u['email'] === 'admin@batamseafood.com') {
            $admin_exists = true;
            break;
        }
    }
    if (!$admin_exists) {
        $users[] = [
            'id' => empty($users) ? 1 : max(array_column($users, 'id')) + 1,
            'username' => 'admin',
            'email' => 'admin@batamseafood.com',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'whatsapp' => '+628111222333',
            'role' => 'admin'
        ];
        write_json('users.json', $users);
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    if (strpos($url, '/') === 0) {
        $url = BASE_URL . $url;
    }
    header("Location: $url");
    exit;
}

function update_user($id, $fields) {
    try {
        $pdo = get_db();
        if ($pdo) {
            $set_cols = [];
            $params = [];
            if (isset($fields['display_name'])) { $set_cols[] = "nama_lengkap = ?"; $params[] = $fields['display_name']; }
            if (isset($fields['username']))     { $set_cols[] = "username = ?"; $params[] = $fields['username']; }
            if (isset($fields['email']))        { $set_cols[] = "email = ?"; $params[] = $fields['email']; }
            if (isset($fields['whatsapp']))     { $set_cols[] = "nohp = ?"; $params[] = $fields['whatsapp']; }
            if (isset($fields['phone']))        { $set_cols[] = "nohp = ?"; $params[] = $fields['phone']; }
            if (isset($fields['avatar']))       { $set_cols[] = "foto_profile = ?"; $params[] = $fields['avatar']; }
            if (isset($fields['password_hash'])){ $set_cols[] = "password = ?"; $params[] = $fields['password_hash']; }
            if (isset($fields['status']))       { $set_cols[] = "status = ?"; $params[] = ($fields['status'] === 'active' || $fields['status'] === 'aktif' ? 'aktif' : 'nonaktif'); }
            if (isset($fields['gender']))       { 
                $g = $fields['gender'] === 'male' ? 'Laki-laki' : ($fields['gender'] === 'female' ? 'Perempuan' : $fields['gender']);
                $set_cols[] = "jenis_kelamin = ?"; 
                $params[] = $g; 
            }

            if (!empty($set_cols)) {
                $params[] = $id;
                $stmt = $pdo->prepare("UPDATE user SET " . implode(", ", $set_cols) . " WHERE id_user = ?");
                $stmt->execute($params);
            }
        }
    } catch (Throwable $e) {}

    $users = get_users();
    foreach ($users as &$u) {
        if ($u['id'] == $id) {
            foreach ($fields as $key => $value) {
                $u[$key] = $value;
            }
            break;
        }
    }
    write_json('users.json', $users);
}

function is_logged_in() {
    return isset($_SESSION['id_user']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['error'] = 'Anda harus masuk untuk mengakses halaman ini.';
        redirect('/auth/login.php');
    }
}

function require_admin() {
    if (!is_admin()) {
        $_SESSION['error'] = 'Akses ditolak. Hanya untuk Admin.';
        redirect('/index.php');
    }
}

function current_user() {
    if (is_logged_in()) {
        return get_user($_SESSION['id_user']);
    }
    return null;
}

function get_user_fav_ids() {
    if (!is_logged_in()) return [];
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT id_restoran FROM favorit WHERE id_user = ?");
        $stmt->execute([$_SESSION['id_user']]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

// Ensure data folder exists
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0777, true);
}
// Ensure upload folder exists
if (!is_dir($reviews_img_dir)) {
    mkdir($reviews_img_dir, 0777, true);
}
// Ensure avatars folder exists
$avatars_dir = __DIR__ . '/../assets/images/avatars';
if (!is_dir($avatars_dir)) {
    mkdir($avatars_dir, 0777, true);
}

// If favorites.json doesn't exist, create it
if (!file_exists("$data_dir/favorites.json")) {
    write_json('favorites.json', []);
}
// If reviews.json doesn't exist, create it
if (!file_exists("$data_dir/reviews.json")) {
    write_json('reviews.json', []);
}

ensure_admin_exists();
?>
