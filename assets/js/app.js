document.addEventListener('DOMContentLoaded', () => {
    // Custom Indonesian validation messages for required and typed inputs
    document.addEventListener('invalid', function (e) {
        const target = e.target;
        if (target.hasAttribute('required') || target.validity.typeMismatch) {
            target.setCustomValidity("");
            if (target.validity.valueMissing) {
                target.setCustomValidity("Harap isi bagian ini.");
            } else if (target.validity.typeMismatch) {
                if (target.type === 'url') {
                    target.setCustomValidity("Harap masukkan URL yang valid.");
                } else if (target.type === 'email') {
                    target.setCustomValidity("Harap masukkan alamat email yang valid.");
                }
            }
        }
    }, true);

    document.addEventListener('input', function (e) {
        e.target.setCustomValidity("");
    });

    document.addEventListener('change', function (e) {
        if (e.target.tagName === 'SELECT') {
            e.target.setCustomValidity("");
        }
    });

    // Navbar scroll effect
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // Mobile Menu Toggle
    const mobileBtn = document.getElementById('mobile-toggle');
    const mobileNav = document.getElementById('mobile-nav');
    if (mobileBtn && mobileNav) {
        mobileBtn.addEventListener('click', () => {
            mobileNav.classList.toggle('active');
        });
    }

    // Back To Top
    const bttBtn = document.getElementById('back-to-top');
    if (bttBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                bttBtn.style.display = 'flex';
            } else {
                bttBtn.style.display = 'none';
            }
        });
        bttBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Interactive Star Rating Logic
    const stars = document.querySelectorAll('#interactive-stars i');
    const ratingInput = document.getElementById('review-rating');

    if (stars.length > 0 && ratingInput) {
        stars.forEach(star => {
            star.addEventListener('mouseover', function () {
                const val = this.getAttribute('data-val');
                highlightStars(val);
            });
            star.addEventListener('mouseout', function () {
                const currentVal = ratingInput.value;
                highlightStars(currentVal);
            });
            star.addEventListener('click', function () {
                const val = this.getAttribute('data-val');
                ratingInput.value = val;
                highlightStars(val);
            });
        });

        function highlightStars(val) {
            stars.forEach(s => {
                const sVal = s.getAttribute('data-val');
                if (sVal <= val) {
                    s.style.color = '#fbbf24'; // lighted up
                    s.classList.remove('fa-regular');
                    s.classList.add('fa-solid');
                } else {
                    s.style.color = '#d1d5db'; // grayed out
                    s.classList.remove('fa-solid');
                    s.classList.add('fa-regular');
                }
            });
        }
        // Init default
        highlightStars(ratingInput.value);
    }

    // Dynamic Admin Menu Adder Logic
    const addMenuBtn = document.getElementById('add-menu-btn');
    const menusContainer = document.getElementById('menus-container');

    if (addMenuBtn && menusContainer) {
        addMenuBtn.addEventListener('click', () => {
            const entry = document.createElement('div');
            entry.className = 'menu-entry grid';
            entry.style.cssText = 'grid-template-columns: 2fr 1fr 2fr; gap:15px; margin-bottom:15px; background:var(--bg-gradient); padding:15px; border-radius:4px; position:relative;';
            entry.innerHTML = `
                <button type="button" class="remove-menu-btn" style="position:absolute; top:-10px; right:-10px; background:#ef4444; color:#fff; border:none; border-radius:50%; width:25px; height:25px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
                <div class="form-group mb-0">
                    <label class="form-label" style="font-size:0.85rem;">Nama Menu</label>
                    <input type="text" name="menu_titles[]" class="form-control" required placeholder="Cth: Kepiting Saus Padang">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label" style="font-size:0.85rem;">Harga</label>
                    <input type="text" name="menu_prices[]" class="form-control" required placeholder="Cth: Rp 150.000">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label" style="font-size:0.85rem;">Deskripsi</label>
                    <input type="text" name="menu_descs[]" class="form-control" placeholder="Cth: Kepiting besar...">
                </div>
            `;
            menusContainer.appendChild(entry);
        });

        // Event delegation for removal
        menusContainer.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-menu-btn');
            if (btn) {
                btn.closest('.menu-entry').remove();
            }
        });
    }

    // Process Session Alerts into Toasts
    const errorAlert = document.getElementById('session-error');
    const successAlert = document.getElementById('session-success');
    if (errorAlert && errorAlert.innerText.trim() !== '') showToast(errorAlert.innerText, 'error');
    if (successAlert && successAlert.innerText.trim() !== '') showToast(successAlert.innerText, 'success');
});

