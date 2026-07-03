/**
 * admin.js — Seafood Batam Admin Panel
 */

// ── Chart.js global defaults ──────────────────────────────
if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.color = '#6B7280';
}

// ── Resize chart on window resize ────────────────────────
let visitChart = null;

function buildVisitChart(labels, data) {
    const canvas = document.getElementById('visit-chart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

    const gradient = ctx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, isDark ? 'rgba(61,140,82,0.35)' : 'rgba(45,106,63,0.20)');
    gradient.addColorStop(1, 'rgba(45,106,63,0)');

    if (visitChart) visitChart.destroy();

    visitChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Kunjungan',
                data: data,
                borderColor: isDark ? '#3D8C52' : '#2D6A3F',
                backgroundColor: gradient,
                tension: 0.42,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: isDark ? '#3D8C52' : '#2D6A3F',
                pointHoverRadius: 7,
                borderWidth: 2.5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#1C2128' : '#FFFFFF',
                    titleColor: isDark ? '#E6EDF3' : '#111827',
                    bodyColor: isDark ? '#8B949E' : '#6B7280',
                    borderColor: isDark ? '#21262D' : '#E5E7EB',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 10,
                }
            },
            scales: {
                x: {
                    grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.04)' },
                    ticks: { color: isDark ? '#8B949E' : '#9CA3AF', font: { size: 11 } },
                    border: { dash: [4,4] }
                },
                y: {
                    grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.04)' },
                    ticks: { color: isDark ? '#8B949E' : '#9CA3AF', font: { size: 11 } },
                    border: { dash: [4,4] },
                    beginAtZero: true
                }
            }
        }
    });
}

// ── Chart period data ─────────────────────────────────────
const chartData = {
    weekly:  {
        labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
        data:   [62, 78, 85, 70, 94, 138, 112]
    },
    monthly: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        data:   [820, 940, 870, 1020, 980, 1150, 1340, 1290, 1100, 1210, 1380, 1560]
    },
    yearly: {
        labels: ['2021', '2022', '2023', '2024', '2025'],
        data:   [4200, 7800, 12400, 18900, 24300]
    }
};

let activePeriod = 'weekly';

function switchChartPeriod(period) {
    activePeriod = period;
    document.querySelectorAll('.adm-chart-tab').forEach(t => {
        t.classList.toggle('active', t.dataset.period === period);
    });
    buildVisitChart(chartData[period].labels, chartData[period].data);
}

// ── Init on DOM ready ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    // Init chart if canvas exists
    if (document.getElementById('visit-chart')) {
        buildVisitChart(chartData.weekly.labels, chartData.weekly.data);
    }

    // Menu row logic (add/edit restaurant)
    initMenuRows();
});

// ── Menu rows (restaurant form) ───────────────────────────
function initMenuRows() {
    const container = document.getElementById('adm-menus-container');
    const addBtn    = document.getElementById('adm-add-menu-btn');
    if (!container || !addBtn) return;

    addBtn.addEventListener('click', addMenuRow);
    container.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.adm-menu-remove-btn');
        if (removeBtn) {
            const row = removeBtn.closest('.adm-menu-row');
            if (row && container.querySelectorAll('.adm-menu-row').length > 0) {
                row.style.opacity = '0';
                row.style.transform = 'scale(0.95)';
                row.style.transition = 'all 0.2s ease';
                setTimeout(() => row.remove(), 200);
            }
        }
    });
}

function addMenuRow(nameVal = '', priceVal = '', descVal = '') {
    const container = document.getElementById('adm-menus-container');
    if (!container) return;

    const row = document.createElement('div');
    row.className = 'adm-menu-row';
    row.innerHTML = `
        <div class="adm-form-group">
            <label class="adm-form-label">Nama Menu</label>
            <input type="text" name="menu_titles[]" class="adm-form-control" placeholder="Cth: Kepiting Saus Padang" value="${escHtml(nameVal)}">
        </div>
        <div class="adm-form-group">
            <label class="adm-form-label">Harga</label>
            <input type="text" name="menu_prices[]" class="adm-form-control" placeholder="Rp 0" value="${escHtml(priceVal)}">
        </div>
        <div class="adm-form-group">
            <label class="adm-form-label">Deskripsi</label>
            <input type="text" name="menu_descs[]" class="adm-form-control" placeholder="Deskripsi singkat..." value="${escHtml(descVal)}">
        </div>
        <button type="button" class="adm-menu-remove-btn adm-btn adm-btn-outline-red adm-btn-sm" style="align-self:flex-end; margin-bottom:0;">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    container.appendChild(row);
}

function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
}

// ── Toggle user status via AJAX ───────────────────────────
async function toggleUserStatus(userId, newStatus) {
    const label = newStatus === 'active' ? 'mengaktifkan' : 'menonaktifkan';
    admConfirm(
        'Konfirmasi Ubah Status',
        `Apakah Anda yakin ingin ${label} pengguna ini?`,
        newStatus === 'active' ? 'adm-modal-icon-green' : 'adm-modal-icon-orange',
        newStatus === 'active' ? 'adm-btn-outline-green' : 'adm-btn-outline-orange',
        newStatus === 'active' ? 'fa-solid fa-check' : 'fa-solid fa-ban',
        newStatus === 'active' ? 'Aktifkan' : 'Nonaktifkan',
        async () => {
            try {
                const res = await fetch('/admin/toggle-user-status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, status: newStatus })
                });
                const data = await res.json();
                if (data.success) {
                    admToast(data.message, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    admToast(data.message || 'Gagal mengubah status.', 'error');
                }
            } catch(e) {
                admToast('Koneksi gagal.', 'error');
            }
        }
    );
}

// ── Confirm delete with form submit ──────────────────────
function confirmDelete(formId, title, msg) {
    admConfirm(
        title || 'Konfirmasi Hapus',
        msg || 'Apakah Anda yakin? Tindakan ini tidak dapat dibatalkan.',
        'adm-modal-icon-red',
        'adm-btn-outline-red',
        'fa-solid fa-trash',
        'Hapus',
        () => document.getElementById(formId)?.submit()
    );
}
