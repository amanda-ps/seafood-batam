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

// Sort by rating desc
usort($restaurants, fn($a,$b) => $b['rating'] <=> $a['rating']);

$icon_colors = ['#4CAF50','#FF9800','#2196F3','#9C27B0','#F44336','#00BCD4','#FF5722','#607D8B'];
?>

<!-- Top bar -->
<div class="adm-list-topbar">
    <a href="<?= BASE_URL ?>/admin/add-restaurant.php" class="adm-btn adm-btn-primary adm-btn-lg">
        <i class="fa-solid fa-plus"></i> Tambah Restoran
    </a>
    <div class="adm-list-topbar-right">
        <form action="" method="GET" style="display:flex; align-items:center; gap:8px;">
            <div class="adm-search-wrap" style="width:280px;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" class="adm-form-control" 
                       placeholder="Cari restoran..." 
                       value="<?= htmlspecialchars($q) ?>" style="padding-left:36px;">
            </div>
            <button type="submit" class="adm-btn adm-btn-ghost">
                <i class="fa-solid fa-filter"></i> Saring
            </button>
            <?php if ($q): ?>
                <a href="<?= BASE_URL ?>/admin/restoran.php" class="adm-btn adm-btn-ghost adm-btn-sm">
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
                    <th style="width:40px;">No.</th>
                    <th style="width:56px;">Foto</th>
                    <th>Nama Restoran</th>
                    <th>Distrik</th>
                    <th>Rating</th>
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
                    <td style="color:var(--adm-text-muted); font-weight:600;"><?= $idx ?></td>
                    <td>
                        <?php if (!empty($r['photos'][0])): ?>
                            <img src="<?= htmlspecialchars($r['photos'][0]) ?>" class="adm-res-icon" alt="">
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
