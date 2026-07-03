<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = current_user();
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
$pdo = get_db();

// Load user's data
$all_reviews = read_json('reviews.json');
$restaurants = get_restaurants();
$user_reviews = array_filter($all_reviews, fn($r) => $r['user_id'] == $user['id']);

// Build restaurant map
$rest_map = [];
foreach($restaurants as $r) $rest_map[$r['id']] = $r;

// Load user's favorite restaurants natively from MySQL
$fav_restaurants = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.*, k.nama_kecamatan,
               (SELECT COUNT(*) FROM ulasan u WHERE u.id_restoran = r.id_restoran) as reviews_count
        FROM favorit f
        JOIN restoran r ON f.id_restoran = r.id_restoran
        LEFT JOIN kecamatan k ON r.id_kecamatan = k.id_kecamatan
        WHERE f.id_user = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$_SESSION['id_user']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $fav_restaurants[] = [
            'id' => $row['id_restoran'],
            'name' => $row['nama_restoran'],
            'description' => $row['deskripsi'],
            'district' => $row['nama_kecamatan'],
            'rating' => (float)$row['rating'],
            'reviews_count' => (int)$row['reviews_count'],
            'photos' => !empty($row['foto_utama']) ? [$row['foto_utama']] : ['https://images.unsplash.com/photo-1565557623262-b51c2513a641']
        ];
    }
} catch (Exception $e) {
    $fav_restaurants = [];
}

$active_tab = $_GET['tab'] ?? 'favorites';

$initial = mb_strtoupper(mb_substr($user['display_name'] ?? $user['username'], 0, 1));
$display = $user['display_name'] ?? $user['username'];
$avatar_colors = ['#2D6A3F','#E07B2A','#3B82F6','#9C27B0','#F44336','#00BCD4'];
$avatar_color  = $avatar_colors[$user['id'] % count($avatar_colors)];
?>

<style>
/* Profile page */
.prof-page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 40px 20px 60px;
}

.prof-banner {
    background: var(--clr-green);
    height: 160px;
    border-radius: var(--radius-md) var(--radius-md) 0 0;
    position: relative;
    overflow: hidden;
}

.prof-banner::after {
    content: '';
    position: absolute;
    inset: 0;
    background: url('https://images.unsplash.com/photo-1559742811-822873691dc8?auto=format&fit=crop&w=900&q=60') center/cover;
    opacity: 0.25;
    mix-blend-mode: overlay;
}

.prof-header {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-top: none;
    border-radius: 0 0 var(--radius-md) var(--radius-md);
    padding: 0 32px 24px;
    margin-bottom: 24px;
}

.prof-avatar-wrap {
    display: flex;
    align-items: flex-end;
    gap: 20px;
    position: relative;
    top: -28px;
    margin-bottom: -10px;
}

.prof-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 4px solid var(--bg-card);
    box-shadow: var(--shadow-sm);
    object-fit: cover;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 800;
    font-family: 'Poppins', sans-serif;
    color: #FFFFFF;
}

.prof-meta { flex: 1; padding-top: 32px; }

.prof-name {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--heading-color);
    font-family: 'Poppins', sans-serif;
    letter-spacing: -0.02em;
    margin-bottom: 4px;
}

.prof-username {
    font-size: 0.85rem;
    color: var(--text-muted);
    font-family: 'Poppins', sans-serif;
}

.prof-stats {
    display: flex;
    gap: 24px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.prof-stat {
    font-size: 0.82rem;
    color: var(--text-muted);
    font-family: 'Poppins', sans-serif;
}

.prof-stat strong {
    color: var(--heading-color);
    font-weight: 700;
    margin-right: 3px;
}

/* Tabs */
.prof-tabs {
    display: flex;
    gap: 0;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 6px;
    margin-bottom: 24px;
    overflow-x: auto;
    box-shadow: var(--shadow-xs);
}

.prof-tab-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 8px;
    border: none;
    background: none;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-muted);
    white-space: nowrap;
    transition: all 0.2s ease;
    text-decoration: none;
}

.prof-tab-btn:hover {
    background: var(--bg-subtle);
    color: var(--text-primary);
}

.prof-tab-btn.active {
    background: var(--clr-green);
    color: #FFFFFF;
}

.prof-tab-btn .tab-count {
    background: rgba(255,255,255,0.25);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 20px;
    font-family: 'Poppins', sans-serif;
}

.prof-tab-btn:not(.active) .tab-count {
    background: var(--bg-subtle);
    color: var(--text-muted);
}

/* Content */
.prof-panel { display: none; }
.prof-panel.active { display: block; }

/* Empty state */
.prof-empty {
    text-align: center;
    padding: 56px 20px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
}

.prof-empty i { font-size: 2.5rem; color: var(--text-muted); display:block; margin-bottom:16px; }
.prof-empty h3 { margin-bottom: 10px; }
.prof-empty p { color: var(--text-muted); font-size: 0.88rem; margin-bottom: 20px; }

/* Edit form */
.prof-form-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    overflow: hidden;
    box-shadow: var(--shadow-xs);
}

