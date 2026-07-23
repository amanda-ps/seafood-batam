<?php
require_once __DIR__ . '/../includes/admin-header.php';
catat_kunjungan();

$restaurants    = get_restaurants();
$users          = get_users();
$reviews        = get_reviews();
$visit_stats    = get_realtime_visit_stats();

$total_restaurants = count($restaurants);
$total_users       = count(array_filter($users, fn($u) => $u['role'] !== 'admin' && ($u['status'] ?? 'active') === 'active'));
$total_reviews     = count($reviews);

// Sort by rating for top table
usort($restaurants, fn($a,$b) => $b['rating'] <=> $a['rating']);
$top_restaurants = array_slice($restaurants, 0, 8);

// Color palette for restaurant icon squares
$icon_colors = ['#4CAF50','#FF9800','#2196F3','#9C27B0','#F44336','#00BCD4','#FF5722','#607D8B'];
?>

<!-- Welcome Banner -->
<div class="adm-welcome">
    <div>
        <div class="adm-welcome-title">Selamat Datang, Administrator! 👋</div>
        <div class="adm-welcome-sub">Berikut ringkasan aktivitas platform hari ini — <?= date('d F Y') ?></div>
    </div>
</div>

<!-- Stat Cards -->
<div class="adm-stats-grid">
    <div class="adm-stat-card">
        <div class="adm-stat-icon adm-stat-icon-green">
            <i class="fa-solid fa-store"></i>
        </div>
        <div>
            <span class="adm-stat-num"><?= $total_restaurants ?></span>
            <span class="adm-stat-label">Total Restoran</span>
            <div>
                <span class="adm-stat-pill adm-stat-pill-green">
                    <i class="fa-solid fa-arrow-up"></i> +5 minggu ini
                </span>
            </div>
        </div>
    </div>

    <div class="adm-stat-card">
        <div class="adm-stat-icon adm-stat-icon-blue">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <span class="adm-stat-num"><?= $total_users ?></span>
            <span class="adm-stat-label">Pengguna Aktif</span>
            <div>
                <span class="adm-stat-pill adm-stat-pill-blue">
                    <i class="fa-solid fa-arrow-up"></i> +12 minggu ini
                </span>
            </div>
        </div>
    </div>

    <div class="adm-stat-card">
        <div class="adm-stat-icon adm-stat-icon-orange">
            <i class="fa-solid fa-comments"></i>
        </div>
        <div>
            <span class="adm-stat-num"><?= number_format($total_reviews) ?></span>
            <span class="adm-stat-label">Total Ulasan</span>
            <div>
                <span class="adm-stat-pill adm-stat-pill-orange">
                    <i class="fa-solid fa-arrow-up"></i> +48 minggu ini
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Visit Chart -->
<div class="adm-card" style="margin-bottom:24px;">
    <div class="adm-card-header">
        <div class="adm-card-title">
            <i class="fa-solid fa-chart-line"></i>
            Statistik Kunjungan
        </div>
        <div class="adm-chart-tabs">
            <button class="adm-chart-tab active" data-period="weekly"
                    onclick="switchChartPeriod('weekly')">Mingguan</button>
            <button class="adm-chart-tab" data-period="monthly"
                    onclick="switchChartPeriod('monthly')">Bulanan</button>
            <button class="adm-chart-tab" data-period="yearly"
                    onclick="switchChartPeriod('yearly')">Tahunan</button>
        </div>
    </div>
    <div class="adm-card-body">
        <div style="height:280px; position:relative;">
            <canvas id="visit-chart"></canvas>
        </div>
    </div>
</div>

<!-- Top Rated Restaurants -->
<div class="adm-card">
    <div class="adm-card-header">
        <div class="adm-card-title">
            <i class="fa-solid fa-trophy"></i>
            Restoran Rating Tertinggi
        </div>
        <a href="<?= BASE_URL ?>/admin/restoran.php" class="adm-btn adm-btn-ghost adm-btn-sm">
            Lihat Semua <i class="fa-solid fa-arrow-right"></i>
        </a>
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
                    <th>Total Ulasan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($top_restaurants as $i => $r):
                    $color   = $icon_colors[$i % count($icon_colors)];
                    $initial = mb_strtoupper(mb_substr($r['name'], 0, 2));
                ?>
                <tr>
                    <td style="color:var(--adm-text-muted); font-weight:600;"><?= $i + 1 ?></td>
                    <td>
                        <?php if (!empty($r['photos'][0])): ?>
                            <img src="<?= htmlspecialchars($r['photos'][0]) ?>" 
                                 class="adm-res-icon" alt="">
                        <?php else: ?>
                            <div class="adm-res-icon-placeholder" style="background:<?= $color ?>; color:#fff; font-size:0.75rem; font-weight:700;">
                                <?= htmlspecialchars($initial) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>/restaurant-detail.php?id=<?= $r['id'] ?>" 
                           style="font-weight:600; color:var(--adm-text);" 
                           target="_blank" title="Lihat detail">
                            <?= htmlspecialchars($r['name']) ?>
                        </a>
                    </td>
                    <td style="color:var(--adm-text-muted);">
                        <i class="fa-solid fa-location-dot" style="color:var(--adm-orange); margin-right:4px;"></i>
                        <?= htmlspecialchars($r['district']) ?>
                    </td>
                    <td>
                        <div class="adm-stars">
                            <?php for($s=1;$s<=5;$s++): ?>
                                <i class="fa-solid fa-star" style="color:<?= $s<=$r['rating']?'#FBBF24':'#E5E7EB'; ?>"></i>
                            <?php endfor; ?>
                            <span class="adm-stars-num"><?= $r['rating'] ?></span>
                        </div>
                    </td>
                    <td style="font-weight:600; color:var(--adm-text);"><?= number_format($r['reviews_count']) ?></td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
</div>

<script>
window.realtimeChartData = <?= json_encode($visit_stats) ?>;
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
