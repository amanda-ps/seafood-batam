<?php
require_once __DIR__ . '/includes/header.php';

$restaurants = get_restaurants();
if (!empty($restaurants) && is_array($restaurants)) {
    usort($restaurants, function($a, $b) {
        return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
    });
}
$top_4 = array_slice($restaurants, 0, 4);
$total_restaurants = count($restaurants);
$all_districts = array_filter(array_unique(array_column($restaurants, 'district')));
sort($all_districts);
$all_reviews = read_json('reviews.json');

// User favorites for card icons
$user_fav_ids = get_user_fav_ids();
?>

<!-- ═══ HERO ═══════════════════════════════════════════════ -->
<section class="hero" style="height:520px; background-image:url('https://islandsunindonesia.com/wp-content/uploads/2024/03/Desain-tanpa-judul-24.jpg'); background-size:cover; background-position:center; position:relative; display:flex; align-items:center; justify-content:center; text-align:center;">
    <div style="position:absolute; inset:0; background:rgba(0, 0, 0, 0.13);"></div>
    <div style="position:relative; z-index:10; padding:0 20px; width:100%;">
        <h1 style="color:#FFFFFF; font-size:clamp(2rem,5vw,3rem); font-weight:800; margin-bottom:16px; text-shadow:0 2px 10px rgba(0,0,0,0.3); letter-spacing:-0.025em; line-height:1.1;">
            Temukan Seafood Terbaik Batam!
        </h1>
        <p style="color:rgba(255,255,255,0.88); font-size:1rem; margin-bottom:32px; max-width:560px; margin-left:auto; margin-right:auto; line-height:1.65;">
            Direktori restoran seafood terlengkap di Batam — temukan kepiting, udang, ikan bakar, dan sajian laut terbaik di satu tempat.
        </p>
        <!-- Search bar pill -->
        <form action="<?= BASE_URL ?>/restaurants.php" method="GET" 
              style="display:flex; max-width:700px; margin:0 auto; background:#FFFFFF; border-radius:50px; overflow:visible; box-shadow:0 8px 32px rgba(0,0,0,0.20); padding:5px 5px 5px 22px; align-items:center; gap:0;">
            <i class="fa-solid fa-magnifying-glass" style="color:#9CA3AF; font-size:0.9rem; flex-shrink:0; margin-right:10px;"></i>
            <input type="text" name="q" placeholder="Cari restoran, masakan..." 
                   style="flex:1; border:none; outline:none; font-family:'Poppins',sans-serif; font-size:0.9rem; color:#111; background:transparent; padding:7px 0; min-width:0;">
            
            <!-- Divider -->
            <div style="width:1px; height:26px; background:#E5E7EB; flex-shrink:0; margin:0 10px;"></div>

            <!-- District select -->
            <select name="district"
                    style="appearance:none; -webkit-appearance:none; border:none; outline:none; background:transparent; color:#374151; font-family:'Poppins',sans-serif; font-size:0.85rem; font-weight:500; padding:7px 28px 7px 8px; cursor:pointer; background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E\"); background-repeat:no-repeat; background-position:right 8px center; min-width:150px;">
                <option value="">Semua Kecamatan</option>
                <?php foreach($all_districts as $d): ?>
                    <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary" 
                    style="border-radius:50px; padding:10px 26px; font-size:0.88rem; flex-shrink:0; margin-left:6px;">
                Cari
            </button>
        </form>
    </div>
</section>

