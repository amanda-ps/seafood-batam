<?php
require_once __DIR__ . '/../includes/admin-header.php';

$restaurants = get_restaurants();
$total = count($restaurants);

// Search filter
$q = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';
if ($q) {
    $restaurants = array_filter($restaurants, fn($r) =>
        str_contains(strtolower($r['name']), $q) ||
        str_contains(strtolower($r['district']), $q)
    );
}

// Sort filter
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'id_desc';

if (!empty($restaurants) && is_array($restaurants)) {
    usort($restaurants, function($a, $b) use ($sort) {
        switch ($sort) {
            case 'rating_asc':
                return ($a['rating'] ?? 0) <=> ($b['rating'] ?? 0) ?: ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
            case 'id_asc':
                return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
            case 'id_desc':
                return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
            case 'district_asc':
                return strcasecmp($a['district'] ?? '', $b['district'] ?? '') ?: ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
            case 'district_desc':
                return strcasecmp($b['district'] ?? '', $a['district'] ?? '') ?: ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
            case 'name_asc':
                return strcasecmp($a['name'] ?? '', $b['name'] ?? '') ?: ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
            case 'name_desc':
                return strcasecmp($b['name'] ?? '', $a['name'] ?? '') ?: ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
            case 'rating_desc':
            default:
                return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0) ?: ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
        }
    });
}

$icon_colors = ['#4CAF50','#FF9800','#2196F3','#9C27B0','#F44336','#00BCD4','#FF5722','#607D8B'];

// Helper logic for table header sort links
function get_sort_link($col, $current_sort, $q) {
    $next_sort = '';
    $icon = '<i class="fa-solid fa-sort" style="color:var(--adm-text-light); opacity:0.4; margin-left:4px;"></i>';
    
    if ($col === 'id') {
        $next_sort = ($current_sort === 'id_asc') ? 'id_desc' : 'id_asc';
        if ($current_sort === 'id_asc') {
            $icon = '<i class="fa-solid fa-sort-up" style="color:var(--adm-green); margin-left:4px;"></i>';
        } elseif ($current_sort === 'id_desc') {
            $icon = '<i class="fa-solid fa-sort-down" style="color:var(--adm-green); margin-left:4px;"></i>';
        }
    } elseif ($col === 'name') {
        $next_sort = ($current_sort === 'name_asc') ? 'name_desc' : 'name_asc';
        if ($current_sort === 'name_asc') {
            $icon = '<i class="fa-solid fa-sort-up" style="color:var(--adm-green); margin-left:4px;"></i>';
        } elseif ($current_sort === 'name_desc') {
            $icon = '<i class="fa-solid fa-sort-down" style="color:var(--adm-green); margin-left:4px;"></i>';
        }
    } elseif ($col === 'district') {
        $next_sort = ($current_sort === 'district_asc') ? 'district_desc' : 'district_asc';
        if ($current_sort === 'district_asc') {
            $icon = '<i class="fa-solid fa-sort-up" style="color:var(--adm-green); margin-left:4px;"></i>';
        } elseif ($current_sort === 'district_desc') {
            $icon = '<i class="fa-solid fa-sort-down" style="color:var(--adm-green); margin-left:4px;"></i>';
        }
    } elseif ($col === 'rating') {
        $next_sort = ($current_sort === 'rating_desc' || $current_sort === '') ? 'rating_asc' : 'rating_desc';
        if ($current_sort === 'rating_desc' || $current_sort === '') {
            $icon = '<i class="fa-solid fa-sort-down" style="color:var(--adm-green); margin-left:4px;"></i>';
        } elseif ($current_sort === 'rating_asc') {
            $icon = '<i class="fa-solid fa-sort-up" style="color:var(--adm-green); margin-left:4px;"></i>';
        }
    }
    
    $url = '?sort=' . urlencode($next_sort);
    if ($q !== '') {
        $url .= '&q=' . urlencode($q);
    }
    return ['url' => $url, 'icon' => $icon];
}

$link_id       = get_sort_link('id', $sort, $q);
$link_name     = get_sort_link('name', $sort, $q);
$link_district = get_sort_link('district', $sort, $q);
$link_rating   = get_sort_link('rating', $sort, $q);
?>

