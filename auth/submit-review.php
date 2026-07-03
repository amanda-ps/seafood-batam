<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurant_id = intval($_POST['restaurant_id']);
    $rating = intval($_POST['rating']);
    $comment = sanitize($_POST['comment']);

    if ($rating < 1 || $rating > 5) $rating = 5;
    if (empty(trim($comment))) {
        $_SESSION['error'] = 'Komentar ulasan tidak boleh kosong.';
        redirect('/restaurant-detail.php?id=' . $restaurant_id . '#reviews');
    }

    $current_user_id = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0;
    
    // Check if user already reviewed in MySQL or JSON
    $already = false;
    try {
        $pdo = get_db();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT id_ulasan FROM ulasan WHERE id_restoran = ? AND id_user = ?");
            $stmt->execute([$restaurant_id, $current_user_id]);
            if ($stmt->fetchColumn()) $already = true;
        }
    } catch (Throwable $e) {}

    $reviews = read_json('reviews.json');
    if (!$already) {
        foreach($reviews as $r) {
            if ($r['restaurant_id'] == $restaurant_id && $r['user_id'] == $current_user_id) {
                $already = true; break;
            }
        }
    }

    if ($already) {
        $_SESSION['error'] = 'Anda sudah mengulas restoran ini sebelumnya.';
        redirect('/restaurant-detail.php?id=' . $restaurant_id . '#reviews');
    }

    // Process uploaded media (images & videos)
    $uploaded_media = [];
    $target_dir = __DIR__ . "/../assets/images/reviews/";

    $allowed_images = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $allowed_videos = ['mp4', 'webm', 'mov', 'ogg'];
    $max_file_size  = 50 * 1024 * 1024; // 50 MB for videos

    $field_name = isset($_FILES['review_media']) ? 'review_media' : (isset($_FILES['review_photos']) ? 'review_photos' : null);

    if ($field_name && isset($_FILES[$field_name])) {
        $files = $_FILES[$field_name];
        $count = count($files['name']);
        if ($count > 5) $count = 5; // max 5 files

        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($files['size'][$i] > $max_file_size) continue;

            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            $type = null;

            if (in_array($ext, $allowed_images)) {
                $type = 'image';
            } elseif (in_array($ext, $allowed_videos)) {
                $type = 'video';
            }

            if ($type) {
                $new_name   = uniqid('rev_') . '.' . $ext;
                $target     = $target_dir . $new_name;
                if (move_uploaded_file($files['tmp_name'][$i], $target)) {
                    $uploaded_media[] = [
                        'type' => $type,
                        'path' => '/assets/images/reviews/' . $new_name
                    ];
                }
            }
        }
    }

    $new_review = [
        'id'            => empty($reviews) ? 1 : max(array_column($reviews, 'id')) + 1,
        'user_id'       => $current_user_id,
        'restaurant_id' => $restaurant_id,
        'rating'        => $rating,
        'comment'       => $comment,
        'media'         => $uploaded_media,
        // backwards-compat: also store image paths as 'photos'
        'photos'        => array_map(fn($m) => $m['path'], array_filter($uploaded_media, fn($m) => $m['type'] === 'image')),
        'date'          => date('Y-m-d H:i:s')
    ];

    $reviews[] = $new_review;
    write_json('reviews.json', $reviews);

    // Simpan ulasan baru langsung ke MySQL (tabel ulasan dan foto_ulasan)
    try {
        if (isset($pdo) && $pdo) {
            $ins = $pdo->prepare("INSERT INTO ulasan (id_user, id_restoran, rating, isi_ulasan, tanggal_ulasan) VALUES (?, ?, ?, ?, NOW())");
            $ins->execute([$current_user_id, $restaurant_id, $rating, $comment]);
            $new_id = $pdo->lastInsertId();

            if (!empty($new_review['photos'])) {
                foreach ($new_review['photos'] as $p) {
                    $ins_f = $pdo->prepare("INSERT INTO foto_ulasan (id_ulasan, url_foto, created_at) VALUES (?, ?, NOW())");
                    $ins_f->execute([$new_id, $p]);
                }
            }

            // Update rating restoran di database MySQL
            $upd = $pdo->prepare("
                UPDATE restoran r 
                SET r.rating = (SELECT COALESCE(ROUND(AVG(u.rating), 1), r.rating) FROM ulasan u WHERE u.id_restoran = r.id_restoran) 
                WHERE r.id_restoran = ?
            ");
            $upd->execute([$restaurant_id]);
        }
    } catch (Throwable $e) {}

    // Update restaurant average rating and count
    $restaurants = get_restaurants();
    foreach($restaurants as &$res) {
        if ($res['id'] == $restaurant_id) {
            $new_count  = $res['reviews_count'] + 1;
            $new_rating = (($res['rating'] * $res['reviews_count']) + $rating) / $new_count;
            $res['rating']        = round($new_rating, 1);
            $res['reviews_count'] = $new_count;
            break;
        }
    }
    write_json('restaurants.json', $restaurants);

    $_SESSION['success'] = 'Ulasan berhasil dikirim! Terima kasih atas kontribusi Anda.';
    redirect('/restaurant-detail.php?id=' . $restaurant_id . '#reviews');

} else {
    redirect('/index.php');
}
