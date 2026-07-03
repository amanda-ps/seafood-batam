<?php
ob_start(); // buffer output to prevent "headers already sent" issues
require_once __DIR__ . '/../includes/functions.php';

if (is_admin()) {
    redirect('/admin/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $users      = get_users();
    $user_found = false;

    foreach ($users as $u) {
        if (($u['email'] === $email || $u['username'] === $email) && isset($u['role']) && $u['role'] === 'admin') {
            if (password_verify($password, $u['password_hash'])) {
                $_SESSION['user_id'] = $u['id'];
                $_SESSION['role']    = $u['role'];
                session_write_close(); // ensure session is written before redirect
                redirect('/admin/index.php');
            } else {
                $error = 'Kata sandi salah.';
            }
            $user_found = true;
            break;
        }
    }

    if (!$user_found) {
        $error = 'Akun admin tidak ditemukan.';
    }
}

$theme = $_COOKIE['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Administrator — Seafood Batam</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">

<div class="adm-login-page">
    <!-- Theme toggle top right -->
    <button onclick="toggleLoginTheme()" id="login-theme-btn"
            style="position:fixed; top:20px; right:20px; display:flex; align-items:center; gap:8px;
                   padding:8px 16px; border-radius:30px; border:1.5px solid var(--adm-card-border);
                   background:var(--adm-card); color:var(--adm-text-muted); font-family:'Poppins',sans-serif;
                   font-size:0.8rem; font-weight:600; cursor:pointer; transition:all 0.2s ease;">
        <i class="fa-solid fa-moon" id="login-theme-icon"></i>
        <span id="login-theme-label">Mode Gelap</span>
    </button>

    <div class="adm-login-card">
        <!-- Green top accent bar is done by ::before in CSS -->
        <div class="adm-login-body">
            <!-- Icon -->
            <div class="adm-login-icon"><i class="fa-solid fa-fish-fins"></i></div>

            <!-- Brand -->
            <div class="adm-login-brand">
                <div class="adm-login-brand-name">Seafood Batam</div>
                <div class="adm-login-brand-sub">Admin Panel</div>
            </div>

            <div class="adm-login-divider"></div>

            <h2 class="adm-login-title">Masuk Administrator</h2>

            <?php if ($error): ?>
            <div class="adm-alert adm-alert-error" style="margin-bottom:16px;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="adm-form-group">
                    <label class="adm-form-label" for="adm-email">Email Admin</label>
                    <input type="text" name="email" id="adm-email" class="adm-form-control"
                           placeholder="admin@seafoodbatam.id"
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                           autocomplete="username" required>
                </div>

                <div class="adm-form-group">
                    <label class="adm-form-label" for="adm-password">Kata Sandi</label>
                    <div class="adm-input-wrap">
                        <input type="password" name="password" id="adm-password" class="adm-form-control"
                               placeholder="••••••••" autocomplete="current-password" required>
                        <button type="button" class="adm-eye-btn" onclick="togglePwd('adm-password','adm-eye-i')">
                            <i class="fa-solid fa-eye" id="adm-eye-i"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="adm-btn adm-btn-primary adm-btn-lg" style="width:100%; margin-top:4px;">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk
                </button>
            </form>

            <p class="adm-login-caption">
                Hanya untuk administrator resmi.<br>
                Akses tidak sah akan dilaporkan dan ditindaklanjuti.
            </p>
        </div>
    </div>
</div>

<script>
function togglePwd(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.className = 'fa-solid fa-eye-slash';
    } else {
        input.type = 'password';
        if (icon) icon.className = 'fa-solid fa-eye';
    }
}

function toggleLoginTheme() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    const newTheme = isDark ? 'light' : 'dark';
    html.setAttribute('data-theme', newTheme);
    document.cookie = 'theme=' + newTheme + '; path=/; max-age=31536000';

    const icon  = document.getElementById('login-theme-icon');
    const label = document.getElementById('login-theme-label');
    if (newTheme === 'dark') {
        if (icon) icon.className = 'fa-solid fa-sun';
        if (label) label.textContent = 'Mode Terang';
    } else {
        if (icon) icon.className = 'fa-solid fa-moon';
        if (label) label.textContent = 'Mode Gelap';
    }
}

// init label
(function(){
    const theme = document.documentElement.getAttribute('data-theme');
    const icon  = document.getElementById('login-theme-icon');
    const label = document.getElementById('login-theme-label');
    if (theme === 'dark') {
        if (icon) icon.className = 'fa-solid fa-sun';
        if (label) label.textContent = 'Mode Terang';
    }
})();
</script>
</body>
</html>
