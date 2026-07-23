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

    $new_photo_urls = [];
    if (isset($_POST['photo_urls']) && is_array($_POST['photo_urls'])) {
        foreach ($_POST['photo_urls'] as $url) {
            $url = trim($url);
            if (!empty($url)) $new_photo_urls[] = $url;
        }
    }

    $uploaded_photo_urls = [];
    if (isset($_FILES['photo_files']) && !empty($_FILES['photo_files']['name']) && is_array($_FILES['photo_files']['name'])) {
        $upload_dir = __DIR__ . '/../assets/images/restaurants/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'];
        $file_count = count($_FILES['photo_files']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['photo_files']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['photo_files']['name'][$i], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed_exts)) {
                    $new_filename = uniqid('res_') . '_' . time() . '_' . $i . '.' . $ext;
                    $target_path  = $upload_dir . $new_filename;
                    if (move_uploaded_file($_FILES['photo_files']['tmp_name'][$i], $target_path)) {
                        $uploaded_photo_urls[] = BASE_URL . '/assets/images/restaurants/' . $new_filename;
                    }
                }
            }
        }
    }

    $photo_urls = array_values(array_unique(array_merge($new_photo_urls, $uploaded_photo_urls)));
    if (count($photo_urls) > 10) {
        $photo_urls = array_slice($photo_urls, 0, 10);
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

    try { write_json('restaurants.json', $restaurants); } catch(Throwable $e){}

    try {
        $pdo = get_db();
        if ($pdo) {
            $stmtKec = $pdo->prepare("SELECT id_kecamatan FROM kecamatan WHERE nama_kecamatan = ?");
            $stmtKec->execute([$_POST['district']]);
            $id_kec = $stmtKec->fetchColumn() ?: 1;

            $stmtIns = $pdo->prepare("
                INSERT INTO restoran (nama_restoran, deskripsi, id_kecamatan, alamat, jam_operasional_weekday, maps, no_wa, foto_utama, rating)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0.0)
            ");
            $stmtIns->execute([
                sanitize($_POST['name']),
                sanitize($_POST['description']),
                $id_kec,
                sanitize($_POST['address']),
                sanitize($_POST['hours'] ?? ''),
                sanitize($_POST['maps_link'] ?? ''),
                sanitize($_POST['whatsapp'] ?? ''),
                $photo_urls[0]
            ]);
            $db_id = $pdo->lastInsertId();

            if ($db_id) {
                foreach ($photo_urls as $purl) {
                    $pdo->prepare("INSERT INTO foto_restoran (id_restoran, url_foto) VALUES (?, ?)")->execute([$db_id, $purl]);
                }
                foreach ($parsed_menus as $m) {
                    $stmtMenu = $pdo->prepare("INSERT INTO menu (id_restoran, nama_menu, harga) VALUES (?, ?, ?)");
                    $stmtMenu->execute([$db_id, $m['name'], $m['price']]);
                }
            }
        }
    } catch (Throwable $e) {}

    $_SESSION['success'] = 'Restoran baru berhasil ditambahkan!';
    redirect('restoran.php');
}

require_once __DIR__ . '/../includes/admin-header.php';
global $districts;
if (empty($districts)) $districts = get_all_districts();
?>

<!-- Back link -->
<div style="margin-bottom:20px;">
    <a href="<?= BASE_URL ?>/admin/restoran.php" style="display:inline-flex; align-items:center; gap:7px; font-size:0.85rem; font-weight:600; color:var(--adm-text-muted); font-family:'Poppins',sans-serif; transition:color 0.2s;" onmouseover="this.style.color='var(--adm-green)'" onmouseout="this.style.color='var(--adm-text-muted)'">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar Restoran
    </a>
</div>

