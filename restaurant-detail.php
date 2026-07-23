<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
$pdo = get_db();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$restaurant = get_restaurant($id);

if (!$restaurant) {
    echo '<div class="container section text-center"><h1>Restoran Tidak Ditemukan</h1></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$reviews = get_reviews($id);
$is_fav = false;
if (isset($_SESSION['id_user'])) {
    $stmt = $pdo->prepare("SELECT 1 FROM favorit WHERE id_user = ? AND id_restoran = ?");
    $stmt->execute([$_SESSION['id_user'], $id]);
    $is_fav = (bool)$stmt->fetch();
}

// Ensure defaults
$wa     = $restaurant['whatsapp'] ?? '';
$wa_url = $wa ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $wa) . "?text=" . urlencode("Halo, saya ingin melakukan reservasi di " . $restaurant['name']) : '#';

// Photo handling
$photos     = $restaurant['photos'] ?? ['https://images.unsplash.com/photo-1565557623262-b51c2513a641'];
$main_photo = $photos[0];

// Gunakan koordinat asli dari database tabel restoran jika sudah diisi
$db_lat = $restaurant['latitude'] ?? $restaurant['lat'] ?? $restaurant['koordinat_lat'] ?? $restaurant['maps_lat'] ?? 0;
$db_lng = $restaurant['longitude'] ?? $restaurant['lng'] ?? $restaurant['koordinat_lng'] ?? $restaurant['maps_lng'] ?? 0;

if (!empty($db_lat) && !empty($db_lng) && (float)$db_lat != 0 && (float)$db_lng != 0) {
    $map_lat = (float)$db_lat;
    $map_lng = (float)$db_lng;
} else {
    // District → approximate [lat, lng] for Batam sebagai fallback
    $district_coords = [
        'Batam Kota'      => [1.1291, 104.0222],
        'Lubuk Baja'      => [1.1449, 104.0210],
        'Batu Ampar'      => [1.1188, 104.0481],
        'Bengkong'        => [1.1634, 104.0453],
        'Sekupang'        => [1.0973, 103.9742],
        'Nongsa'          => [1.1780, 104.1054],
        'Sagulung'        => [1.0834, 104.0107],
        'Batu Aji'        => [1.0670, 104.0023],
        'Belakang Padang' => [1.0667, 103.9667],
        'Bulang'          => [0.9833, 104.0333],
        'Galang'          => [0.9000, 104.2667],
        'Sei Beduk'       => [1.1167, 104.0000],
    ];
    $map_center = $district_coords[$restaurant['district']] ?? [1.1301, 104.0529];
    $map_lat    = $map_center[0];
    $map_lng    = $map_center[1];
}

// Link Google Maps dari database atau generate dari koordinat
$maps_url = !empty($restaurant['maps_link']) && $restaurant['maps_link'] !== '#'
    ? $restaurant['maps_link']
    : "https://www.google.com/maps?q=" . $map_lat . "," . $map_lng;

// Generate Google Maps Embed URL untuk iframe berdasarkan link di database atau nama/alamat
$embed_query = '';
if (!empty($restaurant['maps_link']) && $restaurant['maps_link'] !== '#') {
    $ml = $restaurant['maps_link'];
    if (preg_match('/[?&](?:query|q)=([^&]+)/i', $ml, $matches)) {
        $embed_query = $matches[1];
    } elseif (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $ml, $matches)) {
        $embed_query = $matches[1] . ',' . $matches[2];
    } elseif (preg_match('/place\/([^\/]+)/i', $ml, $matches)) {
        $embed_query = $matches[1];
    }
}
if (empty($embed_query)) {
    // Cek apakah koordinat valid (bukan 0 dan bukan 9.999999 salah ketik)
    $valid_lat = (!empty($map_lat) && (float)$map_lat != 0);
    $valid_lng = (!empty($map_lng) && (float)$map_lng > 100 && (float)$map_lng < 110);
    if ($valid_lat && $valid_lng) {
        $embed_query = $map_lat . ',' . $map_lng;
    } else {
        $embed_query = urlencode($restaurant['name'] . ', ' . ($restaurant['address'] ?: ($restaurant['district'] . ', Kota Batam')));
    }
}
$google_embed_url = "https://maps.google.com/maps?q=" . $embed_query . "&z=15&output=embed";
?>

