        </main><!-- end .adm-content -->
    </div><!-- end .adm-main -->
</div><!-- end .adm-layout -->

<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
// Dark mode toggle for admin
function toggleAdmTheme() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    const newTheme = isDark ? 'light' : 'dark';
    html.setAttribute('data-theme', newTheme);
    document.cookie = 'theme=' + newTheme + '; path=/; max-age=31536000';
    updateAdmThemeUI(newTheme);
}

function updateAdmThemeUI(theme) {
    const icon  = document.getElementById('adm-theme-icon');
    const label = document.getElementById('adm-theme-label');
    if (!icon || !label) return;
    if (theme === 'dark') {
        icon.className  = 'fa-solid fa-sun';
        label.textContent = 'Mode Terang';
    } else {
        icon.className  = 'fa-solid fa-moon';
        label.textContent = 'Mode Gelap';
    }
}

updateAdmThemeUI(document.documentElement.getAttribute('data-theme'));

// Confirmation modal system
let _adm_pending_action = null;

function admConfirm(title, msg, iconClass, btnClass, btnIcon, btnLabel, onConfirm) {
    _adm_pending_action = onConfirm;
    document.getElementById('adm-modal-title').textContent = title;
    document.getElementById('adm-modal-msg').textContent   = msg;
    document.getElementById('adm-modal-icon').className    = 'adm-modal-icon ' + iconClass;
    document.getElementById('adm-modal-icon-i').className  = btnIcon;

    const confirmBtn = document.getElementById('adm-modal-confirm');
    confirmBtn.className = 'adm-btn adm-btn-lg ' + btnClass;
    confirmBtn.innerHTML = '<i class="' + btnIcon + '"></i> ' + btnLabel;
    confirmBtn.onclick   = function() {
        closeAdmModal();
        if (_adm_pending_action) _adm_pending_action();
    };
    document.getElementById('adm-confirm-overlay').classList.add('open');
}

function closeAdmModal() {
    document.getElementById('adm-confirm-overlay').classList.remove('open');
    _adm_pending_action = null;
}

// Close on overlay click
document.getElementById('adm-confirm-overlay')?.addEventListener('click', function(e) {
    if (e.target === this) closeAdmModal();
});

// Admin toast
function admToast(msg, type = 'success') {
    const container = document.getElementById('adm-toast-container');
    if (!container) return;
    const t = document.createElement('div');
    t.className = 'adm-toast' + (type !== 'success' ? ' adm-toast-error' : '');
    const icon = type === 'success'
        ? '<i class="fa-solid fa-check-circle" style="color:var(--adm-green);"></i>'
        : '<i class="fa-solid fa-circle-exclamation" style="color:var(--adm-red);"></i>';
    t.innerHTML = icon + '<span>' + msg + '</span>';
    container.appendChild(t);
    setTimeout(() => {
        t.style.opacity = '0';
        t.style.transform = 'translateX(108%)';
        t.style.transition = 'all 0.3s ease';
        setTimeout(() => t.remove(), 320);
    }, 4000);
}
</script>
</body>
</html>
