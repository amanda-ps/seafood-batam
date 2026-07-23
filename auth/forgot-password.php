<?php
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in()) {
    redirect(is_admin() ? '/admin/index.php' : '/profile.php');
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
    <!-- SweetAlert2 untuk notifikasi pop-up modern -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    [data-theme="dark"] .auth-premium-card {
        box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    }

    .auth-top-bar {
        height: 6px;
        background: linear-gradient(90deg, var(--clr-orange) 0%, #FFA726 100%);
    }

    .auth-card-body {
        padding: 36px 36px 40px;
    }

    .auth-logo-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
        text-decoration: none;
    }
    .auth-logo-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--clr-orange) 0%, #D84315 100%);
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
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--heading-color);
        letter-spacing: -0.025em;
        margin-bottom: 6px;
    }

    .auth-subtitle {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-bottom: 26px;
        line-height: 1.5;
    }
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-premium-card">
        <div class="auth-top-bar"></div>
        <div class="auth-card-body">
            <a href="<?= BASE_URL ?>/index.php" class="auth-logo-row">
                <div class="auth-logo-icon"><i class="fa-solid fa-fish-fins"></i></div>
                <span class="auth-logo-name">Seafood Batam</span>
            </a>

            <div class="auth-divider-line"></div>

            <h1 class="auth-title">Lupa Kata Sandi?</h1>
            <p class="auth-subtitle">Masukkan alamat email yang terdaftar pada akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.</p>

            <form id="forgotForm">
                <div class="form-group">
                    <label class="form-label" for="email">Alamat Email</label>
                    <input type="email" name="email" id="email" class="form-control"
                           placeholder="email@example.com" required autocomplete="email">
                </div>

                <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit" style="width:100%;">
                    <i class="fa-solid fa-paper-plane"></i> Kirim Tautan Reset
                </button>
            </form>

            <div style="margin-top:24px; text-align:center;">
                <a href="<?= BASE_URL ?>/auth/login.php" style="font-size:0.875rem; color:var(--clr-orange); font-weight:600; text-decoration:none;">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Masuk
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('forgotForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmit');
    const emailInput = document.getElementById('email');
    const email = emailInput.value.trim();

    if (!email) {
        Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Silakan masukkan alamat email Anda.' });
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...';

    try {
        const res = await fetch('process-forgot-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ email: email })
        });
        const data = await res.json();

        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Email Terkirim!',
                text: data.message,
                confirmButtonColor: '#28a745'
            });
            emailInput.value = '';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: data.message || 'Alamat email tidak ditemukan.',
                confirmButtonColor: '#d33'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Jaringan',
            text: 'Tidak dapat terhubung ke server. Silakan coba kembali.',
            confirmButtonColor: '#d33'
        });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim Tautan Reset';
    }
});
</script>
</body>
</html>
