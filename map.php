<?php
require_once __DIR__ . '/includes/header.php';

// Pre-selected district dari URL (untuk state awal)
$selected_district = isset($_GET['district']) ? htmlspecialchars($_GET['district']) : '';

$districts = [
    'Batam Kota', 'Lubuk Baja', 'Batu Ampar', 'Sekupang',
    'Batu Aji',   'Sagulung',   'Bengkong',   'Nongsa',
    'Galang',     'Bulang',     'Belakang Padang', 'Sei Beduk',
];
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

<style>
/* ── Map page layout ── */
.map-page-wrap {
    display: flex;
    flex-direction: column;
    gap: 0;
}

/* Filter bar above map */
.map-filter-bar {
    background: var(--bg-card);
    border-bottom: 1px solid var(--border);
    padding: 14px 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    position: sticky;
    top: 66px;         /* below navbar */
    z-index: 800;
    box-shadow: var(--shadow-sm);
}

.map-filter-bar label {
    font-family: 'Poppins', sans-serif;
    font-size: 0.83rem;
    font-weight: 600;
    color: var(--text-muted);
    white-space: nowrap;
}

/* Outline pill select */
#map-district-select {
    appearance: none;
    -webkit-appearance: none;
    border: 1.5px solid var(--border);
    border-radius: 999px;
    background: transparent;
    color: var(--text-primary);
    font-family: 'Poppins', sans-serif;
    font-size: 0.83rem;
    font-weight: 500;
    padding: 7px 32px 7px 16px;
    cursor: pointer;
    outline: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    transition: border-color 0.2s, box-shadow 0.2s;
    min-width: 170px;
}
#map-district-select:focus,
#map-district-select.active {
    border-color: var(--clr-green);
    box-shadow: 0 0 0 3px rgba(45,106,63,0.12);
}

/* Filter apply button */
#map-filter-btn {
    display: none;   /* shown via JS when district chosen */
    align-items: center;
    gap: 6px;
    background: var(--clr-green);
    color: #fff;
    border: none;
    border-radius: 999px;
    padding: 7px 18px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.83rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.18s, transform 0.12s;
}
#map-filter-btn:hover { background: var(--clr-green-light); }
#map-filter-btn:active { transform: translateY(1px); }

/* Reset button */
#map-reset-btn {
    display: none;
    align-items: center;
    gap: 5px;
    background: transparent;
    color: var(--text-muted);
    border: 1.5px solid var(--border);
    border-radius: 999px;
    padding: 6px 14px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.18s;
}
#map-reset-btn:hover {
    border-color: var(--clr-orange);
    color: var(--clr-orange);
}

/* Result count badge */
#map-count-badge {
    font-family: 'Poppins', sans-serif;
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-left: auto;
    background: var(--bg-subtle);
    border: 1px solid var(--border);
    border-radius: 999px;
    padding: 4px 14px;
    white-space: nowrap;
}

/* Map container */
#restaurant-map {
    width: 100%;
    height: calc(100vh - 66px - 57px);   /* full remaining viewport */
    min-height: 420px;
    background: var(--bg-subtle);
}

/* Loading overlay */
#map-loading {
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.82);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    z-index: 999;
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    color: var(--clr-green);
    backdrop-filter: blur(4px);
}
[data-theme="dark"] #map-loading { background: rgba(15,26,19,0.82); }

#map-loading i { font-size: 2rem; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Leaflet popup override */
.leaflet-popup-content-wrapper {
    border-radius: 10px !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.18) !important;
    padding: 0 !important;
    overflow: hidden;
}
.leaflet-popup-content { margin: 0 !important; width: 230px !important; }
.leaflet-popup-tip-container { margin-top: -1px; }

