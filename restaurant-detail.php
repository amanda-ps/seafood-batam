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
?>

<!-- Leaflet CSS — must load before map div renders -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />

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
                <?php if (is_logged_in()): ?>
                <button onclick="toggleFavorite(<?= $id ?>)" id="fav-btn" 
                        class="btn <?= $is_fav ? 'btn-secondary' : 'btn-ghost' ?>"
                        style="background:rgba(255,255,255,0.15); backdrop-filter:blur(8px); border-color:rgba(255,255,255,0.35); color:#FFFFFF;">
                    <i class="fa-<?= $is_fav ? 'solid' : 'regular' ?> fa-heart" 
                       style="color:<?= $is_fav ? '#ef4444' : 'rgba(255,255,255,0.85)' ?>;"></i>
                    <span id="fav-text"><?= $is_fav ? 'Tersimpan' : 'Simpan' ?></span>
                </button>
                <?php endif; ?>
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
                         alt="Galeri Utama" style="width:100%; height:100%; object-fit:cover; transition:opacity 0.25s ease;">
                </div>
                <div class="gallery-grid" style="grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap:8px;">
                    <?php foreach($photos as $index => $img): ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="Foto <?= $index ?>" 
                             onclick="switchMainImg(this.src)"
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
                        MENU
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
                            <input type="hidden" name="rating" id="review-rating" value="5">

                            <!-- Star Rating -->
                            <div class="star-rating-group">
                                <span class="star-rating-label">Penilaian Anda:</span>
                                <div class="star-rating-interactive" id="interactive-stars">
                                    <?php for($s = 1; $s <= 5; $s++): ?>
                                        <i class="fa-solid fa-star active" data-val="<?= $s ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="star-rating-text" id="star-label">5 / 5 ★</span>
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
                                </div>
                            </div>
                            
                            <p class="review-comment"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>

                            <?php if (!empty($media)): ?>
                                <div class="review-media-strip">
                                    <?php foreach($media as $m): ?>
                                        <?php if ($m['type'] === 'image'): ?>
                                            <img src="<?= htmlspecialchars($m['path']) ?>" 
                                                 alt="Review media"
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

                <!-- ── Leaflet Map ── -->
                <div style="margin-bottom:14px;">
                    <div id="map-detail" style="height: 250px; width: 100%; z-index: 1; border-radius:var(--radius-sm); border:1px solid var(--border); box-shadow:var(--shadow-sm);"></div>
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

<!-- Leaflet JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
if (typeof L === 'undefined') {
    document.write('<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js"><\/script>');
}
</script>

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
                s.style.color = '#fbbf24';
            } else {
                s.classList.remove('active');
                s.style.color = '#d1d5db';
            }
        });
        if (starLabel) {
            starLabel.textContent = val + ' / 5 — ' + (labels[val] || '');
            starLabel.style.color = val >= 4 ? 'var(--clr-orange)' : (val >= 3 ? 'var(--text-secondary)' : '#ef4444');
        }
    }

    let current = parseInt(ratingInput.value) || 5;
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
            removeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            removeBtn.addEventListener('click', () => {
                selectedFiles.splice(idx, 1);
                syncFiles();
                renderPreview();
            });
            item.appendChild(removeBtn);
            strip.appendChild(item);
        });
    }

    function syncFiles() {
        // Create a new DataTransfer to update the input's files
        const dt = new DataTransfer();
        selectedFiles.forEach(f => dt.items.add(f));
        input.files = dt.files;
    }

    input.addEventListener('change', function() {
        const newFiles = Array.from(this.files);
        const remaining = 5 - selectedFiles.length;
        const toAdd = newFiles.slice(0, remaining);
        selectedFiles = selectedFiles.concat(toAdd);
        if (selectedFiles.length >= 5) {
            showToast('Maksimal 5 file dapat diunggah.', 'error');
        }
        renderPreview();
    });

    // Drag & drop
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

// ── Leaflet Map Init ──────────────────────────────────────────
function initDetailMap() {
    const mapEl = document.getElementById('map-detail');
    if (!mapEl || typeof L === 'undefined') return;
    if (mapEl._leaflet_id) return; // Mencegah inisialisasi ganda

    // Jika tinggi elemen masih 0 karena render layout belum selesai, tunggu sebentar
    if (mapEl.clientHeight === 0) {
        setTimeout(initDetailMap, 100);
        return;
    }

    let lat  = parseFloat(<?= json_encode($map_lat) ?>) || 1.1301;
    let lng  = parseFloat(<?= json_encode($map_lng) ?>) || 104.0529;

    // Koreksi otomatis jika koordinat di database salah ketik digit longitude (misal 14.0453 -> 104.0453 atau 10.40 -> 104.0)
    if (lng > 0 && lng < 100) lng += 90;
    if (lng > 10 && lng < 20) lng += 90;

    const name = <?= json_encode(htmlspecialchars($restaurant['name'])) ?>;
    const addr = <?= json_encode(htmlspecialchars($restaurant['address'] ?: ($restaurant['district'] . ', Kota Batam'))) ?>;
    const mapsUrl = <?= json_encode($maps_url) ?>;

    const map = L.map('map-detail', {
        center: [lat, lng],
        zoom: 12,
        scrollWheelZoom: false,
        zoomControl: true,
        attributionControl: true,
    });

    // Menggunakan OpenStreetMap contributors dengan fokus wilayah Kota Batam, Kepulauan Riau, Indonesia
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
    }).addTo(map);

    const markerIcon = L.divIcon({
        className: '',
        html: `<div style="
            width:36px; height:36px;
            background:#F97316;
            border:3px solid #FFFFFF;
            border-radius:50% 50% 50% 0;
            transform:rotate(-45deg);
            box-shadow:0 3px 10px rgba(0,0,0,0.30);
        "></div>
        <div style="
            position:absolute; top:50%; left:50%;
            transform:translate(-50%,-62%) rotate(45deg);
            color:#fff; font-size:14px; pointer-events:none;
        ">🍴</div>`,
        iconSize: [36, 36],
        iconAnchor: [18, 36],
        popupAnchor: [0, -38]
    });

    L.marker([lat, lng], { icon: markerIcon }).addTo(map)
        .bindPopup(`<div style="min-width:140px; font-family:'Poppins',sans-serif; text-align:center; padding:2px 0;">
            <strong style="font-size:14px; color:#1F2937; display:block; margin-bottom:6px;">${name}</strong>
            <a href="${mapsUrl}" target="_blank" style="font-size:12.5px; color:#F97316; font-weight:600; text-decoration:underline; display:inline-block;">
                Buka rute di Google Maps &rarr;
            </a>
        </div>`)
        .openPopup();

    // Pastikan ukuran kontainer dihitung ulang setelah render DOM & sticky sidebar
    setTimeout(() => { map.invalidateSize(true); }, 300);
    setTimeout(() => { map.invalidateSize(true); }, 800);
    setTimeout(() => { map.invalidateSize(true); }, 1500);
}

function checkAndInitMap() {
    if (typeof L !== 'undefined') {
        initDetailMap();
    } else {
        setTimeout(checkAndInitMap, 150);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', checkAndInitMap);
} else {
    checkAndInitMap();
}
window.addEventListener('load', checkAndInitMap);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
