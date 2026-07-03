<?php
ob_start();
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect(is_admin() ? '/admin/index.php' : '/profile.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $users = get_users();
    $user_found = false;

    foreach ($users as $u) {
        if ($u['email'] === $email || $u['username'] === $email) {
            if (password_verify($password, $u['password_hash'])) {
                $_SESSION['id_user'] = $u['id'];
                $_SESSION['role']    = $u['role'];
                $_SESSION['success'] = 'Selamat datang kembali, ' . $u['username'] . '!';
                session_write_close();
                redirect($u['role'] === 'admin' ? '/admin/index.php' : '/index.php');
            } else {
                $error = 'Kata sandi tidak valid.';
            }
            $user_found = true;
            break;
        }
    }
    if (!$user_found) $error = 'Pengguna tidak ditemukan.';
}

$theme = $_COOKIE['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Seafood Batam</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
    .auth-page {
        min-height: 100vh;
        background: var(--bg-subtle);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 16px;
    }
    [data-theme="dark"] .auth-page { background: #0F1710; }

    .auth-premium-card {
        background: var(--bg-card);
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.12);
        width: 100%;
        max-width: 520px;
        overflow: hidden;
    }
    [data-theme="dark"] .auth-premium-card {
        box-shadow: 0 20px 60px rgba(0,0,0,0.40);
    }

    .auth-top-bar {
        height: 8px;
        background: var(--clr-green);
    }

    .auth-card-body {
        padding: 36px 40px 40px;
    }

    @media (max-width: 480px) {
        .auth-card-body { padding: 28px 24px 32px; }
    }

    .auth-logo-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 22px;
    }

    .auth-logo-icon {
        width: 38px;
        height: 38px;
        background: var(--clr-green);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FFFFFF;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .auth-logo-name {
        font-family: 'Poppins', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: var(--clr-green);
        letter-spacing: -0.01em;
    }

    .auth-divider-line {
        height: 1px;
        background: var(--border);
        margin: 0 0 24px;
    }

    .auth-title {
        font-family: 'Poppins', sans-serif;
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--heading-color);
        letter-spacing: -0.025em;
        margin-bottom: 6px;
    }

    .auth-subtitle {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-bottom: 26px;
    }

    .auth-or {
        display: flex;
        align-items: center;
        gap: 14px;
        margin: 20px 0;
        color: var(--text-muted);
        font-size: 0.82rem;
        font-family: 'Poppins', sans-serif;
    }
    .auth-or::before, .auth-or::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border);
    }

    .pw-wrap {
        position: relative;
    }
    .pw-wrap .form-control { padding-right: 44px; }
    .pw-eye {
        position: absolute;
        right: 13px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 0.9rem;
        transition: color 0.2s;
    }
    .pw-eye:hover { color: var(--text-primary); }
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-premium-card">
        <div class="auth-top-bar"></div>
        <div class="auth-card-body">
            <!-- Logo -->
            <div class="auth-logo-row">
                <div class="auth-logo-icon"><i class="fa-solid fa-fish-fins"></i></div>
                <span class="auth-logo-name">Seafood Batam</span>
            </div>

            <div class="auth-divider-line"></div>

            <h1 class="auth-title">Masuk</h1>
            <p class="auth-subtitle">Selamat datang kembali di Seafood Batam</p>

            <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom:18px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="login-email">Email atau Nama Pengguna</label>
                    <input type="text" name="email" id="login-email" class="form-control"
                           placeholder="email@example.com atau username"
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                           autocomplete="username" required>
                </div>

                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label class="form-label" for="login-password" style="margin:0;">Kata Sandi</label>
                        <a href="<?= BASE_URL ?>/auth/forgot-password.php" style="font-size:0.82rem; color:var(--clr-orange); font-weight:600; text-decoration:none;">Lupa Kata Sandi?</a>
                    </div>
                    <div class="pw-wrap">
                        <input type="password" name="password" id="login-password" class="form-control"
                               placeholder="••••••••" autocomplete="current-password" required>
                        <button type="button" class="pw-eye" onclick="togglePwd('login-password','eye-icon-1')">
                            <i class="fa-solid fa-eye" id="eye-icon-1"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk
                </button>
            </form>

            <div class="auth-or">atau</div>

            <p style="text-align:center; font-size:0.875rem; color:var(--text-muted); font-family:'Poppins',sans-serif;">
                Belum memiliki akun? 
                <a href="<?= BASE_URL ?>/auth/register.php" style="color:var(--clr-orange); font-weight:700; text-decoration:underline;">Daftar di sini.</a>
            </p>
        </div>
    </div>

    <!-- Footer below card -->
    <p style="margin-top:22px; font-size:0.78rem; color:var(--text-muted); text-align:center; font-family:'Poppins',sans-serif;">
        &copy; <?= date('Y') ?> Seafood Batam &mdash;
        <a href="<?= BASE_URL ?>/index.php" style="color:var(--text-muted); text-decoration:underline;">Kembali ke Beranda</a>
    </p>
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

// Cookie-based theme (for consistency)
(function(){
    const t = document.cookie.split(';').map(c=>c.trim()).find(c=>c.startsWith('theme='));
    if (t) document.documentElement.setAttribute('data-theme', t.split('=')[1]);
})();
</script>
</body>
</html>