<style>
/* ── Review Form & Cards ── */
.review-form-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    overflow: hidden;
    margin-bottom: 32px;
    box-shadow: var(--shadow-sm);
}

.review-form-header {
    background: linear-gradient(135deg, var(--clr-green) 0%, var(--clr-green-dark) 100%);
    padding: 18px 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.review-form-header h4 {
    color: #FFFFFF;
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
}

.review-form-body {
    padding: 24px;
}

/* Star Rating */
.star-rating-group {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}

.star-rating-label {
    font-family: 'Poppins', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-primary);
    white-space: nowrap;
}

.star-rating-interactive {
    display: flex;
    gap: 4px;
    cursor: pointer;
}

.star-rating-interactive i {
    font-size: 1.6rem;
    color: #d1d5db;
    transition: color 0.15s ease, transform 0.1s ease;
}

.star-rating-interactive i.active,
.star-rating-interactive i.hovered {
    color: #fbbf24;
}

.star-rating-interactive i:hover {
    transform: scale(1.18);
}

.star-rating-text {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-muted);
    min-width: 60px;
}

/* Media upload area */
.media-upload-zone {
    border: 2px dashed var(--border-strong);
    border-radius: var(--radius-sm);
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    background: var(--bg-subtle);
    position: relative;
}

.media-upload-zone:hover,
.media-upload-zone.dragover {
    border-color: var(--clr-green);
    background: var(--clr-green-subtle);
}

[data-theme="dark"] .media-upload-zone:hover,
[data-theme="dark"] .media-upload-zone.dragover {
    background: rgba(52, 160, 90, 0.08);
}

.media-upload-zone input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}

.media-upload-icon {
    font-size: 1.8rem;
    color: var(--text-muted);
    margin-bottom: 8px;
}

.media-upload-text {
    font-size: 0.85rem;
    color: var(--text-muted);
    font-family: 'Poppins', sans-serif;
}

.media-upload-hint {
    font-size: 0.75rem;
    color: var(--text-disabled);
    margin-top: 4px;
}

/* Media preview strip */
.media-preview-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 14px;
}

.media-preview-item {
    position: relative;
    width: 80px;
    height: 80px;
    border-radius: var(--radius-sm);
    overflow: hidden;
    border: 2px solid var(--border);
    flex-shrink: 0;
}

.media-preview-item img,
.media-preview-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.media-preview-remove {
    position: absolute;
    top: 3px;
    right: 3px;
    background: rgba(0,0,0,0.65);
    color: #fff;
    border: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    cursor: pointer;
    transition: background 0.15s;
}

.media-preview-remove:hover { background: #ef4444; }

.media-preview-type-badge {
    position: absolute;
    bottom: 3px;
    left: 3px;
    background: rgba(0,0,0,0.60);
    color: #fff;
    font-size: 0.58rem;
    padding: 1px 5px;
    border-radius: 3px;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

/* Review card */
.review-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 22px 24px;
    transition: var(--transition);
}

.review-card:hover {
    box-shadow: var(--shadow-sm);
    border-color: var(--border-strong);
}

.review-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.reviewer-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--clr-green-subtle);
    color: var(--clr-green);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    font-weight: 700;
    font-family: 'Poppins', sans-serif;
    flex-shrink: 0;
    border: 2px solid var(--border);
}

[data-theme="dark"] .reviewer-avatar {
    background: rgba(52,160,90,0.14);
    color: var(--primary);
}

.reviewer-name {
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--heading-color);
}

.reviewer-date {
    font-size: 0.75rem;
    color: var(--text-muted);
    display: block;
    margin-top: 1px;
}

.review-stars {
    display: flex;
    align-items: center;
    gap: 2px;
}

