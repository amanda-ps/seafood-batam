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

// Sort by rating
if (!empty($restaurants) && is_array($restaurants)) {
    usort($restaurants, fn($a,$b) => ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0));
}

// User favorites
$user_fav_ids = get_user_fav_ids();
?>

<section class="section container">
    <!-- Page header -->
    <div style="margin-bottom:28px;">
        <h1 style="font-size:clamp(1.6rem,3.5vw,2rem); margin-bottom:6px; position:relative; display:inline-block;">
            Semua Restoran
            <span style="position:absolute; bottom:-4px; left:0; width:100%; height:4px; background:var(--clr-orange); border-radius:4px;"></span>
        </h1>
        <p style="color:var(--text-muted); margin-top:12px; font-size:0.9rem;">
            Temukan restoran seafood terbaik di seluruh Batam
        </p>
        <p style="font-size:0.82rem; color:var(--text-muted); margin-top:4px;">
            Menampilkan <strong style="color:var(--heading-color);"><?= count($restaurants) ?></strong> restoran
            <?php if ($q || $district): ?>
                untuk pencarian "<strong><?= htmlspecialchars($q ?: $district) ?></strong>"
            <?php endif; ?>
        </p>
    </div>

    <!-- Filter Bar -->
    <form action="<?= BASE_URL ?>/restaurants.php" method="GET" id="filter-form"
          style="background:var(--bg-card); border-radius:var(--radius-xl);
                 box-shadow:var(--shadow-sm); border:1px solid var(--border);
                 margin-bottom:36px; display:flex; align-items:center;
                 padding:8px 10px 8px 20px; gap:0; overflow:visible;">

        <!-- Search icon + input -->
        <i class="fa-solid fa-magnifying-glass"
           style="color:var(--text-muted); font-size:0.9rem; flex-shrink:0; margin-right:10px;"></i>
        <input type="text" name="q" id="search-input"
               value="<?= htmlspecialchars($q) ?>"
               placeholder="Cari nama restoran atau masakan..."
               style="flex:1; border:none; outline:none; background:transparent;
                      font-family:'Poppins',sans-serif; font-size:0.9rem;
                      color:var(--text-primary); min-width:0;">

        <!-- Divider -->
        <div style="width:1px; height:26px; background:var(--border); flex-shrink:0; margin:0 10px;"></div>

        <!-- District select — outline pill -->
        <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
            <select name="district" id="district-select"
                    onchange="handleDistrictChange(this)"
                    style="appearance:none; -webkit-appearance:none;
                           border:1.5px solid var(--border); border-radius:var(--radius-xl);
                           background:transparent; color:var(--text-primary);
                           font-family:'Poppins',sans-serif; font-size:0.83rem; font-weight:500;
                           padding:6px 30px 6px 14px; cursor:pointer; outline:none;
                           background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E\");
                           background-repeat:no-repeat; background-position:right 10px center;
                           transition:border-color 0.2s, box-shadow 0.2s; min-width:155px;">
                <option value="">Semua Kecamatan</option>
                <?php foreach($districts as $d): ?>
                    <option value="<?= htmlspecialchars($d) ?>" <?= $district===$d?'selected':'' ?>><?= htmlspecialchars($d) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Filter apply button — only visible when a district is chosen -->
            <button type="submit" id="filter-apply-btn"
                    style="display:<?= $district ? 'inline-flex' : 'none' ?>;
                           align-items:center; gap:6px;
                           background:var(--clr-green); color:#fff; border:none;
                           border-radius:var(--radius-xl); padding:7px 16px;
                           font-family:'Poppins',sans-serif; font-size:0.83rem; font-weight:600;
                           cursor:pointer; white-space:nowrap; flex-shrink:0;
                           transition:background 0.18s, transform 0.12s;">
                <i class="fa-solid fa-filter" style="font-size:0.78rem;"></i> Terapkan
            </button>

            <?php if ($q || $district): ?>
            <a href="<?= BASE_URL ?>/restaurants.php" title="Hapus semua filter"
               style="display:inline-flex; align-items:center; justify-content:center;
                      width:32px; height:32px; border-radius:50%;
                      border:1.5px solid var(--border); color:var(--text-muted);
                      font-size:0.85rem; flex-shrink:0; text-decoration:none;
                      transition:all 0.18s;"
               onmouseover="this.style.borderColor='var(--clr-orange)';this.style.color='var(--clr-orange)';"
               onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-muted)';">
                <i class="fa-solid fa-xmark"></i>
            </a>
            <?php endif; ?>
        </div>
    </form>

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

    function handleDistrictChange(sel) {
        const btn = document.getElementById('filter-apply-btn');
        if (sel.value) {
            // show button + highlight select
            btn.style.display = 'inline-flex';
            sel.style.borderColor = 'var(--clr-green)';
            sel.style.boxShadow   = '0 0 0 2px rgba(45,106,63,0.12)';
        } else {
            // hide button + reset select style
            btn.style.display = 'none';
            sel.style.borderColor = 'var(--border)';
            sel.style.boxShadow   = 'none';
            // If no search query either, submit to clear filters
            const q = document.getElementById('search-input');
            if (!q || !q.value.trim()) {
                window.location.href='<?= BASE_URL ?>/restaurants.php';
            }
        }
    }

    // Hover effect on apply button
    const applyBtn = document.getElementById('filter-apply-btn');
    if (applyBtn) {
        applyBtn.addEventListener('mouseover', () => applyBtn.style.background = 'var(--clr-green-light)');
        applyBtn.addEventListener('mouseout',  () => applyBtn.style.background = 'var(--clr-green)');
        applyBtn.addEventListener('mousedown', () => applyBtn.style.transform = 'translateY(1px)');
        applyBtn.addEventListener('mouseup',   () => applyBtn.style.transform = 'none');
    }
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
                        <div class="flex justify-between align-center mt-4">
                            <span style="font-size:0.8rem; color:var(--text-muted);"><?= $r['reviews_count'] ?> ulasan</span>
                            <span class="btn btn-primary" style="padding:4px 14px; font-size:0.8rem;">Lihat →</span>
                        </div>
                    </div>
                </a>
                <?php if (is_logged_in()): ?>
                <button onclick="event.stopPropagation(); toggleFavorite(<?= $r['id'] ?>);"
                        data-fav-id="<?= $r['id'] ?>"
                        title="<?= $is_fav ? 'Hapus favorit' : 'Simpan favorit' ?>"
                        style="position:absolute; top:10px; left:10px; background:rgba(255,255,255,0.92); backdrop-filter:blur(4px); border:none; width:32px; height:32px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,0,0,0.18); z-index:5; transition:transform 0.2s ease;"
                        onmouseover="this.style.transform='scale(1.15)'"
                        onmouseout="this.style.transform='scale(1)'">
                    <i class="fa-<?= $is_fav?'solid':'regular' ?> fa-heart" style="color:<?= $is_fav?'#ef4444':'#aaa' ?>; font-size:0.85rem; pointer-events:none;"></i>
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
