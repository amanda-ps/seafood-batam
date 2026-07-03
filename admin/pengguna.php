<?php
require_once __DIR__ . '/../includes/admin-header.php';

$users = get_users();

// Search and filter
$q      = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';
$filter = $_GET['status'] ?? '';

$display_users = array_filter($users, function($u) use ($q, $filter) {
    if ($u['role'] === 'admin') return false; // don't show admin accounts
    $match = true;
    if ($q) {
        $in_name  = str_contains(strtolower($u['username'] ?? ''), $q);
        $in_email = str_contains(strtolower($u['email'] ?? ''), $q);
        $in_phone = str_contains($u['whatsapp'] ?? '', $q);
        if (!$in_name && !$in_email && !$in_phone) $match = false;
    }
    if ($filter && ($u['status'] ?? 'active') !== $filter) $match = false;
    return $match;
});

// Color palette for initials
$avatar_colors = ['#4CAF50','#FF9800','#2196F3','#9C27B0','#F44336','#00BCD4','#FF5722','#607D8B',
                   '#E91E63','#3F51B5','#009688','#795548'];
?>

<!-- Top bar -->
<div class="adm-list-topbar">
    <div>
        <span class="adm-badge adm-badge-gray" style="font-size:0.82rem; padding:6px 14px;">
            <?= count($display_users) ?> Pengguna
        </span>
    </div>
    <div class="adm-list-topbar-right">
        <form action="" method="GET" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <div class="adm-search-wrap" style="width:280px;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" class="adm-form-control"
                       placeholder="Cari nama, email, atau HP..."
                       value="<?= htmlspecialchars($q) ?>" style="padding-left:36px;">
            </div>
            <select name="status" class="adm-form-control" style="width:160px;">
                <option value="">Semua Status</option>
                <option value="active"   <?= $filter==='active'  ?'selected':'' ?>>Aktif</option>
                <option value="inactive" <?= $filter==='inactive'?'selected':'' ?>>Nonaktif</option>
            </select>
            <button type="submit" class="adm-btn adm-btn-ghost">
                <i class="fa-solid fa-filter"></i> Saring
            </button>
            <?php if ($q || $filter): ?>
                <a href="<?= BASE_URL ?>/admin/pengguna.php" class="adm-btn adm-btn-ghost adm-btn-sm">
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
            <i class="fa-solid fa-users"></i> Daftar Pengguna
        </div>
    </div>
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr>
                    <th style="width:40px;">No.</th>
                    <th style="width:50px;">Foto</th>
                    <th>Nama Pengguna</th>
                    <th>No. HP</th>
                    <th>Email</th>
                    <th>Gender</th>
                    <th>Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($display_users)): ?>
                <tr>
                    <td colspan="8">
                        <div class="adm-empty-state">
                            <i class="fa-solid fa-users-slash"></i>
                            <p>Tidak ada pengguna ditemukan.</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php $idx = 0; foreach($display_users as $u):
                    $idx++;
                    $status  = $u['status'] ?? 'active';
                    $initial = mb_strtoupper(mb_substr($u['username'] ?? 'U', 0, 1));
                    $color   = $avatar_colors[$u['id'] % count($avatar_colors)];
                    $gender  = $u['gender'] ?? '';
                ?>
                <tr>
                    <td style="color:var(--adm-text-muted); font-weight:600;"><?= $idx ?></td>
                    <td>
                        <?php if (!empty($u['avatar'])): ?>
                            <img src="<?= htmlspecialchars($u['avatar']) ?>" 
                                 style="width:38px; height:38px; border-radius:50%; object-fit:cover;">
                        <?php else: ?>
                            <div class="adm-user-circle" style="background:<?= $color ?>;">
                                <?= htmlspecialchars($initial) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-weight:700; color:var(--adm-text);">
                            <?= htmlspecialchars($u['username'] ?? '—') ?>
                        </span>
                        <?php if (!empty($u['display_name'])): ?>
                            <div style="font-size:0.75rem; color:var(--adm-text-muted);">
                                <?= htmlspecialchars($u['display_name']) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--adm-text-2);">
                        <?php if (!empty($u['whatsapp'])): ?>
                            <i class="fa-solid fa-phone" style="color:var(--adm-green); margin-right:5px;"></i>
                            <?= htmlspecialchars($u['whatsapp']) ?>
                        <?php else: ?>
                            <span style="color:var(--adm-text-light);">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--adm-text-2);">
                        <i class="fa-solid fa-at" style="color:var(--adm-text-muted); margin-right:4px;"></i>
                        <?= htmlspecialchars($u['email']) ?>
                    </td>
                    <td>
                        <?php if ($gender === 'male'): ?>
                            <span class="adm-badge adm-badge-blue">♂ Laki-laki</span>
                        <?php elseif ($gender === 'female'): ?>
                            <span class="adm-badge adm-badge-orange">♀ Perempuan</span>
                        <?php else: ?>
                            <span class="adm-badge adm-badge-gray">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($status === 'active'): ?>
                            <span class="adm-badge adm-badge-green">
                                <i class="fa-solid fa-circle" style="font-size:0.5rem;"></i> Aktif
                            </span>
                        <?php else: ?>
                            <span class="adm-badge adm-badge-red">
                                <i class="fa-solid fa-circle" style="font-size:0.5rem;"></i> Nonaktif
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex; gap:6px; justify-content:flex-end; flex-wrap:wrap;">
                            <?php if ($status === 'active'): ?>
                                <button type="button" class="adm-btn adm-btn-outline-orange adm-btn-sm"
                                        onclick="toggleUserStatus(<?= $u['id'] ?>, 'inactive')">
                                    <i class="fa-solid fa-ban"></i> Nonaktifkan
                                </button>
                            <?php else: ?>
                                <button type="button" class="adm-btn adm-btn-outline-green adm-btn-sm"
                                        onclick="toggleUserStatus(<?= $u['id'] ?>, 'active')">
                                    <i class="fa-solid fa-check"></i> Aktifkan
                                </button>
                            <?php endif; ?>
                            <form id="del-user-<?= $u['id'] ?>" action="<?= BASE_URL ?>/admin/delete-user.php" method="POST" style="display:none;">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            </form>
                            <button type="button" class="adm-btn adm-btn-outline-red adm-btn-sm"
                                    onclick="confirmDelete('del-user-<?= $u['id'] ?>', 'Hapus Pengguna', 'Hapus akun \'<?= addslashes(htmlspecialchars($u['username'])) ?>\'?')">
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