.review-stars i { color: #fbbf24; font-size: 0.85rem; }
.review-stars i.empty { color: #d1d5db; }

.review-comment {
    color: var(--text-secondary);
    font-size: 0.875rem;
    line-height: 1.7;
    margin-bottom: 14px;
}

.review-media-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.review-media-strip img {
    width: 72px;
    height: 72px;
    object-fit: cover;
    border-radius: 6px;
    cursor: zoom-in;
    border: 1px solid var(--border);
    transition: var(--transition);
}

.review-media-strip img:hover {
    transform: scale(1.05);
    border-color: var(--clr-orange);
}

.review-media-strip video {
    width: 120px;
    height: 72px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--border);
}
</style>

<!-- Detail Header -->
<div class="detail-header" style="background-image: url('<?= htmlspecialchars($main_photo) ?>');">
    <div class="detail-overlay"></div>
    <div class="container detail-header-content">
        <div class="flex justify-between align-center" style="gap:20px; flex-wrap:wrap;">
            <div>
                <h1 class="detail-title"><?= htmlspecialchars($restaurant['name']) ?></h1>
                <p style="font-size:1.1rem; margin-bottom:8px; color:rgba(255,255,255,0.95);">
                    <i class="fa-solid fa-star" style="color:#fbbf24;"></i>
                    <?= $restaurant['rating'] ?>
                    <span style="opacity:0.75; font-weight:400;">
                        (<a href="#reviews" style="text-decoration:underline; color:rgba(255,255,255,0.85);"><?= $restaurant['reviews_count'] ?> ulasan</a>)
                    </span>
                </p>
                <p style="color:rgba(255,255,255,0.82); font-size:0.9rem;">
                    <i class="fa-solid fa-location-dot" style="color:#F97316;"></i>
                    <?= htmlspecialchars($restaurant['address']) ?> (<?= htmlspecialchars($restaurant['district']) ?>)
                </p>
            </div>
            <div>
                <button onclick="toggleFavorite(<?= $id ?>)" id="fav-btn" 
                        class="btn <?= $is_fav ? 'btn-secondary' : 'btn-ghost' ?>"
                        style="background:rgba(255,255,255,0.15); backdrop-filter:blur(8px); border-color:rgba(255,255,255,0.35); color:#FFFFFF;">
                    <i class="fa-<?= $is_fav ? 'solid' : 'regular' ?> fa-heart" 
                       style="color:<?= $is_fav ? '#ef4444' : 'rgba(255,255,255,0.85)' ?>;"></i>
                    <span id="fav-text"><?= $is_fav ? 'Tersimpan' : 'Simpan' ?></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container section">
    <div class="grid" style="grid-template-columns: 2fr 1fr; gap:50px;">
        <!-- Left Column -->
        <div>
            <!-- About -->
            <div style="margin-bottom:40px;">
                <span class="eyebrow">Tentang</span>
                <h2 style="margin-bottom:16px;"><?= htmlspecialchars($restaurant['name']) ?></h2>
                <p style="color:var(--text-secondary); font-size:0.95rem; line-height:1.75;"><?= nl2br(htmlspecialchars($restaurant['description'])) ?></p>
            </div>

            <!-- Gallery -->
            <div style="margin-bottom:80px;">
                <span class="eyebrow">Galeri Foto</span>
                <div style="width:100%; height:360px; border-radius:var(--radius-md); overflow:hidden; margin-bottom:12px; box-shadow:var(--shadow-md);">
                    <img id="main-gallery-img" src="<?= htmlspecialchars($main_photo) ?>" 
                         alt="Galeri Utama" style="width:100%; height:100%; object-fit:cover; transition:opacity 0.25s ease;"
                         referrerpolicy="no-referrer"
                         onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1565557623262-b51c2513a641?auto=format&fit=crop&w=800&q=80';">
                </div>
                <div class="gallery-grid" style="grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap:8px;">
                    <?php foreach($photos as $index => $img): ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="Foto <?= $index ?>" 
                             onclick="switchMainImg(this.src)"
                             referrerpolicy="no-referrer"
                             onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1565557623262-b51c2513a641?auto=format&fit=crop&w=800&q=80';"
                             style="height:90px; cursor:pointer; border-radius:var(--radius-sm); 
                                    border:2px solid <?= $index === 0 ? 'var(--clr-orange)' : 'transparent' ?>;
                                    transition:all 0.2s ease;"
                             class="gallery-thumb"
                             data-index="<?= $index ?>">
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Menu -->
            <div style="margin-bottom:40px;">
                <?php if(empty($restaurant['menus'])): ?>
                    <p class="text-muted">Menu belum diunggah.</p>
                <?php else: ?>
                <?php
                    $menus      = $restaurant['menus'];
                    $half       = (int) ceil(count($menus) / 2);
                    $left_col   = array_slice($menus, 0, $half);
                    $right_col  = array_slice($menus, $half);
                    $rows       = max(count($left_col), count($right_col));
                ?>
                <!-- Outer wrapper: position relative so badge can overlap top border -->
                <div style="position:relative; padding-top:18px;">
                    <!-- MENU badge -->
                    <div style="position:absolute; top:0; left:50%; transform:translateX(-50%);
                                background:var(--clr-green); color:#FFFFFF;
                                font-family:'Poppins',sans-serif; font-size:0.72rem; font-weight:700;
                                letter-spacing:0.14em; text-transform:uppercase;
                                padding:6px 24px; border-radius:4px;
                                box-shadow:0 2px 8px rgba(45,106,63,0.30);
                                z-index:2; white-space:nowrap;">
                        MENU BEST SELLER
                    </div>

                    <!-- Card -->
                    <div style="border:1.5px solid var(--border); border-radius:var(--radius-md);
                                background:var(--bg-card); overflow:hidden;
                                box-shadow:var(--shadow-sm);">

                        <!-- 2-column grid header row (invisible, just for structure) -->
                        <div style="display:grid; grid-template-columns:1fr 1fr; padding-top:10px;">

                            <?php for($i = 0; $i < $rows; $i++):
                                $l = $left_col[$i]  ?? null;
                                $r = $right_col[$i] ?? null;
                                $is_last = ($i === $rows - 1);
                            ?>
                            <!-- Left item -->
                            <div style="display:flex; justify-content:space-between; align-items:center;
                                        padding:13px 20px;
                                        <?= !$is_last ? 'border-bottom:1px solid var(--border);' : '' ?>
                                        border-right:1px solid var(--border); gap:12px;">
                                <?php if($l): ?>
                                    <span style="font-size:0.875rem; color:var(--text-primary);
                                                 font-family:'Poppins',sans-serif; line-height:1.4;">
                                        <?= htmlspecialchars($l['name']) ?>
                                    </span>
                                    <span style="font-size:0.875rem; font-weight:700;
                                                 color:var(--clr-green); font-family:'Poppins',sans-serif;
                                                 white-space:nowrap; flex-shrink:0;">
                                        <?= htmlspecialchars($l['price']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Right item -->
                            <div style="display:flex; justify-content:space-between; align-items:center;
                                        padding:13px 20px;
                                        <?= !$is_last ? 'border-bottom:1px solid var(--border);' : '' ?>
                                        gap:12px;">
                                <?php if($r): ?>
                                    <span style="font-size:0.875rem; color:var(--text-primary);
                                                 font-family:'Poppins',sans-serif; line-height:1.4;">
                                        <?= htmlspecialchars($r['name']) ?>
                                    </span>
                                    <span style="font-size:0.875rem; font-weight:700;
                                                 color:var(--clr-green); font-family:'Poppins',sans-serif;
                                                 white-space:nowrap; flex-shrink:0;">
                                        <?= htmlspecialchars($r['price']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php endfor; ?>

                        </div><!-- end grid -->
                    </div><!-- end card -->
                </div><!-- end wrapper -->
                <?php endif; ?>
            </div>

            <!-- Reviews Section -->
            <div id="reviews">
                <span class="eyebrow">Ulasan Pengunjung</span>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                    <h2 style="margin:0;">
                        <?= count($reviews) ?> Ulasan
                        <i class="fa-solid fa-star" style="color:#fbbf24; font-size:1.1rem; margin-left:8px;"></i>
                        <?= $restaurant['rating'] ?>
                    </h2>
                </div>

                <!-- Review Form -->
                <?php if(is_logged_in()): ?>
                <div class="review-form-card">
                    <div class="review-form-header">
                        <i class="fa-solid fa-pen-to-square" style="color:rgba(255,255,255,0.85); font-size:1rem;"></i>
                        <h4>Tulis Ulasan Anda</h4>
                    </div>
                    <div class="review-form-body">
                        <form action="<?= BASE_URL ?>/auth/submit-review.php" method="POST" enctype="multipart/form-data" id="review-form">
                            <input type="hidden" name="restaurant_id" value="<?= $id ?>">
                            <input type="hidden" name="rating" id="review-rating" value="0">

                            <!-- Star Rating -->
                            <div class="star-rating-group">
                                <span class="star-rating-label">Penilaian Anda:</span>
                                <div class="star-rating-interactive" id="interactive-stars">
                                    <?php for($s = 1; $s <= 5; $s++): ?>
                                        <i class="fa-regular fa-star" data-val="<?= $s ?>" style="color:#d1d5db;"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="star-rating-text" id="star-label">Pilih rating Anda</span>
                            </div>

                            <!-- Comment -->
                            <div class="form-group">
                                <label class="form-label" for="review-comment">
                                    <i class="fa-solid fa-comment" style="color:var(--clr-orange);"></i>
                                    Komentar <span style="color:#ef4444;">*</span>
                                </label>
                                <textarea name="comment" id="review-comment" class="form-control" rows="4" 
                                          placeholder="Bagikan pengalaman makan Anda di sini — makanan, pelayanan, suasana..." 
                                          required></textarea>
                            </div>

                            <!-- Media Upload -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-solid fa-photo-film" style="color:var(--primary);"></i>
                                    Foto / Video (Maks. 5 file)
                                </label>
                                <div class="media-upload-zone" id="media-upload-zone">
                                    <input type="file" name="review_media[]" id="review-media-input"
                                           accept="image/*,video/*" multiple>
                                    <div class="media-upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                    <div class="media-upload-text">Klik atau seret file ke sini</div>
                                    <div class="media-upload-hint">JPG, PNG, WebP · MP4, WebM, MOV · Maks. 50MB per file</div>
                                </div>
                                <div class="media-preview-strip" id="media-preview-strip"></div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg" id="submit-review-btn">
                                <i class="fa-solid fa-paper-plane"></i> Kirim Ulasan
                            </button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info" style="margin-bottom:28px;">
                    <i class="fa-solid fa-circle-info"></i>
                    Silakan <a href="<?= BASE_URL ?>/auth/login.php" style="color:var(--clr-orange); font-weight:600; text-decoration:underline;">Masuk</a> 
                    untuk menulis ulasan atau menyimpan restoran ini.
                </div>
                <?php endif; ?>

                <!-- Reviews List -->
                <?php if(empty($reviews)): ?>
                    <div style="text-align:center; padding:40px 20px; background:var(--bg-card); border-radius:var(--radius-md); border:1px solid var(--border);">
                        <i class="fa-regular fa-comment-dots" style="font-size:2.5rem; color:var(--text-muted); display:block; margin-bottom:12px;"></i>
                        <h4 style="color:var(--heading-color); margin-bottom:8px;">Belum ada ulasan</h4>
                        <p style="color:var(--text-muted); font-size:0.875rem;">Jadilah yang pertama mengulas restoran ini!</p>
                    </div>
                <?php else: ?>
                    <?php 
                        $current_user_id = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0;
                    ?>
                    <div style="display:flex; flex-direction:column; gap:16px;">
                    <?php foreach(array_reverse(array_values($reviews)) as $rev):
                        $u = get_user($rev['user_id']);
                        $uname = $u ? $u['username'] : 'Anonim';
                        $initial = mb_strtoupper(mb_substr($uname, 0, 1));
                        
                        // Gather media
                        $media = $rev['media'] ?? [];
                        if (empty($media) && !empty($rev['photos'])) {
                            foreach($rev['photos'] as $p) {
                                $media[] = ['type' => 'image', 'path' => $p];
                            }
                        }
                        foreach($media as &$m) {
                            if (isset($m['path']) && strpos($m['path'], '/assets/') === 0 && strpos($m['path'], BASE_URL) !== 0) {
                                $m['path'] = BASE_URL . $m['path'];
                            }
                        }
                        unset($m);
                    ?>
                        <div class="review-card">
                            <div class="review-card-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar"><?= htmlspecialchars($initial) ?></div>
                                    <div>
                                        <div class="reviewer-name"><?= htmlspecialchars($uname) ?></div>
                                        <span class="reviewer-date">
                                            <i class="fa-regular fa-calendar" style="margin-right:3px;"></i>
                                            <?= htmlspecialchars($rev['date'] ?? 'Baru saja') ?>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <div class="review-stars">
                                        <?php for($s = 1; $s <= 5; $s++): ?>
                                            <i class="fa-solid fa-star <?= $s > $rev['rating'] ? 'empty' : '' ?>"
                                               style="color:<?= $s <= $rev['rating'] ? '#fbbf24' : '#d1d5db' ?>;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div style="text-align:right; font-size:0.78rem; color:var(--text-muted); font-weight:600; margin-top:2px;">
                                        <?= $rev['rating'] ?>/5
                                    </div>
                                    <?php if ($current_user_id && ($rev['user_id'] == $current_user_id || is_admin())): ?>
                                        <div style="text-align:right; margin-top:8px;">
                                            <button type="button" 
                                                    onclick="deleteMyReview(<?= intval($rev['id']) ?>, <?= intval($id) ?>)"
                                                    title="Hapus ulasan saya"
                                                    style="background:rgba(239, 68, 68, 0.1); color:#ef4444; border:1px solid rgba(239, 68, 68, 0.3); padding:4px 10px; border-radius:6px; font-size:0.75rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all 0.2s;">
                                                <i class="fa-solid fa-trash-can"></i> Hapus
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <p class="review-comment"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>

                            <?php if (!empty($media)): ?>
                                <div class="review-media-strip">
                                    <?php foreach($media as $m): ?>
                                        <?php if ($m['type'] === 'image'): ?>
                                            <img src="<?= htmlspecialchars($m['path']) ?>" 
                                                 alt="Review media"
                                                 onerror="this.style.display='none';"
                                                 onclick="window.open(this.src, '_blank')">
                                        <?php else: ?>
                                            <video src="<?= htmlspecialchars($m['path']) ?>" controls
                                                   preload="metadata"></video>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div><!-- end left column -->

        <!-- Right Column / Sidebar -->
        <div>
            <div style="background:var(--bg-card); padding:28px; border-radius:var(--radius-md); 
                        box-shadow:var(--shadow-md); position:sticky; top:100px; border:1px solid var(--border);">
                <h4 style="margin-bottom:24px; font-size:1.05rem; border-bottom:1px solid var(--border); padding-bottom:14px;">
                    Info &amp; Lokasi
                </h4>

                <div style="display:flex; flex-direction:column; gap:16px; margin-bottom:24px;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:34px; height:34px; background:var(--clr-green-subtle); color:var(--clr-green); 
                                    border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="fa-solid fa-clock" style="font-size:0.85rem;"></i>
                        </div>
                        <div>
                            <small style="color:var(--text-muted); display:block; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em;">Jam Operasional</small>
                            <strong style="font-size:0.9rem; color:var(--text-primary);"><?= htmlspecialchars($restaurant['hours']) ?></strong>
                        </div>
                    </div>

                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:34px; height:34px; background:rgba(249,115,22,0.1); color:var(--clr-orange); 
                                    border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="fa-solid fa-location-dot" style="font-size:0.85rem;"></i>
                        </div>
                        <div>
                            <small style="color:var(--text-muted); display:block; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em;">Kecamatan</small>
                            <strong style="font-size:0.9rem; color:var(--text-primary);"><?= htmlspecialchars($restaurant['district']) ?></strong>
                        </div>
                    </div>

                    <div style="display:flex; align-items:flex-start; gap:12px;">
                        <div style="width:34px; height:34px; background:rgba(59,130,246,0.1); color:#3B82F6; 
                                    border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px;">
                            <i class="fa-solid fa-map-pin" style="font-size:0.85rem;"></i>
                        </div>
                        <div>
                            <small style="color:var(--text-muted); display:block; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em;">Alamat Lengkap</small>
                            <strong style="font-size:0.85rem; color:var(--text-primary); line-height:1.4; display:block;"><?= htmlspecialchars($restaurant['address'] ?: ($restaurant['district'] . ', Kota Batam')) ?></strong>
                        </div>
                    </div>
                </div>

                <!-- ── Google Maps Embed ── -->
                <div style="margin-bottom:14px;">
                    <div style="height: 250px; width: 100%; border-radius:var(--radius-sm); border:1px solid var(--border); box-shadow:var(--shadow-sm); overflow:hidden; background:#E5E7EB; position:relative;">
                        <iframe 
                            src="<?= htmlspecialchars($google_embed_url) ?>" 
                            width="100%" 
                            height="100%" 
                            style="border:0; position:absolute; top:0; left:0; width:100%; height:100%;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
                <a href="<?= htmlspecialchars($maps_url) ?>" target="_blank"
                   class="btn btn-outline" style="display:flex; width:100%; margin-bottom:12px;">
                    <i class="fa-solid fa-map-location-dot"></i> Buka di Google Maps
                </a>

                <?php if($wa): ?>
                <a href="<?= $wa_url ?>" target="_blank" 
                   class="btn" style="display:flex; width:100%; margin-bottom:20px; background:#25D366; color:#fff; border-color:#25D366;">
                    <i class="fa-brands fa-whatsapp"></i> Reservasi WhatsApp
                </a>
                <?php endif; ?>


            </div>
        </div>
    </div>
</div>

<script>
// Gallery switcher
function switchMainImg(src) {
    const main = document.getElementById('main-gallery-img');
    main.style.opacity = '0';
    setTimeout(() => {
        main.src = src;
        main.style.opacity = '1';
    }, 180);
    document.querySelectorAll('.gallery-thumb').forEach(t => {
        t.style.borderColor = t.src === src ? 'var(--clr-orange)' : 'transparent';
    });
}

// Interactive star rating
(function() {
    const stars = document.querySelectorAll('#interactive-stars i');
    const ratingInput = document.getElementById('review-rating');
    const starLabel = document.getElementById('star-label');
    const labels = ['', 'Buruk', 'Kurang', 'Cukup', 'Bagus', 'Sempurna!'];

    if (!stars.length || !ratingInput) return;

    function setStars(val, mode) {
        stars.forEach(s => {
            const sv = parseInt(s.getAttribute('data-val'));
            if (sv <= val) {
                s.classList.add('active');
                s.classList.remove('fa-regular');
                s.classList.add('fa-solid');
                s.style.color = '#fbbf24';
            } else {
                s.classList.remove('active');
                s.classList.remove('fa-solid');
                s.classList.add('fa-regular');
                s.style.color = '#d1d5db';
            }
        });
        if (starLabel) {
            if (val === 0) {
                starLabel.textContent = "Pilih rating Anda";
                starLabel.style.color = 'var(--text-secondary)';
            } else {
                starLabel.textContent = val + ' / 5 — ' + (labels[val] || '');
                starLabel.style.color = val >= 4 ? 'var(--clr-orange)' : (val >= 3 ? 'var(--text-secondary)' : '#ef4444');
            }
        }
    }

    let current = parseInt(ratingInput.value) || 0;
    setStars(current);

    stars.forEach(star => {
        star.addEventListener('mouseover', function() {
            setStars(parseInt(this.getAttribute('data-val')), 'hover');
        });
        star.addEventListener('mouseout', function() {
            setStars(current);
        });
        star.addEventListener('click', function() {
            current = parseInt(this.getAttribute('data-val'));
            ratingInput.value = current;
            setStars(current);
        });
    });
})();

// Media upload preview
(function() {
    const input = document.getElementById('review-media-input');
    const strip = document.getElementById('media-preview-strip');
    const zone  = document.getElementById('media-upload-zone');
    if (!input || !strip) return;

    let selectedFiles = [];

    function renderPreview() {
        strip.innerHTML = '';
        selectedFiles.forEach((file, idx) => {
            const item = document.createElement('div');
            item.className = 'media-preview-item';

            const isVideo = file.type.startsWith('video/');
            const el = document.createElement(isVideo ? 'video' : 'img');
            el.src = URL.createObjectURL(file);
            if (isVideo) { el.controls = false; el.muted = true; el.autoplay = false; }
            item.appendChild(el);

            const badge = document.createElement('div');
            badge.className = 'media-preview-type-badge';
            badge.textContent = isVideo ? 'video' : 'foto';
            item.appendChild(badge);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'media-preview-remove';
            removeBtn.title = 'Hapus gambar ini';
            removeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                selectedFiles.splice(idx, 1);
                syncFiles();
                renderPreview();
            });
            item.appendChild(removeBtn);
            strip.appendChild(item);
        });
    }

    function syncFiles() {
        const dt = new DataTransfer();
        selectedFiles.forEach(f => dt.items.add(f));
        input.files = dt.files;
    }

    input.addEventListener('change', function() {
        const newFiles = Array.from(this.files);
        const remaining = 5 - selectedFiles.length;
        const toAdd = newFiles.slice(0, remaining);
        selectedFiles = selectedFiles.concat(toAdd);
        syncFiles();
        if (selectedFiles.length >= 5) {
            if (typeof showToast === 'function') showToast('Maksimal 5 file dapat diunggah.', 'error');
        }
        renderPreview();
    });

    // Drag & drop
    if (zone) {
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('dragover');
            const dropped = Array.from(e.dataTransfer.files);
            const allowed = dropped.filter(f => f.type.startsWith('image/') || f.type.startsWith('video/'));
            const remaining = 5 - selectedFiles.length;
            selectedFiles = selectedFiles.concat(allowed.slice(0, remaining));
            syncFiles();
            renderPreview();
        });
    }
})();

