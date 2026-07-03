<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $restaurants = get_restaurants();

    $parsed_menus = [];
    if (isset($_POST['menu_titles']) && is_array($_POST['menu_titles'])) {
        for ($i = 0; $i < count($_POST['menu_titles']); $i++) {
            if (!empty(trim($_POST['menu_titles'][$i]))) {
                $parsed_menus[] = [
                    'name'        => sanitize($_POST['menu_titles'][$i]),
                    'price'       => sanitize($_POST['menu_prices'][$i] ?? ''),
                    'description' => sanitize($_POST['menu_descs'][$i] ?? ''),
                ];
            }
        }
    }

    // Handle photo URLs
    $photo_urls = [];
    if (isset($_POST['photo_urls']) && is_array($_POST['photo_urls'])) {
        foreach ($_POST['photo_urls'] as $url) {
            $url = trim($url);
            if (!empty($url)) $photo_urls[] = $url;
        }
    }

    foreach ($restaurants as &$r) {
        if ($r['id'] === $id) {
            $r['name']        = sanitize($_POST['name']);
            $r['description'] = sanitize($_POST['description']);
            $r['district']    = sanitize($_POST['district']);
            $r['address']     = sanitize($_POST['address']);
            $r['hours']       = sanitize($_POST['hours']);
            $r['maps_link']   = sanitize($_POST['maps_link'] ?? '');
            $r['whatsapp']    = sanitize($_POST['whatsapp'] ?? '');
            $r['status']      = sanitize($_POST['status'] ?? 'active');
            $r['menus']       = $parsed_menus;
            if (!empty($photo_urls)) $r['photos'] = $photo_urls;
            break;
        }
    }

    write_json('restaurants.json', $restaurants);
    $_SESSION['success'] = 'Restoran berhasil diperbarui!';
    redirect('/admin/restoran.php');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$restaurant = get_restaurant($id);
if (!$restaurant) redirect('/admin/restoran.php');

require_once __DIR__ . '/../includes/admin-header.php';
global $districts;
?>

<!-- Back link -->
<div style="margin-bottom:20px;">
    <a href="<?= BASE_URL ?>/admin/restoran.php" style="display:inline-flex; align-items:center; gap:7px; font-size:0.85rem; font-weight:600; color:var(--adm-text-muted); font-family:'Poppins',sans-serif; transition:color 0.2s;" onmouseover="this.style.color='var(--adm-green)'" onmouseout="this.style.color='var(--adm-text-muted)'">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar Restoran
    </a>
</div>