.map-popup-img {
    width: 100%;
    height: 110px;
    object-fit: cover;
    display: block;
}
.map-popup-body {
    padding: 12px 14px 14px;
}
.map-popup-name {
    font-weight: 700;
    font-size: 0.88rem;
    color: #111;
    margin-bottom: 3px;
    line-height: 1.3;
}
.map-popup-district {
    font-size: 0.75rem;
    color: var(--clr-orange);
    font-weight: 600;
    margin-bottom: 7px;
}
.map-popup-stars { color: #fbbf24; font-size: 0.78rem; margin-bottom: 8px; }
.map-popup-link {
    display: inline-block;
    background: #2D6A3F;
    color: #fff !important;
    border-radius: 6px;
    padding: 5px 13px;
    font-size: 0.78rem;
    font-weight: 600;
    text-decoration: none !important;
    transition: background 0.18s;
}
.map-popup-link:hover { background: #256F3C; }
</style>

<div class="map-page-wrap">

    <!-- Filter Bar -->
    <div class="map-filter-bar">
        <i class="fa-solid fa-map-location-dot" style="color:var(--clr-green); font-size:1.1rem; flex-shrink:0;"></i>
        <label for="map-district-select">Kecamatan:</label>

        <select id="map-district-select" title="Filter kecamatan">
            <option value="">Semua Kecamatan</option>
            <?php foreach ($districts as $d): ?>
                <option value="<?= $d ?>" <?= $selected_district === $d ? 'selected' : '' ?>>
                    <?= $d ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button id="map-filter-btn" type="button">
            <i class="fa-solid fa-filter" style="font-size:0.78rem;"></i> Terapkan
        </button>

        <button id="map-reset-btn" type="button">
            <i class="fa-solid fa-xmark"></i> Reset
        </button>

        <span id="map-count-badge">Memuat...</span>
    </div>

    <!-- Map -->
    <div style="position:relative; flex:1;">
        <div id="map-loading">
            <i class="fa-solid fa-spinner"></i>
            <span>Memuat data restoran...</span>
        </div>
        <div id="restaurant-map"
             aria-label="Peta restoran seafood Batam"
             data-district="<?= $selected_district ?>">
        </div>
    </div>

</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WLs=" crossorigin=""></script>

<script>
/* ══════════════════════════════════════════════════════════
   Peta Restoran Seafood Batam — Leaflet + API
══════════════════════════════════════════════════════════ */
(function SeafoodMap() {

    /* ── Koordinat pusat tiap kecamatan di Batam ── */
    const DISTRICT_COORDS = {
        'Batam Kota':      [1.1291, 104.0222],
        'Lubuk Baja':      [1.1449, 104.0210],
        'Batu Ampar':      [1.1188, 104.0481],
        'Bengkong':        [1.1634, 104.0453],
        'Sekupang':        [1.0973, 103.9742],
        'Nongsa':          [1.1780, 104.1054],
        'Sagulung':        [1.0834, 104.0107],
        'Batu Aji':        [1.0670, 104.0023],
        'Belakang Padang': [1.0667, 103.9667],
        'Bulang':          [0.9833, 104.0333],
        'Galang':          [0.9000, 104.2667],
        'Sei Beduk':       [1.1167, 104.0000],
    };
    const BATAM_CENTER = [1.1301, 104.0529];

    /* ── Warna pin per kecamatan ── */
    const DISTRICT_COLORS = {
        'Batam Kota':      '#2D6A3F',
        'Lubuk Baja':      '#E07B2A',
        'Batu Ampar':      '#2196F3',
        'Bengkong':        '#9C27B0',
        'Sekupang':        '#F44336',
        'Nongsa':          '#00BCD4',
        'Sagulung':        '#FF5722',
        'Batu Aji':        '#607D8B',
        'Belakang Padang': '#795548',
        'Bulang':          '#009688',
        'Galang':          '#673AB7',
        'Sei Beduk':       '#4CAF50',
    };

    /* ── Inisialisasi Peta ── */
    const mapEl = document.getElementById('restaurant-map');
    if (!mapEl) return;

    const map = L.map('restaurant-map', {
        center: BATAM_CENTER,
        zoom: 12,
        scrollWheelZoom: true,
        zoomControl: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    /* ── State ── */
    let allMarkers   = [];     // semua marker yang dibuat
    let markerLayer  = L.layerGroup().addTo(map);
    let currentDistrict = mapEl.dataset.district || '';

    /* ── Buat icon pin kustom per warna ── */
    function makeIcon(color) {
        return L.divIcon({
            className: '',
            html: `
                <div style="
                    width:32px; height:32px;
                    background:${color};
                    border:3px solid #FFFFFF;
                    border-radius:50% 50% 50% 0;
                    transform:rotate(-45deg);
                    box-shadow:0 3px 10px rgba(0,0,0,0.28);
                "></div>
                <div style="
                    position:absolute; top:50%; left:50%;
                    transform:translate(-50%,-62%) rotate(45deg);
                    color:#fff; font-size:11px; pointer-events:none;
                ">🍽</div>`,
            iconSize:    [32, 32],
            iconAnchor:  [16, 32],
            popupAnchor: [0, -34],
        });
    }

    /* ── Render popup HTML ── */
    function makePopup(r) {
        const photo = (r.photos && r.photos.length) ? r.photos[0] : '';
        const rating = parseFloat(r.avg_rating || r.rating || 0).toFixed(1);
        const stars = '★'.repeat(Math.round(rating)) + '☆'.repeat(5 - Math.round(rating));
        const imgHtml = photo
            ? `<img src="${photo}" class="map-popup-img" alt="${r.name}">`
            : '';
        return `
            ${imgHtml}
            <div class="map-popup-body">
                <div class="map-popup-name">${r.name}</div>
                <div class="map-popup-district">
                    <i class="fa-solid fa-location-dot"></i> ${r.district}
                </div>
                <div class="map-popup-stars">${stars} <span style="color:#555;font-size:0.76rem;">${rating}/5</span></div>
                <a href="<?= BASE_URL ?>/restaurant-detail.php?id=${r.id}" class="map-popup-link" target="_blank">
                    Lihat Detail →
                </a>
            </div>`;
    }

    /* ── Tambah jitter kecil agar marker bertumpuk tidak overlap ── */
    function jitter(lat, lng, index) {
        const angle = (index * 137.5) * (Math.PI / 180); // golden angle
        const radius = 0.0015 * Math.sqrt(index % 8);
        return [lat + radius * Math.cos(angle), lng + radius * Math.sin(angle)];
    }

    /* ── Muat data dari API ── */
    async function loadRestaurants(district = '') {
        const loading = document.getElementById('map-loading');
        if (loading) loading.style.display = 'flex';

        // Hitung berapa restoran per district untuk jitter
        const districtCount = {};

        try {
            const url = new URL('/api/get-restaurants.php', window.location.origin);
            url.searchParams.set('limit', '200'); // ambil semua
            if (district) url.searchParams.set('district', district);

            const res = await fetch(url.toString());
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();

            if (!json.success) throw new Error(json.message || 'API error');

            // Hapus marker lama
            markerLayer.clearLayers();
            allMarkers = [];

            const restaurants = json.data || [];

            restaurants.forEach((r, i) => {
                // Koordinat: pakai lat/lng dari DB jika ada, fallback kecamatan
                let lat, lng;
                let rawLat = r.lat || r.latitude || 0;
                let rawLng = r.lng || r.longitude || 0;
                if (rawLat && rawLng && parseFloat(rawLat) !== 0 && parseFloat(rawLng) !== 0) {
                    lat = parseFloat(rawLat);
                    lng = parseFloat(rawLng);
                } else {
                    const base = DISTRICT_COORDS[r.district] || BATAM_CENTER;
                    // Hitung index per district untuk jitter
                    districtCount[r.district] = (districtCount[r.district] || 0) + 1;
                    const jIdx = districtCount[r.district];
                    [lat, lng] = jitter(base[0], base[1], jIdx);
                }

                const color  = DISTRICT_COLORS[r.district] || '#2D6A3F';
                const marker = L.marker([lat, lng], { icon: makeIcon(color) });
                marker.bindPopup(makePopup(r), { maxWidth: 240 });
                marker.restaurantData = r;
                markerLayer.addLayer(marker);
                allMarkers.push(marker);
            });

            // Update badge
            const badge = document.getElementById('map-count-badge');
            if (badge) {
                badge.textContent = `${restaurants.length} restoran ditampilkan`;
            }

            // Fit bounds ke marker yang ada
            if (allMarkers.length > 0) {
                const group = L.featureGroup(allMarkers);
                map.fitBounds(group.getBounds().pad(0.15));
            }

        } catch (err) {
            console.error('[SeafoodMap] Gagal memuat data:', err);
            const badge = document.getElementById('map-count-badge');
            if (badge) badge.textContent = 'Gagal memuat data.';
        } finally {
            if (loading) loading.style.display = 'none';
        }
    }

    /* ── UI: Filter dropdown ── */
    const selectEl   = document.getElementById('map-district-select');
    const filterBtn  = document.getElementById('map-filter-btn');
    const resetBtn   = document.getElementById('map-reset-btn');

    function syncFilterUI(district) {
        if (district) {
            filterBtn.style.display = 'inline-flex';
            resetBtn.style.display  = 'inline-flex';
            selectEl.classList.add('active');
        } else {
            filterBtn.style.display = 'none';
            resetBtn.style.display  = 'none';
            selectEl.classList.remove('active');
        }
    }

    // Init state sesuai URL / PHP pre-selection
    syncFilterUI(currentDistrict);
    if (currentDistrict && selectEl) selectEl.value = currentDistrict;

    selectEl.addEventListener('change', function () {
        syncFilterUI(this.value);
    });

    filterBtn.addEventListener('click', () => {
        currentDistrict = selectEl.value;
        loadRestaurants(currentDistrict);
        // Update URL tanpa reload
        const url = new URL(window.location);
        if (currentDistrict) {
            url.searchParams.set('district', currentDistrict);
        } else {
            url.searchParams.delete('district');
        }
        history.replaceState(null, '', url.toString());
    });

    resetBtn.addEventListener('click', () => {
        selectEl.value  = '';
        currentDistrict = '';
        syncFilterUI('');
        loadRestaurants('');
        history.replaceState(null, '', '/map.php');
    });

    /* ── Muat awal ── */
    loadRestaurants(currentDistrict);

})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