<!-- ═══ STATS BAR ════════════════════════════════════════════ -->
<div style="background:#2D6A3F; padding:24px 0;">
    <div class="container" style="display:flex; justify-content:center; align-items:center; gap:0; flex-wrap:wrap;">
        <?php
        $stats = [
            ['icon'=>'fa-utensils', 'num'=>'80+',   'label'=>'Restoran'],
            ['icon'=>'fa-location-dot','num'=>'12',   'label'=>'Kecamatan'],
            ['icon'=>'fa-comments', 'num'=>'100+',   'label'=>'Ulasan Terpercaya'],
        ];
        foreach($stats as $i => $s):
        ?>
        <?php if ($i > 0): ?>
            <div style="width:1px; height:36px; background:rgba(255,255,255,0.25); margin:0 32px; flex-shrink:0;"></div>
        <?php endif; ?>
        <div style="text-align:center; flex-shrink:0;">
            <i class="fa-solid <?= $s['icon'] ?>" style="color:#E07B2A; font-size:1.1rem; margin-bottom:4px; display:block;"></i>
            <div style="font-size:1.75rem; font-weight:800; color:#FFFFFF; font-family:'Poppins',sans-serif; letter-spacing:-0.03em; line-height:1;"><?= $s['num'] ?></div>
            <div style="font-size:0.78rem; color:rgba(255,255,255,0.72); font-family:'Poppins',sans-serif; margin-top:2px;"><?= $s['label'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ═══ BEST RECOMMENDATIONS ════════════════════════════════ -->
<section class="section container">
    <div class="section-head" style="margin-bottom:36px;">
        <div>
            <h2 style="margin-bottom:6px;">Rekomendasi Terbaik</h2>
            <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Restoran seafood pilihan dengan rating tertinggi dari pengunjung setia</p>
        </div>
        <a href="<?= BASE_URL ?>/restaurants.php" class="btn btn-outline">
            Lihat Semua Restoran <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>

    <div class="grid" style="grid-template-columns:repeat(4,1fr); gap:20px;">
        <?php foreach($top_4 as $r):
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
</section>

<!-- ═══ WHY SEAFOOD BATAM ═══════════════════════════════════ -->
<section class="section section-alt">
    <div class="container" style="text-align:center;">
        <h2 style="margin-bottom:10px;">Mengapa Seafood Batam?</h2>
        <p style="color:var(--text-muted); max-width:520px; margin:0 auto 48px; font-size:0.95rem;">Platform terlengkap untuk menemukan dan memesan seafood terbaik di Kota Batam</p>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:24px;">
            <?php
            $features = [
                ['icon'=>'fa-star',           'color'=>'#E07B2A', 'bg'=>'rgba(224,123,42,0.10)', 'title'=>'Ulasan Terpercaya',   'desc'=>'Ribuan ulasan jujur dari pengunjung nyata dengan rating dan foto autentik.'],
                ['icon'=>'fa-location-dot',   'color'=>'#2D6A3F', 'bg'=>'rgba(45,106,63,0.10)',  'title'=>'Pilihan Lokal Terbaik','desc'=>'Kami menampilkan hanya restoran terpilih dari seluruh kecamatan di Batam.'],
                ['icon'=>'fa-magnifying-glass','color'=>'#3B82F6', 'bg'=>'rgba(59,130,246,0.10)', 'title'=>'Pencarian Mudah',     'desc'=>'Cari berdasarkan nama, lokasi, atau jenis masakan dalam hitungan detik.'],
            ];
            foreach($features as $f):
            ?>
            <div style="background:var(--bg-card); border-radius:var(--radius-md); padding:32px 24px; box-shadow:var(--shadow-sm); border:1px solid var(--border); transition:var(--transition-slow);"
                 onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='var(--shadow-md)'"
                 onmouseout="this.style.transform=''; this.style.boxShadow='var(--shadow-sm)'">
                <div style="width:56px; height:56px; border-radius:16px; background:<?= $f['bg'] ?>; color:<?= $f['color'] ?>; display:flex; align-items:center; justify-content:center; font-size:1.3rem; margin:0 auto 18px;">
                    <i class="fa-solid <?= $f['icon'] ?>"></i>
                </div>
                <h3 style="font-size:1rem; margin-bottom:10px; color:var(--heading-color);"><?= $f['title'] ?></h3>
                <p style="font-size:0.85rem; color:var(--text-muted); line-height:1.65; margin:0;"><?= $f['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══ CTA BANNER ═══════════════════════════════════════════ -->
<section style="background:#2D6A3F; padding:64px 0; text-align:center;">
    <div class="container">
        <h2 style="color:#FFFFFF; font-size:clamp(1.5rem,3.5vw,2.2rem); margin-bottom:14px; letter-spacing:-0.025em;">
            Siap Jelajahi Kuliner Batam?
        </h2>
        <p style="color:rgba(255,255,255,0.80); margin-bottom:32px; font-size:0.95rem; max-width:480px; margin-left:auto; margin-right:auto;">
            Temukan lebih dari 80 restoran seafood terbaik dengan ulasan terpercaya, menu lengkap, dan reservasi mudah.
        </p>
        <a href="<?= BASE_URL ?>/restaurants.php" class="btn btn-accent btn-lg" style="font-size:1rem; padding:14px 36px;">
            Jelajahi Sekarang <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
