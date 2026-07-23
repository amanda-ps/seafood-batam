<?php
require_once __DIR__ . '/includes/header.php';

$restaurants = get_restaurants();

// Build districts list from data
$all_districts = [];
foreach ($restaurants as $r) {
    if (!empty($r['district'])) $all_districts[] = $r['district'];
}
$districts = array_values(array_unique($all_districts));
sort($districts);

// Filter
$q        = isset($_GET['q'])        ? sanitize($_GET['q'])        : '';
$district = isset($_GET['district']) ? sanitize($_GET['district']) : '';
$q_lower  = strtolower($q);

if ($q_lower || $district) {
    $restaurants = array_filter($restaurants, function($r) use ($q_lower, $district) {
        $match = true;
        if ($q_lower) {
            $in_name = str_contains(strtolower($r['name']), $q_lower);
            $in_desc = str_contains(strtolower($r['description']), $q_lower);
            $in_menu = false;
            foreach ($r['menus'] ?? [] as $m) {
                if (str_contains(strtolower($m['name']), $q_lower)) { $in_menu = true; break; }
            }
            if (!$in_name && !$in_desc && !$in_menu) $match = false;
        }
        if ($district && $r['district'] !== $district) $match = false;
        return $match;
    });
}

// Sort by rating highest first as requested by user
if (!empty($restaurants) && is_array($restaurants)) {
    usort($restaurants, function($a, $b) {
        return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
    });
}


// User favorites
$user_fav_ids = get_user_fav_ids();
?>

<!-- ═══ PAGE HERO BANNER ════════════════════════════════════ -->
<section style="background: linear-gradient(135deg, rgba(27, 85, 48, 0.92) 0%, rgba(17, 17, 17, 0.86) 100%), url('https://images.unsplash.com/photo-1559742811-822873691dc8?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat; padding: 56px 20px 72px; text-align: center; position: relative; overflow: hidden; border-bottom: 1px solid rgba(255,255,255,0.08);">
    <div class="container" style="max-width:820px; position:relative; z-index:2;">
        <span style="display:inline-flex; align-items:center; gap:6px; background:rgba(249,115,22,0.18); color:#fb8f3d; padding:6px 16px; border-radius:50px; font-size:0.8rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; border:1px solid rgba(249,115,22,0.35); margin-bottom:14px; box-shadow:0 2px 8px rgba(0,0,0,0.15);">
            <i class="fa-solid fa-compass"></i> Direktori Kuliner Seafood Batam
        </span>
        <h1 style="color:#FFFFFF; font-size:clamp(1.9rem,4vw,2.7rem); font-weight:800; margin-bottom:12px; text-shadow:0 2px 12px rgba(0,0,0,0.45); line-height:1.18;">
            Jelajahi <span style="color:#fb8f3d;">Semua Restoran</span> Seafood
        </h1>
        <p style="color:rgba(255,255,255,0.86); font-size:1.02rem; max-width:640px; margin:0 auto 20px; line-height:1.6;">
            Temukan tempat makan olahan laut segar favorit di seluruh sudut Kota Batam dengan ulasan jujur, rekomendasi menu unggulan, dan rute lokasi akurat.
        </p>
        <div style="display:flex; justify-content:center; align-items:center; gap:12px; flex-wrap:wrap;">
            <span style="display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,0.12); backdrop-filter:blur(6px); color:#fff; padding:6px 16px; border-radius:30px; font-size:0.84rem; border:1px solid rgba(255,255,255,0.15);">
                <i class="fa-solid fa-store" style="color:#fbbf24;"></i> Menampilkan <strong><?= count($restaurants) ?></strong> Restoran
            </span>
            <span style="display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,0.12); backdrop-filter:blur(6px); color:#fff; padding:6px 16px; border-radius:30px; font-size:0.84rem; border:1px solid rgba(255,255,255,0.15);">
                <i class="fa-solid fa-map-location-dot" style="color:#4ade80;"></i> Tersebar di <strong><?= count($districts) ?></strong> Kecamatan
            </span>
            <?php if ($q || $district): ?>
            <span style="display:inline-flex; align-items:center; gap:6px; background:rgba(249,115,22,0.25); color:#fff; padding:6px 16px; border-radius:30px; font-size:0.84rem; border:1px solid rgba(249,115,22,0.5);">
                <i class="fa-solid fa-filter"></i> Filter aktif: "<strong><?= htmlspecialchars($q ?: $district) ?></strong>"
            </span>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══ FLOATING FILTER BAR & GRID ══════════════════════════ -->
