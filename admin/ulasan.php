<?php
require_once __DIR__ . '/../includes/admin-header.php';

$reviews     = read_json('reviews.json');
$restaurants = get_restaurants();
$users       = get_users();

// Build quick lookup maps
$rest_map = [];
foreach ($restaurants as $r) $rest_map[$r['id']] = $r;

$user_map = [];
foreach ($users as $u) $user_map[$u['id']] = $u;

// Filter by search
$q = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';
if ($q) {
    $reviews = array_filter($reviews, function($rev) use ($q, $rest_map, $user_map) {
        $rname = strtolower($rest_map[$rev['restaurant_id']]['name'] ?? '');
        $uname = strtolower($user_map[$rev['user_id']]['username'] ?? '');
        $comment = strtolower($rev['comment'] ?? '');
        return str_contains($rname, $q) || str_contains($uname, $q) || str_contains($comment, $q);
    });
}

// Sort newest first
usort($reviews, fn($a,$b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

// Avatar colors
$avatar_colors = ['#4CAF50','#FF9800','#2196F3','#9C27B0','#F44336','#00BCD4'];
?>

<!-- Top bar -->
<div class="adm-list-topbar">
    <div>
        <span class="adm-badge adm-badge-gray" style="font-size:0.82rem; padding:6px 14px;">
            <?= count($reviews) ?> Ulasan Total
        </span>
    </div>
    <div class="adm-list-topbar-right">
        <form action="" method="GET" style="display:flex; gap:8px;">
            <div class="adm-search-wrap" style="width:300px;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" class="adm-form-control" 
                       placeholder="Cari restoran, pengguna, komentar..."
                       value="<?= htmlspecialchars($q) ?>" style="padding-left:36px;">
            </div>
            <button type="submit" class="adm-btn adm-btn-ghost">
                <i class="fa-solid fa-filter"></i> Saring
            </button>
            <?php if ($q): ?>
                <a href="<?= BASE_URL ?>/admin/ulasan.php" class="adm-btn adm-btn-ghost adm-btn-sm">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Table -->
<div class="adm-card">
    <div class="adm-card-header">
        <div class="adm-card-title">
            <i class="fa-solid fa-comments"></i>
            Daftar Ulasan
        </div>
    </div>
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr>
                    <th style="width:40px;">No.</th>
                    <th style="width:70px;">Foto</th>
                    <th>Pengguna</th>
                    <th>Restoran</th>
                    <th>Rating</th>
                    <th>Komentar</th>
                    <th>Tanggal</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                <tr>
                    <td colspan="8">
                        <div class="adm-empty-state">
                            <i class="fa-regular fa-comment-dots"></i>
                            <p>Belum ada ulasan.</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php $idx = 0; foreach($reviews as $rev):
                    $idx++;
                    $user = $user_map[$rev['user_id']] ?? null;
                    $rest = $rest_map[$rev['restaurant_id']] ?? null;
                    $uname = $user ? $user['username'] : 'Anonim';
                    $initial = mb_strtoupper(mb_substr($uname, 0, 1));
                    $color   = $avatar_colors[$rev['user_id'] % count($avatar_colors)];

                    // First media
                    $thumb = null;
                    if (!empty($rev['media'])) {
                        foreach ($rev['media'] as $m) {
                            if ($m['type'] === 'image') { $thumb = $m['path']; break; }
                        }
                    } elseif (!empty($rev['photos'])) {
                        $thumb = $rev['photos'][0];
                    }
                ?>
                <tr style="min-height:80px;">
                    <td style="color:var(--adm-text-muted); font-weight:600;"><?= $idx ?></td>
                    <td>
                        <?php if ($thumb): ?>
                            <img src="<?= htmlspecialchars($thumb) ?>"
                                 style="width:64px; height:64px; object-fit:cover; border-radius:8px; border:1px solid var(--adm-card-border);"
                                 alt="">
                        <?php else: ?>
                            <div style="width:64px; height:64px; background:var(--adm-th-bg); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--adm-text-muted); border:1px solid var(--adm-card-border);">
                                <i class="fa-solid fa-camera"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div class="adm-user-circle" style="background:<?= $color ?>;">
                                <?= htmlspecialchars($initial) ?>
                            </div>
                            <span style="font-weight:600; color:var(--adm-text);">
                                <?= htmlspecialchars($uname) ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <?php if ($rest): ?>
                            <a href="<?= BASE_URL ?>/restaurant-detail.php?id=<?= $rev['restaurant_id'] ?>" 
                               target="_blank" style="font-weight:600; color:var(--adm-text);">
                                <?= htmlspecialchars($rest['name']) ?>
                            </a>
                            <div style="font-size:0.75rem; color:var(--adm-text-muted); margin-top:2px;">
                                <i class="fa-solid fa-location-dot" style="color:var(--adm-orange);"></i>
                                <?= htmlspecialchars($rest['district']) ?>
                            </div>
                        <?php else: ?>
                            <span style="color:var(--adm-text-muted);">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="adm-stars">
                            <?php for($s=1;$s<=5;$s++): ?>
                                <i class="fa-solid fa-star" style="color:<?=$s<=$rev['rating']?'#FBBF24':'#E5E7EB';?>"></i>
                            <?php endfor; ?>
                        </div>
                    </td>
                    <td style="max-width:240px;">
                        <span style="display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; font-size:0.82rem; color:var(--adm-text-2); line-height:1.5;">
                            <?= htmlspecialchars($rev['comment']) ?>
                        </span>
                    </td>
                    <td style="white-space:nowrap; font-size:0.8rem; color:var(--adm-text-muted);">
                        <?= htmlspecialchars(substr($rev['date'] ?? '', 0, 10)) ?>
                    </td>
                    <td>
                        <div style="display:flex; justify-content:flex-end;">
                            <form id="del-rev-<?= $rev['id'] ?>" action="<?= BASE_URL ?>/admin/delete-review.php" method="POST" style="display:none;">
                                <input type="hidden" name="id" value="<?= $rev['id'] ?>">
                            </form>
                            <button type="button" class="adm-btn adm-btn-outline-red adm-btn-sm"
                                    onclick="confirmDelete('del-rev-<?= $rev['id'] ?>', 'Hapus Ulasan', 'Hapus ulasan dari <?= addslashes(htmlspecialchars($uname)) ?>?')">
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