// Toast Notification System
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;

    const iconColor = type === 'success' ? 'var(--clr-green)' : '#ef4444';
    const icon = type === 'success'
        ? `<i class="fa-solid fa-check-circle" style="color:${iconColor};"></i>`
        : `<i class="fa-solid fa-circle-exclamation" style="color:${iconColor};"></i>`;

    toast.innerHTML = `${icon} <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 320);
    }, 4000);
}

// REAL AJAX Favorite toggling
async function toggleFavorite(restaurantId) {
    let baseUrl = '';
    if (typeof window.BASE_URL !== 'undefined' && window.BASE_URL !== null) {
        baseUrl = window.BASE_URL;
    } else if (typeof BASE_URL !== 'undefined' && BASE_URL !== null) {
        baseUrl = BASE_URL;
    }
    const cleanBaseUrl = (baseUrl ? baseUrl.replace(/\/+$/, '') : '');

    // Check if user is logged in
    if (typeof window.IS_LOGGED_IN !== 'undefined' && window.IS_LOGGED_IN === false) {
        window.location.href = cleanBaseUrl + '/auth/login.php';
        return;
    }

    try {
        const url = cleanBaseUrl + '/api/toggle-favorite.php';
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ restaurant_id: restaurantId })
        });

        if (!res.ok) {
            // Try to get error message from response
            let errMsg = `Server error (${res.status})`;
            try { const d = await res.json(); errMsg = d.message || errMsg; } catch (e) { }
            showToast(errMsg, 'error');
            return;
        }

        const data = await res.json();

        if (data.success) {
            showToast(data.message || (data.status === 'added' ? 'Disimpan ke favorit' : 'Dihapus dari favorit'), 'success');

            // --- Update detail page fav button ---
            const favBtn = document.getElementById('fav-btn');
            if (favBtn) {
                const icon = favBtn.querySelector('i');
                const text = favBtn.querySelector('#fav-text');
                if (data.status === 'added') {
                    favBtn.classList.remove('btn-ghost');
                    favBtn.classList.add('btn-secondary');
                    icon.className = 'fa-solid fa-heart';
                    icon.style.color = '#ef4444';
                    if (text) text.innerText = 'Tersimpan';
                } else {
                    favBtn.classList.remove('btn-secondary');
                    favBtn.classList.add('btn-ghost');
                    icon.className = 'fa-regular fa-heart';
                    icon.style.color = 'rgba(255,255,255,0.85)';
                    if (text) text.innerText = 'Simpan';
                }
            }

            // --- Update card-level heart icons on listing pages ---
            const cardBtns = document.querySelectorAll(`[data-fav-id="${restaurantId}"]`);
            cardBtns.forEach(btn => {
                const cardIcon = btn.querySelector('i');
                if (!cardIcon) return;
                if (data.status === 'added') {
                    cardIcon.className = 'fa-solid fa-heart';
                    cardIcon.style.color = '#ef4444';
                    btn.title = 'Hapus dari favorit';
                } else {
                    cardIcon.className = 'fa-regular fa-heart';
                    cardIcon.style.color = '#aaa';
                    btn.title = 'Simpan ke favorit';
                }
            });
        } else {
            showToast(data.message || 'Terjadi kesalahan.', 'error');
        }
    } catch (e) {
        showToast('Tidak dapat terhubung ke server. Pastikan Anda sudah masuk.', 'error');
    }
}
