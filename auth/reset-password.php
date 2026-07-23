<?php
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in()) {
    redirect(is_admin() ? '/admin/index.php' : '/profile.php');
}

$token = sanitize($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = '';
$valid_token = false;
$email = '';

try {
    $pdo = get_db();
    if ($pdo && !empty($token)) {
        $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $email = $stmt->fetchColumn();
        if ($email) {
            $valid_token = true;
        } else {
            $error = 'Tautan reset kata sandi tidak valid atau telah kedaluwarsa. Silakan minta tautan baru.';
        }
    } else {
        $error = 'Token tidak ditemukan.';
    }
} catch (Throwable $e) {
    $error = 'Terjadi kesalahan sistem.';
}

// Proses submit password baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 6) {
        $error = 'Kata sandi baru minimal harus 6 karakter.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        try {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE user SET password = ? WHERE email = ?");
            $updateStmt->execute([$hash, $email]);

            // Hapus token yang sudah digunakan
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            $success = 'Kata sandi Anda berhasil diubah! Silakan masuk dengan kata sandi baru.';
            $valid_token = false; // sembunyikan form reset
        } catch (Throwable $e) {
            $error = 'Gagal menyimpan kata sandi baru. Coba lagi nanti.';
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
    <title>Atur Ulang Kata Sandi — Seafood Batam</title>
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
        box-shadow: 0 20px 60px rgba(0,0,0,0.08);
        width: 100%;
        max-width: 440px;
        overflow: hidden;
        border: 1px solid var(--border);
        position: relative;
    }
    .auth-top-bar {
        height: 6px;
        background: linear-gradient(90deg, var(--clr-orange) 0%, #FFA726 100%);
    }
    .auth-card-body { padding: 36px 36px 40px; }
    .auth-title {
        font-family: 'Poppins', sans-serif;
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--heading-color);
        margin-bottom: 6px;
    }
    .auth-subtitle {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-bottom: 26px;
    }
    .pw-wrap { position: relative; }
    .pw-wrap .form-control { padding-right: 44px; }
    .pw-eye {
        position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
        background: none; border: none; color: var(--text-muted); cursor: pointer;
    }
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-premium-card">
        <div class="auth-top-bar"></div>
        <div class="auth-card-body">
            <h1 class="auth-title">Atur Ulang Kata Sandi</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom:20px; padding:16px; background:#d4edda; color:#155724; border-radius:8px;">
                    <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
                <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary btn-lg" style="width:100%; text-align:center; display:block; text-decoration:none;">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang
                </a>
            <?php elseif ($error && !$valid_token): ?>
                <div class="alert alert-error" style="margin-bottom:20px; padding:16px; background:#f8d7da; color:#721c24; border-radius:8px;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
                </div>
                <a href="<?= BASE_URL ?>/auth/forgot-password.php" class="btn btn-outline-orange" style="width:100%; text-align:center; display:block; text-decoration:none;">
                    <i class="fa-solid fa-rotate-left"></i> Minta Tautan Baru
                </a>
            <?php elseif ($valid_token): ?>
                <p class="auth-subtitle">Silakan buat kata sandi baru untuk akun Anda.</p>
                <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom:18px;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>
                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="new-pw">Kata Sandi Baru</label>
                        <div class="pw-wrap">
                            <input type="password" name="new_password" id="new-pw" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                            <button type="button" class="pw-eye" onclick="togglePwd('new-pw','eye-1')"><i class="fa-solid fa-eye" id="eye-1"></i></button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm-pw">Konfirmasi Kata Sandi Baru</label>
                        <div class="pw-wrap">
                            <input type="password" name="confirm_password" id="confirm-pw" class="form-control" placeholder="Ulangi kata sandi" required minlength="6">
                            <button type="button" class="pw-eye" onclick="togglePwd('confirm-pw','eye-2')"><i class="fa-solid fa-eye" id="eye-2"></i></button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Kata Sandi Baru
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
function togglePwd(inputId, eyeId) {
    const input = document.getElementById(inputId);
    const eye   = document.getElementById(eyeId);
    if (input.type === 'password') {
        input.type = 'text';
        eye.className = 'fa-solid fa-eye-slash';
    } else {
        input.type = 'password';
        eye.className = 'fa-solid fa-eye';
    }
}
</script>
</body>
</html>