<!-- Top bar -->
<div class="adm-list-topbar">
    <a href="<?= BASE_URL ?>/admin/add-restaurant.php" class="adm-btn adm-btn-primary adm-btn-lg">
        <i class="fa-solid fa-plus"></i> Tambah Restoran
    </a>
    <div class="adm-list-topbar-right">
        <form action="" method="GET" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <div class="adm-search-wrap" style="width:240px;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" class="adm-form-control" 
                       placeholder="Cari restoran..." 
                       value="<?= htmlspecialchars($q) ?>" style="padding-left:36px;">
            </div>
            <select name="sort" class="adm-form-control" style="width:auto; min-width:210px;" onchange="this.form.submit()">
                <option value="id_desc"     <?= ($sort === 'id_desc' || $sort === '') ? 'selected' : '' ?>>ID / Nomor: Terbesar - Terkecil</option>
                <option value="id_asc"      <?= $sort === 'id_asc' ? 'selected' : '' ?>>ID / Nomor: Terkecil - Terbesar</option>
                <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Rating: Tertinggi - Terendah</option>
                <option value="rating_asc"  <?= $sort === 'rating_asc' ? 'selected' : '' ?>>Rating: Terendah - Tertinggi</option>
                <option value="district_asc"  <?= $sort === 'district_asc' ? 'selected' : '' ?>>Distrik: A - Z</option>
                <option value="district_desc" <?= $sort === 'district_desc' ? 'selected' : '' ?>>Distrik: Z - A</option>
                <option value="name_asc"    <?= $sort === 'name_asc' ? 'selected' : '' ?>>Nama Restoran: A - Z</option>
                <option value="name_desc"   <?= $sort === 'name_desc' ? 'selected' : '' ?>>Nama Restoran: Z - A</option>
            </select>
            <button type="submit" class="adm-btn adm-btn-ghost">
                <i class="fa-solid fa-filter"></i> Saring
            </button>
            <?php if ($q || ($sort && $sort !== 'id_desc')): ?>
                <a href="<?= BASE_URL ?>/admin/restoran.php" class="adm-btn adm-btn-ghost adm-btn-sm" title="Reset Filter">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="adm-card">
    <div class="adm-card-header">
        <div class="adm-card-title">
            <i class="fa-solid fa-store"></i>
            Daftar Restoran
            <span class="adm-badge adm-badge-gray"><?= count($restaurants) ?> dari <?= $total ?></span>
        </div>
    </div>
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr>
                    <th style="width:85px;">
                        <a href="<?= $link_id['url'] ?>" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center;" title="Urutkan berdasarkan ID/No.">
                            ID / No. <?= $link_id['icon'] ?>
                        </a>
                    </th>
                    <th style="width:56px;">Foto</th>
                    <th>
                        <a href="<?= $link_name['url'] ?>" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center;" title="Urutkan berdasarkan Nama">
                            Nama Restoran <?= $link_name['icon'] ?>
                        </a>
                    </th>
                    <th>
                        <a href="<?= $link_district['url'] ?>" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center;" title="Urutkan berdasarkan Distrik">
                            Distrik <?= $link_district['icon'] ?>
                        </a>
                    </th>
                    <th>
                        <a href="<?= $link_rating['url'] ?>" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center;" title="Urutkan berdasarkan Rating">
                            Rating <?= $link_rating['icon'] ?>
                        </a>
                    </th>
                    <th>Ulasan</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($restaurants)): ?>
                <tr>
                    <td colspan="7">
                        <div class="adm-empty-state">
                            <i class="fa-solid fa-store-slash"></i>
                            <p>Tidak ada restoran ditemukan.</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php $idx = 0; foreach($restaurants as $r):
                    $color   = $icon_colors[$idx % count($icon_colors)];
                    $initial = mb_strtoupper(mb_substr($r['name'], 0, 2));
                    $status  = $r['status'] ?? 'active';
                    $idx++;
                ?>
                <tr>
                    <td style="color:var(--adm-text-muted); font-weight:600;"><?= htmlspecialchars($r['id']) ?></td>
                    <td>
                        <?php $img_src = !empty($r['foto_utama']) ? $r['foto_utama'] : ($r['photos'][0] ?? ''); ?>
                        <?php if (!empty($img_src)): ?>
                            <img src="<?= htmlspecialchars($img_src) ?>" class="adm-res-icon" alt=""
                                 referrerpolicy="no-referrer">
                        <?php else: ?>
                            <div class="adm-res-icon-placeholder" style="background:<?= $color ?>; color:#fff; font-size:0.75rem; font-weight:700;">
                                <?= htmlspecialchars($initial) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-weight:600; color:var(--adm-text);">
                            <?= htmlspecialchars($r['name']) ?>
                        </span>
                    </td>
                    <td style="color:var(--adm-text-muted);">
                        <i class="fa-solid fa-location-dot" style="color:var(--adm-orange);margin-right:4px;"></i>
                        <?= htmlspecialchars($r['district']) ?>
                    </td>
                    <td>
                        <div class="adm-stars">
                            <?php for($s=1;$s<=5;$s++): ?>
                                <i class="fa-solid fa-star" style="color:<?=$s<=$r['rating']?'#FBBF24':'#E5E7EB';?>"></i>
                            <?php endfor; ?>
                            <span class="adm-stars-num"><?= $r['rating'] ?></span>
                        </div>
                    </td>
                    <td style="font-weight:600;"><?= number_format($r['reviews_count']) ?></td>

                    <td>
                        <div style="display:flex; gap:6px; justify-content:flex-end;">
                            <a href="<?= BASE_URL ?>/admin/edit-restaurant.php?id=<?= $r['id'] ?>" 
                               class="adm-btn adm-btn-outline-blue adm-btn-sm">
                                <i class="fa-solid fa-pen"></i> Edit
                            </a>
                            <!-- Hidden delete form -->
                            <form id="del-form-<?= $r['id'] ?>" action="<?= BASE_URL ?>/admin/delete-restaurant.php" method="POST" style="display:none;">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            </form>
                            <button type="button" class="adm-btn adm-btn-outline-red adm-btn-sm"
                                    onclick="confirmDelete('del-form-<?= $r['id'] ?>', 'Hapus Restoran', 'Hapus \'<?= addslashes(htmlspecialchars($r['name'])) ?>\'? Data ulasan tidak akan terhapus.')">
                                <i class="fa-solid fa-trash"></i> Hapus
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