<section class="section container" style="padding-top:0;">
    <!-- Filter Bar overlapping the hero -->
    <div style="margin-top:-34px; position:relative; z-index:10; margin-bottom:40px;">
        <form action="<?= BASE_URL ?>/restaurants.php" method="GET" id="filter-form"
              style="background:var(--bg-card); border-radius:50px;
                     box-shadow:0 12px 36px rgba(0,0,0,0.14); border:1.5px solid var(--border);
                     display:flex; align-items:center;
                     padding:8px 10px 8px 22px; gap:0; overflow:visible; max-width:880px; margin:0 auto;">

            <!-- Search icon + input -->
            <i class="fa-solid fa-magnifying-glass"
               style="color:var(--text-muted); font-size:0.95rem; flex-shrink:0; margin-right:12px;"></i>
            <input type="text" name="q" id="search-input"
                   value="<?= htmlspecialchars($q) ?>"
                   placeholder="Cari nama restoran atau masakan favorit Anda..."
                   style="flex:1; border:none; outline:none; background:transparent;
                          font-family:'Poppins',sans-serif; font-size:0.92rem;
                          color:var(--text-primary); min-width:0; font-weight:400;">

            <!-- Divider -->
            <div style="width:1px; height:28px; background:var(--border); flex-shrink:0; margin:0 12px;"></div>

            <!-- District select — outline pill -->
            <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                <select name="district" id="district-select"
                        onchange="this.form.submit()"
                        style="appearance:none; -webkit-appearance:none;
                               border:1.5px solid var(--border); border-radius:var(--radius-xl);
                               background:transparent; color:var(--text-primary);
                               font-family:'Poppins',sans-serif; font-size:0.84rem; font-weight:500;
                               padding:7px 32px 7px 16px; cursor:pointer; outline:none;
                               background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E\");
                               background-repeat:no-repeat; background-position:right 12px center;
                               transition:border-color 0.2s, box-shadow 0.2s; min-width:160px;">
                    <option value="">Semua Kecamatan</option>
                    <?php foreach($districts as $d): ?>
                        <option value="<?= htmlspecialchars($d) ?>" <?= $district===$d?'selected':'' ?>><?= htmlspecialchars($d) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Search Icon Button (sebelah kanan dari semua kecamatan) -->
                <button type="submit" id="search-submit-btn" title="Cari Restoran"
                        style="display:inline-flex; align-items:center; justify-content:center;
                               width:40px; height:40px; border-radius:50%;
                               background:var(--clr-orange); color:#fff; border:none;
                               cursor:pointer; flex-shrink:0;
                               box-shadow:0 3px 8px rgba(224,123,42,0.35); transition:all 0.18s;"
                        onmouseover="this.style.transform='scale(1.08)'"
                        onmouseout="this.style.transform='scale(1)'">
                    <i class="fa-solid fa-magnifying-glass" style="font-size:0.95rem;"></i>
                </button>

                <?php if ($q || $district): ?>
                <a href="<?= BASE_URL ?>/restaurants.php" title="Hapus semua filter"
                   style="display:inline-flex; align-items:center; justify-content:center;
                          width:34px; height:34px; border-radius:50%;
                          border:1.5px solid var(--border); color:var(--text-muted);
                          font-size:0.88rem; flex-shrink:0; text-decoration:none;
                          transition:all 0.18s;"
                   onmouseover="this.style.borderColor='var(--clr-orange)';this.style.color='var(--clr-orange)';"
                   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-muted)';">
                    <i class="fa-solid fa-xmark"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
    // Highlight select border when a district is active
    (function() {
        const sel = document.getElementById('district-select');
        if (!sel) return;
        if (sel.value) {
            sel.style.borderColor = 'var(--clr-green)';
            sel.style.boxShadow   = '0 0 0 2px rgba(45,106,63,0.12)';
        }
    })();
    </script>

    <!-- Grid -->
    <?php if (empty($restaurants)): ?>
        <div style="text-align:center; padding:60px 20px; background:var(--bg-card); border-radius:var(--radius-md); border:1px solid var(--border);">
            <i class="fa-solid fa-fish fa-3x" style="color:var(--text-muted); display:block; margin-bottom:16px;"></i>
            <h3 style="margin-bottom:10px;">Tidak ada restoran ditemukan</h3>
            <p style="color:var(--text-muted); margin-bottom:20px;">Coba hapus filter atau gunakan kata kunci lain.</p>
            <a href="<?= BASE_URL ?>/restaurants.php" class="btn btn-outline">Hapus Saringan</a>
        </div>
    <?php else: ?>
        <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:20px;">
            <?php foreach($restaurants as $r):
                $is_fav = in_array($r['id'], $user_fav_ids);
            ?>
            <div style="position:relative;">
                <a href="<?= BASE_URL ?>/restaurant-detail.php?id=<?= $r['id'] ?>" class="card" style="display:block;">
                    <div class="card-img-wrap">
                        <img src="<?= htmlspecialchars(!empty($r['foto_utama']) ? $r['foto_utama'] : ($r['photos'][0] ?? 'https://images.unsplash.com/photo-1565557623262-b51c2513a641')) ?>"
                             alt="<?= htmlspecialchars($r['name']) ?>" class="card-img"
                             referrerpolicy="no-referrer">
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
                        <div class="flex justify-between align-center mt-4">
                            <span style="font-size:0.8rem; color:var(--text-muted);"><?= $r['reviews_count'] ?> ulasan</span>
                            <span class="btn btn-primary" style="padding:4px 14px; font-size:0.8rem;">Lihat →</span>
                        </div>
                    </div>
                </a>
                <button onclick="event.stopPropagation(); toggleFavorite(<?= $r['id'] ?>);"
                        data-fav-id="<?= $r['id'] ?>"
                        title="<?= $is_fav ? 'Hapus favorit' : 'Simpan favorit' ?>"
                        style="position:absolute; top:10px; left:10px; background:rgba(255,255,255,0.92); backdrop-filter:blur(4px); border:none; width:32px; height:32px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,0,0,0.18); z-index:5; transition:transform 0.2s ease;"
                        onmouseover="this.style.transform='scale(1.15)'"
                        onmouseout="this.style.transform='scale(1)'">
                    <i class="fa-<?= $is_fav?'solid':'regular' ?> fa-heart" style="color:<?= $is_fav?'#ef4444':'#aaa' ?>; font-size:0.85rem; pointer-events:none;"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