<form method="POST" action="" enctype="multipart/form-data">

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

    <!-- Photo Management Card (Up to 10 photos total: direct uploads + URL links) -->
    <div class="adm-card" style="margin-bottom:20px;">
        <div class="adm-card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div class="adm-card-title">
                <i class="fa-solid fa-images"></i>
                Foto Restoran (Maksimal 10 Foto)
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <span id="photo-count-badge" style="background:rgba(45, 106, 63, 0.12); color:var(--adm-green); padding:5px 14px; border-radius:30px; font-weight:700; font-size:0.82rem; font-family:'Poppins',sans-serif; display:flex; align-items:center; gap:6px;">
                    <i class="fa-solid fa-camera"></i> <span id="photo-total-count">0</span> / 10 Foto Dipilih
                </span>
            </div>
        </div>
        <div class="adm-card-body" style="display:flex; flex-direction:column; gap:24px;">

            <!-- Section 1: Unggah Foto Langsung dari Komputer / HP -->
            <div>
                <div style="font-weight:700; font-size:0.9rem; color:var(--adm-text); margin-bottom:6px; display:flex; align-items:center; gap:6px;">
                    <i class="fa-solid fa-cloud-arrow-up" style="color:var(--adm-blue, #2196F3);"></i> Unggah Foto Langsung (File Gambar)
                </div>
                <p style="font-size:0.8rem; color:var(--adm-text-muted); margin-bottom:12px;">Pilih atau geser file gambar dari komputer/HP Anda. Anda dapat memilih beberapa foto sekaligus (Multi-upload).</p>
                
                <div id="drop-zone-area" style="border:2px dashed #CBD5E1; border-radius:12px; padding:24px; text-align:center; background:#F8FAFC; cursor:pointer; transition:all 0.2s;" onclick="document.getElementById('photo-file-input').click();">
                    <input type="file" id="photo-file-input" name="photo_files[]" multiple accept="image/*" style="display:none;" onchange="handleFileSelect(this)">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size:2.2rem; color:var(--adm-green); margin-bottom:8px;"></i>
                    <div style="font-weight:600; font-size:0.92rem; color:#1E293B; margin-bottom:4px;">Klik di sini atau Geser Foto ke area ini</div>
                    <div style="font-size:0.78rem; color:#64748B;">Mendukung format JPG, PNG, WEBP, AVIF. (Maksimal total 10 foto gabungan)</div>
                </div>
                <div id="file-upload-preview" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:12px; margin-top:14px;"></div>
            </div>

            <div style="height:1px; background:var(--adm-card-border); margin:4px 0;"></div>

            <!-- Section 2: Input Foto Berupa Link URL -->
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; flex-wrap:wrap; gap:8px;">
                    <div style="font-weight:700; font-size:0.9rem; color:var(--adm-text); display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-link" style="color:var(--adm-orange, #FF9800);"></i> Tambah dari Link / URL Internet
                    </div>
                    <button type="button" id="add-url-row-btn" onclick="addUrlInputRow()" class="adm-btn adm-btn-outline-green adm-btn-sm" style="border-radius:20px; padding:6px 14px; font-size:0.8rem;">
                        <i class="fa-solid fa-plus"></i> Tambah Baris Link
                    </button>
                </div>
                <p style="font-size:0.8rem; color:var(--adm-text-muted); margin-bottom:12px;">Masukkan link/URL foto dari internet jika foto tidak disimpan di komputer.</p>
                
                <div id="photo-urls-container" style="display:flex; flex-direction:column; gap:10px;">
                    <!-- Rows generated dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Photo Limits & Previews -->
    <script>
    function addUrlInputRow(initialValue = '') {
        const container = document.getElementById('photo-urls-container');
        if (getTotalPhotosCount() >= 10 && !initialValue) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Batas Maksimal', text: 'Maksimal 10 foto untuk satu restoran sudah tercapai!' });
            } else {
                alert('Maksimal 10 foto untuk satu restoran sudah tercapai!');
            }
            return;
        }

        const rowId = 'url-row-' + Date.now() + '-' + Math.random().toString(36).substr(2, 5);
        const rowDiv = document.createElement('div');
        rowDiv.className = 'adm-form-group url-photo-row';
        rowDiv.id = rowId;
        rowDiv.style.cssText = 'margin:0; display:flex; gap:10px; align-items:center; background:var(--adm-bg); padding:10px; border-radius:8px; border:1px solid var(--adm-card-border);';
        
        rowDiv.innerHTML = `
            <span style="min-width:24px; color:var(--adm-text-muted); font-family:'Poppins',sans-serif; font-size:0.82rem; font-weight:600;">🔗</span>
            <input type="url" name="photo_urls[]" class="adm-form-control url-input-field"
                   placeholder="https://images.unsplash.com/... atau link foto publik"
                   value="${initialValue}" oninput="updateUrlPreview(this); updatePhotoCounter();" style="flex:1;">
            <div class="url-preview-box" style="width:42px; height:42px; border-radius:6px; overflow:hidden; background:#e2e8f0; display:${initialValue ? 'block' : 'none'}; flex-shrink:0; border:1px solid #cbd5e1;">
                ${initialValue ? `<img src="${initialValue}" style="width:100%; height:100%; object-fit:cover;" onerror="this.parentElement.style.display='none'">` : ''}
            </div>
            <button type="button" onclick="removeUrlRow('${rowId}')" class="adm-btn adm-btn-outline-red adm-btn-sm" style="padding:6px 10px; flex-shrink:0;" title="Hapus baris ini">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;
        container.appendChild(rowDiv);
        updatePhotoCounter();
    }

    function removeUrlRow(rowId) {
        const row = document.getElementById(rowId);
        if (row) {
            row.remove();
            updatePhotoCounter();
        }
    }

    function updateUrlPreview(inputElem) {
        const row = inputElem.closest('.url-photo-row');
        const previewBox = row.querySelector('.url-preview-box');
        const val = inputElem.value.trim();
        if (val) {
            previewBox.style.display = 'block';
            previewBox.innerHTML = `<img src="${val}" style="width:100%; height:100%; object-fit:cover;" onerror="this.parentElement.style.display='none'">`;
        } else {
            previewBox.style.display = 'none';
            previewBox.innerHTML = '';
        }
    }

    function handleFileSelect(inputElem) {
        const previewContainer = document.getElementById('file-upload-preview');
        previewContainer.innerHTML = '';
        if (!inputElem.files || inputElem.files.length === 0) {
            updatePhotoCounter();
            return;
        }

        const currentCount = getUrlCount();
        const availableSlots = Math.max(0, 10 - currentCount);
        if (inputElem.files.length > availableSlots) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Batas Maksimal 10 Foto', text: `Anda hanya memiliki sisa ${availableSlots} slot foto. Hanya ${availableSlots} foto pertama dari file yang dipilih yang akan diunggah.` });
            } else {
                alert(`Anda hanya memiliki sisa ${availableSlots} slot foto. Hanya ${availableSlots} foto pertama yang akan diunggah.`);
            }
        }

        Array.from(inputElem.files).forEach((file, idx) => {
            if (idx >= availableSlots) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.style.cssText = 'position:relative; border-radius:8px; overflow:hidden; border:2px solid var(--adm-green); aspect-ratio:1; background:#fff;';
                div.innerHTML = `
                    <img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">
                    <div style="position:absolute; bottom:0; inset-x:0; background:rgba(45,106,63,0.85); color:#fff; font-size:0.68rem; font-weight:700; padding:3px 6px; text-align:center;">
                        📁 File Baru
                    </div>
                `;
                previewContainer.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
        updatePhotoCounter();
    }

    function getFileCount() {
        const inputElem = document.getElementById('photo-file-input');
        if (!inputElem || !inputElem.files) return 0;
        const currentCount = getUrlCount();
        const slots = Math.max(0, 10 - currentCount);
        return Math.min(inputElem.files.length, slots);
    }

    function getUrlCount() {
        let count = 0;
        document.querySelectorAll('.url-input-field').forEach(inp => {
            if (inp.value.trim() !== '') count++;
        });
        return count;
    }

    function getTotalPhotosCount() {
        return getFileCount() + getUrlCount();
    }

    function updatePhotoCounter() {
        const fileCount = getFileCount();
        const urlCount = getUrlCount();
        const total = fileCount + urlCount;

        const totalSpan = document.getElementById('photo-total-count');
        const badge = document.getElementById('photo-count-badge');
        if (totalSpan) totalSpan.innerText = total;

        if (badge) {
            if (total >= 10) {
                badge.style.background = 'rgba(239, 68, 68, 0.15)';
                badge.style.color = '#ef4444';
                badge.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> <span>${total}</span> / 10 Foto (Maksimal!)`;
            } else {
                badge.style.background = 'rgba(45, 106, 63, 0.12)';
                badge.style.color = 'var(--adm-green)';
                badge.innerHTML = `<i class="fa-solid fa-camera"></i> <span>${total}</span> / 10 Foto Dipilih`;
            }
        }

        const addBtn = document.getElementById('add-url-row-btn');
        if (addBtn) {
            addBtn.disabled = total >= 10;
            addBtn.style.opacity = total >= 10 ? '0.5' : '1';
        }
    }

    window.addEventListener('DOMContentLoaded', function() {
        updatePhotoCounter();
        if (getTotalPhotosCount() < 10 && document.querySelectorAll('.url-photo-row').length === 0) {
            addUrlInputRow();
        }
    });
    </script>

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