.prof-form-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 12px;
}

.prof-form-header i { color: var(--clr-green); }
.prof-form-header h3 { font-size: 0.95rem; margin: 0; }

.prof-form-body {
    padding: 24px;
}

/* Avatar preview */
.avatar-prev-ring {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    border: 3px solid var(--clr-green);
    overflow: hidden;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    font-weight: 800;
    font-family: 'Poppins', sans-serif;
    color: #FFFFFF;
}
</style>

<div class="prof-page section">

    <!-- Banner + Header -->
    <div class="prof-banner"></div>
    <div class="prof-header">
        <div class="prof-avatar-wrap">
            <?php if (!empty($user['avatar'])): ?>
                <img src="<?= htmlspecialchars($user['avatar']) ?>"
                     class="prof-avatar" alt="Avatar">
            <?php else: ?>
                <div class="prof-avatar" style="background:<?= $avatar_color ?>;">
                    <?= htmlspecialchars($initial) ?>
                </div>
            <?php endif; ?>
            <div class="prof-meta">
                <div class="prof-name"><?= htmlspecialchars($display) ?></div>
                <div class="prof-username">@<?= htmlspecialchars($user['username']) ?></div>
                <div class="prof-stats">
                    <span class="prof-stat"><strong><?= count($fav_restaurants) ?></strong>Restoran Favorit</span>
                    <span class="prof-stat"><strong><?= count($user_reviews) ?></strong>Ulasan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="prof-tabs">
        <a href="?tab=favorites" class="prof-tab-btn <?= $active_tab==='favorites'?'active':'' ?>">
            <i class="fa-solid fa-heart"></i> Restoran Tersimpan
            <span class="tab-count"><?= count($fav_restaurants) ?></span>
        </a>
        <a href="?tab=reviews" class="prof-tab-btn <?= $active_tab==='reviews'?'active':'' ?>">
            <i class="fa-solid fa-star"></i> Ulasan Saya
            <span class="tab-count"><?= count($user_reviews) ?></span>
        </a>
        <a href="?tab=edit" class="prof-tab-btn <?= $active_tab==='edit'?'active':'' ?>">
            <i class="fa-solid fa-pen"></i> Edit Profil
        </a>
    </div>

    <!-- ─── TAB: FAVORITES ─── -->
    <div class="prof-panel <?= $active_tab==='favorites'?'active':'' ?>">
        <h2 style="margin-bottom:20px; font-size:1.1rem; color:var(--heading-color);">
            <i class="fa-solid fa-heart" style="color:#ef4444; margin-right:8px;"></i>
            Restoran Tersimpan
        </h2>
        <?php if (empty($fav_restaurants)): ?>
            <div class="prof-empty">
                <i class="fa-regular fa-heart"></i>
                <h3>Belum ada restoran favorit</h3>
                <p>Klik ikon ♥ di kartu restoran untuk menyimpannya di sini.</p>
                <a href="<?= BASE_URL ?>/restaurants.php" class="btn btn-primary">Jelajahi Restoran</a>
            </div>
        <?php else: ?>
            <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:20px;">
                <?php foreach($fav_restaurants as $r): ?>
                <div style="position:relative;">
                    <a href="<?= BASE_URL ?>/restaurant-detail.php?id=<?= $r['id'] ?>" class="card" style="display:block;">
                        <div class="card-img-wrap">
                            <img src="<?= htmlspecialchars($r['photos'][0] ?? 'https://images.unsplash.com/photo-1565557623262-b51c2513a641') ?>"
                                 alt="<?= htmlspecialchars($r['name']) ?>" class="card-img">
                            <div class="card-badge">
                                <i class="fa-solid fa-star" style="color:#fbbf24; margin-right:2px;"></i>
                                <?= $r['rating'] ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?= htmlspecialchars($r['name']) ?></h3>
                            <p style="font-size:0.85rem; color:var(--clr-orange); margin-bottom:6px; font-weight:600;">
                                <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($r['district']) ?>
                            </p>
                            <p class="card-text"><?= htmlspecialchars($r['description']) ?></p>
                        </div>
                    </a>
                    <!-- Heart remove button -->
                    <button onclick="event.stopPropagation(); toggleFavorite(<?= $r['id'] ?>);"
                            data-fav-id="<?= $r['id'] ?>"
                            style="position:absolute; top:10px; left:10px; background:rgba(255,255,255,0.92); backdrop-filter:blur(4px); border:none; width:32px; height:32px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,0,0,0.18); z-index:5;">
                        <i class="fa-solid fa-heart" style="color:#ef4444; font-size:0.85rem; pointer-events:none;"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ─── TAB: REVIEWS ─── -->
    <div class="prof-panel <?= $active_tab==='reviews'?'active':'' ?>">
        <h2 style="margin-bottom:20px; font-size:1.1rem; color:var(--heading-color);">
            <i class="fa-solid fa-star" style="color:#fbbf24; margin-right:8px;"></i>
            Ulasan Saya
        </h2>
        <?php if (empty($user_reviews)): ?>
            <div class="prof-empty">
                <i class="fa-regular fa-comment-dots"></i>
                <h3>Belum ada ulasan</h3>
                <p>Bagikan pengalaman makan seafood Anda ke restoran favorit.</p>
                <a href="<?= BASE_URL ?>/restaurants.php" class="btn btn-primary">Jelajahi Restoran</a>
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:14px;">
                <?php foreach($user_reviews as $rev):
                    $r = $rest_map[$rev['restaurant_id']] ?? null;
                ?>
                <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-md); padding:18px 20px; box-shadow:var(--shadow-xs);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; gap:12px;">
                        <div>
                            <div style="font-weight:700; font-size:0.9rem; color:var(--heading-color); margin-bottom:3px;">
                                <?= $r ? htmlspecialchars($r['name']) : '—' ?>
                            </div>
                            <div style="display:flex; gap:2px; color:#fbbf24; font-size:0.85rem;">
                                <?php for($s=1;$s<=5;$s++): ?>
                                    <i class="fa-solid fa-star" style="color:<?=$s<=$rev['rating']?'#fbbf24':'#E5E7EB';?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-family:'Poppins',sans-serif;">
                            <?= htmlspecialchars(substr($rev['date'] ?? '', 0, 10)) ?>
                        </div>
                    </div>
                    <p style="font-size:0.875rem; color:var(--text-secondary); line-height:1.65; margin:0;">
                        <?= htmlspecialchars($rev['comment']) ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ─── TAB: EDIT PROFILE ─── -->
    <div class="prof-panel <?= $active_tab==='edit'?'active':'' ?>">
        <?php if (isset($_SESSION['edit_success'])): ?>
            <div class="alert alert-success" style="margin-bottom:16px;">
                <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($_SESSION['edit_success']) ?>
            </div>
            <?php unset($_SESSION['edit_success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['edit_error'])): ?>
            <div class="alert alert-error" style="margin-bottom:16px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['edit_error']) ?>
            </div>
            <?php unset($_SESSION['edit_error']); ?>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/auth/update-profile.php" enctype="multipart/form-data">
            <!-- Avatar card -->
            <div class="prof-form-card" style="margin-bottom:20px;">
                <div class="prof-form-header">
                    <i class="fa-solid fa-circle-user"></i>
                    <h3>Foto Profil</h3>
                </div>
                <div class="prof-form-body" style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                    <div class="avatar-prev-ring" id="avatar-preview-wrap" style="background:<?= $avatar_color ?>;">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($user['avatar']) ?>"
                                 id="avatar-preview-img"
                                 style="width:100%; height:100%; object-fit:cover;" alt="Avatar">
                        <?php else: ?>
                            <span id="avatar-preview-text"><?= htmlspecialchars($initial) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="btn btn-outline" style="cursor:pointer;">
                            <i class="fa-solid fa-upload"></i> Unggah Foto
                            <input type="file" name="avatar" accept="image/*" style="display:none;"
                                   onchange="previewAvatar(this)">
                        </label>
                        <p style="font-size:0.78rem; color:var(--text-muted); margin-top:8px; font-family:'Poppins',sans-serif;">JPG/PNG maks. 2MB</p>
                    </div>
                </div>
            </div>

            <!-- Info card -->
            <div class="prof-form-card" style="margin-bottom:20px;">
                <div class="prof-form-header">
                    <i class="fa-solid fa-user"></i>
                    <h3>Informasi Profil</h3>
                </div>
                <div class="prof-form-body">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="display_name" class="form-control"
                                   value="<?= htmlspecialchars($user['display_name'] ?? $user['username']) ?>">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Nama Pengguna</label>
                            <input type="text" name="username" class="form-control"
                                   value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        <div class="form-group" style="margin:0; grid-column:1/-1;">
                            <label class="form-label">Bio</label>
                            <textarea name="bio" class="form-control" rows="3"
                                      placeholder="Ceritakan sedikit tentang diri Anda..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">No. Telepon</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= htmlspecialchars($user['whatsapp'] ?? '') ?>"
                                   placeholder="+62 812 xxxx xxxx">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password card -->
            <div class="prof-form-card" style="margin-bottom:24px;">
                <div class="prof-form-header">
                    <i class="fa-solid fa-lock"></i>
                    <h3>Ganti Kata Sandi</h3>
                </div>
                <div class="prof-form-body">
                    <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:18px; font-family:'Poppins',sans-serif;">Biarkan kosong jika tidak ingin mengganti kata sandi.</p>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Kata Sandi Baru</label>
                            <input type="password" name="new_password" class="form-control"
                                   placeholder="Min. 6 karakter">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Konfirmasi Kata Sandi Baru</label>
                            <input type="password" name="confirm_password" class="form-control"
                                   placeholder="Ulangi kata sandi baru">
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <a href="?tab=favorites" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</div>

<script>
function previewAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    const wrap = document.getElementById('avatar-preview-wrap');
    reader.onload = (e) => {
        wrap.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="Preview">';
    };
    reader.readAsDataURL(input.files[0]);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