// Form submit feedback
const reviewForm = document.getElementById('review-form');
const submitBtn  = document.getElementById('submit-review-btn');
if (reviewForm && submitBtn) {
    reviewForm.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...';
    });
}

async function deleteMyReview(idUlasan, restoId) {
    const result = await Swal.fire({
        title: 'Hapus Ulasan Ini?',
        text: 'Apakah kamu yakin ingin menghapus ulasanmu?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    });

    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('id_ulasan', idUlasan);
            formData.append('restaurant_id', restoId);

            const res = await fetch(BASE_URL + '/auth/delete-my-review.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status === 'success') {
                await Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: data.message,
                    confirmButtonColor: '#F97316'
                });
                location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: data.message || 'Gagal menghapus ulasan.',
                    confirmButtonColor: '#e11d48'
                });
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan Jaringan',
                text: 'Tidak dapat terhubung ke server.',
                confirmButtonColor: '#e11d48'
            });
        }
    }
}

function normalisasiTeks(teks) {
    let hasil = teks.toLowerCase();
    const petaLeet = {
        '1': 'i', '!': 'i', '|': 'i',
        '3': 'e',
        '4': 'a', '@': 'a',
        '5': 's', '$': 's',
        '0': 'o',
        '9': 'g',
        '7': 't'
    };
    hasil = hasil.replace(/[1!|34@5$09 7]/g, c => petaLeet[c] ?? c);
    const tanpaSpasi = hasil.replace(/[\s._\-*]+/g, '').replace(/(.)\1{2,}/g, '$1');
    const denganSpasi = hasil.replace(/[._\-*]+/g, '').replace(/(.)\1{2,}/g, '$1');
    return { tanpaSpasi, denganSpasi, asli: teks.toLowerCase() };
}

