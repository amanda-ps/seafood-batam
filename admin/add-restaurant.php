<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    $photo_urls = [];
    if (isset($_POST['photo_urls']) && is_array($_POST['photo_urls'])) {
        foreach ($_POST['photo_urls'] as $url) {
            $url = trim($url);
            if (!empty($url)) $photo_urls[] = $url;
        }
    }
    if (empty($photo_urls)) {
        $photo_urls = ['https://images.unsplash.com/photo-1565557623262-b51c2513a641'];
    }

    $new_id = empty($restaurants) ? 1 : max(array_column($restaurants, 'id')) + 1;
    $restaurants[] = [
        'id'            => $new_id,
        'name'          => sanitize($_POST['name']),
        'description'   => sanitize($_POST['description']),
        'district'      => sanitize($_POST['district']),
        'address'       => sanitize($_POST['address']),
        'hours'         => sanitize($_POST['hours'] ?? ''),
        'maps_link'     => sanitize($_POST['maps_link'] ?? ''),
        'whatsapp'      => sanitize($_POST['whatsapp'] ?? ''),
        'status'        => 'active',
        'rating'        => 0.0,
        'reviews_count' => 0,
        'photos'        => $photo_urls,
        'menus'         => $parsed_menus,
    ];

    write_json('restaurants.json', $restaurants);
    $_SESSION['success'] = 'Restoran baru berhasil ditambahkan!';
    redirect('/admin/restoran.php');
}

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

    <!-- Info Card -->
    <div class="adm-card" style="margin-bottom:20px;">
        <div class="adm-card-header">
            <div class="adm-card-title">
                <i class="fa-solid fa-store"></i>
                Tambah Restoran Baru
            </div>
        </div>
        <div class="adm-card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div class="adm-form-group">
                    <label class="adm-form-label">Nama Restoran *</label>
                    <input type="text" name="name" class="adm-form-control" required placeholder="Nama restoran">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">Distrik / Lokasi *</label>
                    <select name="district" class="adm-form-control" required>
                        <option value="">Pilih Distrik</option>
                        <?php foreach($districts as $d): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="adm-form-group" style="grid-column:1/-1;">
                    <label class="adm-form-label">Alamat Lengkap *</label>
                    <textarea name="address" class="adm-form-control" rows="2" required placeholder="Alamat lengkap restoran"></textarea>
                </div>
                <div class="adm-form-group" style="grid-column:1/-1;">
                    <label class="adm-form-label">Deskripsi *</label>
                    <textarea name="description" class="adm-form-control" rows="4" required placeholder="Deskripsi singkat tentang restoran..."></textarea>
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">Jam Buka</label>
                    <input type="text" name="hours" class="adm-form-control" placeholder="Cth: 10:00 - 22:00">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">No. WhatsApp</label>
                    <input type="text" name="whatsapp" class="adm-form-control" placeholder="+628123456789">
                </div>
                <div class="adm-form-group" style="grid-column:1/-1;">
                    <label class="adm-form-label">Link Google Maps</label>
                    <input type="url" name="maps_link" class="adm-form-control" placeholder="https://maps.google.com/...">
                </div>
            </div>
        </div>
    </div>

    <!-- Photo URLs -->
    <div class="adm-card" style="margin-bottom:20px;">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-images"></i> Foto Restoran</div>
            <span style="font-size:0.78rem; color:var(--adm-text-muted); font-family:'Poppins',sans-serif;">Maks. 5 URL foto</span>
        </div>
        <div class="adm-card-body">
            <div style="display:flex; flex-direction:column; gap:10px;">
                <?php for($i=0;$i<5;$i++): ?>
                <div class="adm-form-group" style="margin:0; display:flex; align-items:center; gap:10px;">
                    <span style="min-width:20px; color:var(--adm-text-muted); font-size:0.82rem; font-weight:600; font-family:'Poppins',sans-serif;"><?= $i+1 ?>.</span>
                    <input type="url" name="photo_urls[]" class="adm-form-control"
                           placeholder="https://images.unsplash.com/...">
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Menu -->
    <div class="adm-card" style="margin-bottom:80px;">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-utensils"></i> Menu & Harga</div>
        </div>
        <div class="adm-card-body">
            <div id="adm-menus-container"></div>
            <button type="button" class="adm-menu-add-row" id="adm-add-menu-btn">
                <i class="fa-solid fa-plus"></i> Tambah Item Menu
            </button>
        </div>
    </div>

    <!-- Sticky Footer -->
    <div class="adm-form-footer">
        <a href="<?= BASE_URL ?>/admin/restoran.php" class="adm-btn adm-btn-ghost adm-btn-lg">Batal</a>
        <button type="submit" class="adm-btn adm-btn-primary adm-btn-lg">
            <i class="fa-solid fa-plus"></i> Simpan Restoran
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
