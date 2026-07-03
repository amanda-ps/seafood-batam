<?php
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) redirect('/profile.php');

$error = '';
$success = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_user = trim($_POST['email'] ?? '');

    if (empty($email_or_user)) {
        $error = 'Harap masukkan alamat email atau nama pengguna Anda.';
    } else {
        $found_user = null;
        try {
            $pdo = get_db();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT id_user as id, username, email FROM user WHERE email = ? OR username = ?");
                $stmt->execute([$email_or_user, $email_or_user]);
                $found_user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $e) {}

        if (!$found_user) {
            $users = get_users();
            foreach ($users as $u) {
                if ($u['email'] === $email_or_user || $u['username'] === $email_or_user) {
                    $found_user = $u;
                    break;
                }
            }
        }

        if ($found_user) {
            $token = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 jam dari sekarang

            try {
                if (isset($pdo) && $pdo) {
                    $upd = $pdo->prepare("UPDATE user SET reset_token = ?, reset_expires = ? WHERE id_user = ?");
                    $upd->execute([$token, $expires, $found_user['id']]);
                }
            } catch (Throwable $e) {}

            // Update JSON backup juga
            $users = read_json('users.json');
            foreach ($users as &$u) {
                if ($u['id'] == $found_user['id']) {
                    $u['reset_token'] = $token;
                    $u['reset_expires'] = $expires;
                    break;
                }
            }
            write_json('users.json', $users);

            $success = 'Instruksi pemulihan kata sandi telah berhasil diproses!';
            $reset_link = BASE_URL . '/auth/reset-password.php?token=' . urlencode($token) . '&email=' . urlencode($found_user['email']);
        } else {
            $error = 'Alamat email atau nama pengguna tidak ditemukan dalam sistem.';
        }
    }
}

$theme = $_COOKIE['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Sandi — Seafood Batam</title>
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

    .simulated-email-box {
        background: rgba(45, 106, 63, 0.08);
        border: 1.5px dashed var(--clr-green);
        border-radius: 12px;
        padding: 16px;
        margin-top: 20px;
        text-align: left;
    }
    [data-theme="dark"] .simulated-email-box {
        background: rgba(45, 106, 63, 0.18);
    }
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

            <h1 class="auth-title">Lupa Kata Sandi</h1>
            <p class="auth-subtitle">Masukkan email atau nama pengguna Anda yang terdaftar. Kami akan menyiapkan tautan untuk mereset kata sandi Anda.</p>

            <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom:18px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:18px;">
                <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>

            <div class="simulated-email-box">
                <div style="font-size:0.82rem; font-weight:700; color:var(--clr-green); margin-bottom:6px; display:flex; align-items:center; gap:6px;">
                    <i class="fa-solid fa-envelope-open-text"></i> Simulasi Email Pemulihan (XAMPP Lokal)
                </div>
                <p style="font-size:0.83rem; color:var(--text-secondary); margin-bottom:12px; line-height:1.5;">
                    Di lingkungan server lokal tanpa SMTP, tautan pemulihan Anda ditampilkan langsung di bawah ini agar Anda dapat langsung melanjutkan proses reset kata sandi:
                </p>
                <a href="<?= $reset_link ?>" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; width:100%; justify-content:center; padding:10px;">
                    <i class="fa-solid fa-key"></i> Reset Kata Sandi Sekarang
                </a>
            </div>
            <?php else: ?>

            <form method="POST" action="">
                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label" for="fp-email">Email atau Nama Pengguna *</label>
                    <input type="text" name="email" id="fp-email" class="form-control"
                           placeholder="email@example.com atau username"
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                           required autofocus>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">
                    <i class="fa-solid fa-paper-plane"></i> Kirim Instruksi Pemulihan
                </button>
            </form>

            <?php endif; ?>

            <div style="margin-top:24px; text-align:center;">
                <a href="<?= BASE_URL ?>/auth/login.php" style="font-size:0.875rem; color:var(--clr-orange); font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Masuk
                </a>
            </div>
        </div>
    </div>

    <p style="margin-top:22px; font-size:0.78rem; color:var(--text-muted); text-align:center; font-family:'Poppins',sans-serif;">
        &copy; <?= date('Y') ?> Seafood Batam &mdash;
        <a href="<?= BASE_URL ?>/index.php" style="color:var(--text-muted); text-decoration:underline;">Kembali ke Beranda</a>
    </p>
</div>

<script>
(function(){
    const t = document.cookie.split(';').map(c=>c.trim()).find(c=>c.startsWith('theme='));
    if (t) document.documentElement.setAttribute('data-theme', t.split('=')[1]);
})();
</script>
</body>
</html>