function cekKataTerlarangJS(teks) {
    const daftarKata = [
        'anjing', 'bangsat', 'babi', 'kontol', 'memek', 'ngentot',
        'kafir', 'lonte', 'perek', 'pelacur', 'goblok', 'idiot', 'bajingan', 'pantek'
    ];
    const { tanpaSpasi, denganSpasi, asli } = normalisasiTeks(teks);
    for (const kata of daftarKata) {
        if (asli.includes(kata) || tanpaSpasi.includes(kata) || denganSpasi.includes(kata)) {
            return true;
        }
    }
    return false;
}

document.addEventListener('DOMContentLoaded', () => {
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', (e) => {
            const ratingInput = document.getElementById('review-rating');
            if (ratingInput && (parseInt(ratingInput.value) || 0) === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Rating Belum Dipilih',
                    text: 'Harap berikan penilaian bintang sebelum mengirimkan ulasan.',
                    confirmButtonColor: '#fbbf24'
                });
                return;
            }

            const commentInput = reviewForm.querySelector('textarea[name="comment"]');
            if (commentInput && cekKataTerlarangJS(commentInput.value)) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Ulasan Gagal Dikirim',
                    text: 'Komentar tidak layak atau mengandung unsur SARA / kata kotor.',
                    confirmButtonColor: '#e11d48'
                });
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
