<?php
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) redirect('/profile.php');

$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$email = trim($_GET['email'] ?? ($_POST['email'] ?? ''));

$error = '';
$success = '';
$valid_user = null;

if (!empty($token) && !empty($email)) {
    try {
        $pdo = get_db();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT id_user as id, username, email FROM user WHERE (email = ? OR username = ?) AND reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([$email, $email, $token]);
            $valid_user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {}

    if (!$valid_user) {
        $users = get_users();
        foreach ($users as $u) {
            if (($u['email'] === $email || $u['username'] === $email) && ($u['reset_token'] ?? '') === $token) {
                if (strtotime($u['reset_expires'] ?? '0') > time()) {
                    $valid_user = $u;
                    break;
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_user) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_pwd  = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 6) {
        $error = 'Kata sandi baru minimal 6 karakter.';
    } elseif ($new_password !== $confirm_pwd) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        $hashed_pw = password_hash($new_password, PASSWORD_DEFAULT);

        try {
            if (isset($pdo) && $pdo) {
                $upd = $pdo->prepare("UPDATE user SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id_user = ?");
                $upd->execute([$hashed_pw, $valid_user['id']]);
            }
        } catch (Throwable $e) {}

        // Update JSON backup
        $users = read_json('users.json');
        foreach ($users as &$u) {
            if ($u['id'] == $valid_user['id']) {
                $u['password_hash'] = $hashed_pw;
                unset($u['reset_token'], $u['reset_expires']);
                break;
            }
        }
        write_json('users.json', $users);

        $_SESSION['success'] = 'Kata sandi Anda berhasil diperbarui! Silakan masuk dengan kata sandi baru.';
        redirect('/auth/login.php');
    }
}

$theme = $_COOKIE['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Kata Sandi — Seafood Batam</title>
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
        max-width: 480px;
        overflow: hidden;
    }
    .auth-top-bar { height: 8px; background: var(--clr-green); }
    .auth-card-body { padding: 36px 40px 40px; }
    @media (max-width: 480px) { .auth-card-body { padding: 28px 22px 32px; } }

    .auth-logo-row {
        display: flex; align-items: center; gap: 10px; margin-bottom: 22px;
    }
    .auth-logo-icon {
        width: 38px; height: 38px; background: var(--clr-green); border-radius: 10px;
        display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1rem;
    }
    .auth-logo-name { font-family: 'Poppins', sans-serif; font-size: 1rem; font-weight: 700; color: var(--clr-green); }

    .auth-divider-line { height: 1px; background: var(--border); margin: 0 0 24px; }
    .auth-title { font-family: 'Poppins', sans-serif; font-size: 1.5rem; font-weight: 800; color: var(--heading-color); letter-spacing: -0.025em; margin-bottom: 5px; }
    .auth-subtitle { font-size: 0.875rem; color: var(--text-muted); margin-bottom: 24px; line-height: 1.6; }

    .pw-wrap { position: relative; }
    .pw-wrap .form-control { padding-right: 44px; }
    .pw-eye {
        position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
        background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 0.9rem;
    }
    .strength-bar-wrap { height: 4px; background: var(--border); border-radius: 4px; margin-top: 8px; }
    .strength-bar { height: 100%; border-radius: 4px; width: 0%; transition: width 0.3s ease, background 0.3s ease; }
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

            <?php if (!$valid_user): ?>
            <h1 class="auth-title">Tautan Tidak Valid</h1>
            <p class="auth-subtitle" style="color:#ef4444;">
                <i class="fa-solid fa-circle-exclamation"></i> Tautan pemulihan kata sandi tidak valid atau telah kedaluwarsa (batas waktu 1 jam).
            </p>
            <div style="margin-top:24px;">
                <a href="<?= BASE_URL ?>/auth/forgot-password.php" class="btn btn-primary" style="display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:12px;">
                    <i class="fa-solid fa-rotate-right"></i> Ajukan Ulang Pemulihan
                </a>
                <div style="margin-top:16px; text-align:center;">
                    <a href="<?= BASE_URL ?>/auth/login.php" style="font-size:0.875rem; color:var(--text-muted); text-decoration:underline;">Kembali ke Masuk</a>
                </div>
            </div>
            <?php else: ?>

            <h1 class="auth-title">Reset Kata Sandi</h1>
            <p class="auth-subtitle">Silakan buat kata sandi baru untuk akun <strong><?= htmlspecialchars($valid_user['username']) ?></strong>.</p>

            <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom:18px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

                <div class="form-group">
                    <label class="form-label" for="reset-pwd">Kata Sandi Baru * <span id="strength-label" style="font-weight:400; font-size:0.75rem; color:var(--text-muted);"></span></label>
                    <div class="pw-wrap">
                        <input type="password" name="new_password" id="reset-pwd" class="form-control"
                               placeholder="Min. 6 karakter" autocomplete="new-password"
                               oninput="updateStrength(this.value)" required autofocus>
                        <button type="button" class="pw-eye" onclick="togglePwd('reset-pwd','eye-1')">
                            <i class="fa-solid fa-eye" id="eye-1"></i>
                        </button>
                    </div>
                    <div class="strength-bar-wrap">
                        <div class="strength-bar" id="strength-bar"></div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:24px;">
                    <label class="form-label" for="reset-confirm">Konfirmasi Kata Sandi Baru *</label>
                    <div class="pw-wrap">
                        <input type="password" name="confirm_password" id="reset-confirm" class="form-control"
                               placeholder="Ulangi kata sandi baru" autocomplete="new-password" required>
                        <button type="button" class="pw-eye" onclick="togglePwd('reset-confirm','eye-2')">
                            <i class="fa-solid fa-eye" id="eye-2"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Kata Sandi Baru
                </button>
            </form>

            <div style="margin-top:20px; text-align:center;">
                <a href="<?= BASE_URL ?>/auth/login.php" style="font-size:0.875rem; color:var(--text-muted); text-decoration:underline;">Batalkan &amp; Kembali ke Masuk</a>
            </div>

            <?php endif; ?>
        </div>
    </div>

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

function updateStrength(val) {
    const bar   = document.getElementById('strength-bar');
    const label = document.getElementById('strength-label');
    if (!bar) return;
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val))   score++;
    if (/[0-9]/.test(val))   score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { pct:'20%', color:'#ef4444', text:'Sangat Lemah' },
        { pct:'40%', color:'#f97316', text:'Lemah' },
        { pct:'60%', color:'#eab308', text:'Sedang' },
        { pct:'80%', color:'#22c55e', text:'Kuat' },
        { pct:'100%',color:'#16a34a', text:'Sangat Kuat' },
    ];
    const lv = levels[Math.min(score, 4)];
    bar.style.width      = val ? lv.pct : '0%';
    bar.style.background = lv.color;
    label.textContent    = val ? '— ' + lv.text : '';
    label.style.color    = lv.color;
}

(function(){
    const t = document.cookie.split(';').map(c=>c.trim()).find(c=>c.startsWith('theme='));
    if (t) document.documentElement.setAttribute('data-theme', t.split('=')[1]);
})();
</script>
</body>
</html>