<form method="POST" action="">
    <input type="hidden" name="id" value="<?= $id ?>">

    <!-- Info Card -->
    <div class="adm-card" style="margin-bottom:20px;">
        <div class="adm-card-header">
            <div class="adm-card-title">
                <i class="fa-solid fa-pen-to-square"></i>
                Edit Restoran — <?= htmlspecialchars($restaurant['name']) ?>
            </div>
            <span class="adm-badge adm-badge-blue">ID: <?= $id ?></span>
        </div>
        <div class="adm-card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div class="adm-form-group">
                    <label class="adm-form-label">Nama Restoran *</label>
                    <input type="text" name="name" class="adm-form-control" 
                           value="<?= htmlspecialchars($restaurant['name']) ?>" required>
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">Distrik / Lokasi *</label>
                    <select name="district" class="adm-form-control" required>
                        <?php foreach($districts as $d): ?>
                            <option value="<?= $d ?>" <?= $restaurant['district']===$d?'selected':'' ?>>
                                <?= $d ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="adm-form-group" style="grid-column:1/-1;">
                    <label class="adm-form-label">Alamat Lengkap *</label>
                    <textarea name="address" class="adm-form-control" rows="2" required><?= htmlspecialchars($restaurant['address']) ?></textarea>
                </div>
                <div class="adm-form-group" style="grid-column:1/-1;">
                    <label class="adm-form-label">Deskripsi *</label>
                    <textarea name="description" class="adm-form-control" rows="4" required><?= htmlspecialchars($restaurant['description']) ?></textarea>
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">Jam Buka</label>
                    <input type="text" name="hours" class="adm-form-control" 
                           value="<?= htmlspecialchars($restaurant['hours'] ?? '') ?>"
                           placeholder="Cth: 10:00 - 22:00">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">Status</label>
                    <select name="status" class="adm-form-control">
                        <option value="active"   <?= ($restaurant['status']??'active')==='active'   ?'selected':'' ?>>Aktif</option>
                        <option value="inactive" <?= ($restaurant['status']??'active')==='inactive' ?'selected':'' ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">No. WhatsApp</label>
                    <input type="text" name="whatsapp" class="adm-form-control"
                           value="<?= htmlspecialchars($restaurant['whatsapp'] ?? '') ?>"
                           placeholder="+628123456789">
                </div>
                <div class="adm-form-group" style="grid-column:1/-1;">
                    <label class="adm-form-label">Link Google Maps</label>
                    <input type="url" name="maps_link" class="adm-form-control"
                           value="<?= htmlspecialchars($restaurant['maps_link'] ?? '') ?>"
                           placeholder="https://maps.google.com/...">
                </div>
            </div>
        </div>
    </div>

    <!-- Photo URLs Card -->
    <div class="adm-card" style="margin-bottom:20px;">
        <div class="adm-card-header">
            <div class="adm-card-title">
                <i class="fa-solid fa-images"></i>
                Foto Restoran
            </div>
            <span style="font-size:0.78rem; color:var(--adm-text-muted); font-family:'Poppins',sans-serif;">Maks. 5 URL foto</span>
        </div>
        <div class="adm-card-body">
            <div style="display:flex; flex-direction:column; gap:10px;" id="photo-urls-container">
                <?php
                $photos = $restaurant['photos'] ?? [''];
                for ($i = 0; $i < 5; $i++):
                    $val = $photos[$i] ?? '';
                ?>
                <div class="adm-form-group" style="margin:0; display:flex; gap:10px; align-items:center;">
                    <span style="min-width:20px; color:var(--adm-text-muted); font-family:'Poppins',sans-serif; font-size:0.82rem; font-weight:600;"><?= $i+1 ?>.</span>
                    <input type="url" name="photo_urls[]" class="adm-form-control"
                           placeholder="https://images.unsplash.com/..."
                           value="<?= htmlspecialchars($val) ?>">
                    <?php if ($val): ?>
                        <img src="<?= htmlspecialchars($val) ?>" 
                             style="width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid var(--adm-card-border); flex-shrink:0;"
                             onerror="this.style.display='none'">
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Menu Card -->
    <div class="adm-card" style="margin-bottom:80px;">
        <div class="adm-card-header">
            <div class="adm-card-title">
                <i class="fa-solid fa-utensils"></i>
                Menu & Harga
            </div>
        </div>
        <div class="adm-card-body">
            <div id="adm-menus-container">
                <?php if (!empty($restaurant['menus'])): ?>
                    <?php foreach($restaurant['menus'] as $m): ?>
                    <div class="adm-menu-row">
                        <div class="adm-form-group">
                            <label class="adm-form-label">Nama Menu</label>
                            <input type="text" name="menu_titles[]" class="adm-form-control"
                                   value="<?= htmlspecialchars($m['name']) ?>">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Harga</label>
                            <input type="text" name="menu_prices[]" class="adm-form-control"
                                   value="<?= htmlspecialchars($m['price']) ?>" placeholder="Rp 0">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-form-label">Deskripsi</label>
                            <input type="text" name="menu_descs[]" class="adm-form-control"
                                   value="<?= htmlspecialchars($m['description'] ?? '') ?>">
                        </div>
                        <button type="button" class="adm-menu-remove-btn adm-btn adm-btn-outline-red adm-btn-sm" style="align-self:flex-end;">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="adm-menu-add-row" id="adm-add-menu-btn">
                <i class="fa-solid fa-plus"></i> Tambah Item Menu
            </button>
        </div>
    </div>

    <!-- Sticky Footer -->
    <div class="adm-form-footer">
        <a href="<?= BASE_URL ?>/admin/restoran.php" class="adm-btn adm-btn-ghost adm-btn-lg">Batal</a>
        <button type="submit" class="adm-btn adm-btn-primary adm-btn-lg">
            <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
