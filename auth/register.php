<?php
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) redirect('/profile.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $display_name = sanitize($_POST['display_name'] ?? '');
    $username     = sanitize($_POST['username'] ?? '');
    $email        = sanitize($_POST['email'] ?? '');
    $phone        = sanitize($_POST['phone'] ?? '');
    $gender       = in_array($_POST['gender'] ?? '', ['male','female']) ? $_POST['gender'] : '';
    $password     = $_POST['password'] ?? '';
    $confirm      = $_POST['confirm_password'] ?? '';
    $agree        = isset($_POST['agree']);

    if (!$display_name || !$username || !$email || !$password) {
        $error = 'Harap isi semua kolom wajib.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } elseif (!$agree) {
        $error = 'Anda harus menyetujui Syarat & Ketentuan.';
    } else {
        $exists = false;
        try {
            $pdo = get_db();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT id_user FROM user WHERE email = ? OR username = ?");
                $stmt->execute([$email, $username]);
                if ($stmt->fetchColumn()) {
                    $exists = true;
                }
            }
        } catch (Throwable $e) {}

        $users = get_users();
        if (!$exists) {
            foreach ($users as $u) {
                if ($u['email'] === $email || $u['username'] === $username) {
                    $exists = true; break;
                }
            }
        }

        if ($exists) {
            $error = 'Email atau Nama Pengguna sudah digunakan.';
        } else {
            $db_gender = ($gender === 'male') ? 'Laki-laki' : (($gender === 'female') ? 'Perempuan' : '');
            $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                if (isset($pdo) && $pdo) {
                    $ins = $pdo->prepare("
                        INSERT INTO user (username, nama_lengkap, nohp, jenis_kelamin, email, password, status, role, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 'aktif', 'user', NOW())
                    ");
                    $ins->execute([$username, $display_name, $phone, $db_gender, $email, $hashed_pw]);
                }
            } catch (Throwable $e) {}

            $new_id = empty($users) ? 1 : max(array_column($users, 'id')) + 1;
            $users[] = [
                'id'           => $new_id,
                'display_name' => $display_name,
                'username'     => $username,
                'email'        => $email,
                'password_hash'=> $hashed_pw,
                'whatsapp'     => $phone,
                'gender'       => $gender,
                'role'         => 'user',
                'status'       => 'active',
            ];
            write_json('users.json', $users);
            $_SESSION['success'] = 'Pendaftaran berhasil! Silakan masuk.';
            redirect('/auth/login.php');
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
    <title>Daftar Akun — Seafood Batam</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
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
        box-shadow: 0 20px 60px rgba(0,0,0,0.12);
        width: 100%;
        max-width: 520px;
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
    .auth-subtitle { font-size: 0.875rem; color: var(--text-muted); margin-bottom: 24px; }

    .pw-wrap { position: relative; }
    .pw-wrap .form-control { padding-right: 44px; }
    .pw-eye { position: absolute; right: 13px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 0.9rem; }

    /* Gender pills */
    .gender-pills { display: flex; gap: 10px; }
    .gender-pill-input { display: none; }
    .gender-pill-label {
        flex: 1; text-align: center; padding: 9px 16px; border-radius: 50px;
        border: 1.5px solid var(--border-strong); color: var(--text-secondary);
        font-family: 'Poppins', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer;
        transition: all 0.2s ease;
    }
    .gender-pill-input:checked + .gender-pill-label {
        background: var(--clr-green); border-color: var(--clr-green); color: #FFFFFF;
    }

    /* Password strength bar */
    .strength-bar-wrap { height: 4px; background: var(--border); border-radius: 4px; margin-top: 8px; }
    .strength-bar { height: 100%; border-radius: 4px; width: 0%; transition: width 0.3s ease, background 0.3s ease; }

    .auth-or { display:flex; align-items:center; gap:14px; margin:18px 0; color:var(--text-muted); font-size:0.82rem; font-family:'Poppins',sans-serif; }
    .auth-or::before, .auth-or::after { content:''; flex:1; height:1px; background:var(--border); }
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

            <h1 class="auth-title">Daftar</h1>
            <p class="auth-subtitle">Buat akun baru di Seafood Batam</p>

            <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom:18px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="reg-form" novalidate>
                <div style="display:flex; flex-direction:column; gap:16px;">

                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="reg-display">Nama Lengkap *</label>
                        <input type="text" name="display_name" id="reg-display" class="form-control"
                               placeholder="Nama Lengkap Anda"
                               value="<?= htmlspecialchars($_POST['display_name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="reg-username">Nama Pengguna *</label>
                        <input type="text" name="username" id="reg-username" class="form-control"
                               placeholder="username_anda"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="reg-email">Email *</label>
                        <input type="email" name="email" id="reg-email" class="form-control"
                               placeholder="email@example.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               autocomplete="email" required>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="reg-phone">No. HP</label>
                        <input type="text" name="phone" id="reg-phone" class="form-control"
                               placeholder="+62 812 xxxx xxxx"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>

                    <!-- Gender pills -->
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Jenis Kelamin</label>
                        <div class="gender-pills">
                            <input type="radio" name="gender" id="g-male" value="male"
                                   class="gender-pill-input"
                                   <?= ($_POST['gender']??'') === 'male' ? 'checked' : '' ?>>
                            <label for="g-male" class="gender-pill-label">♂ Laki-laki</label>

                            <input type="radio" name="gender" id="g-female" value="female"
                                   class="gender-pill-input"
                                   <?= ($_POST['gender']??'') === 'female' ? 'checked' : '' ?>>
                            <label for="g-female" class="gender-pill-label">♀ Perempuan</label>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="reg-password">Kata Sandi * <span id="strength-label" style="font-weight:400; font-size:0.75rem; color:var(--text-muted);"></span></label>
                        <div class="pw-wrap">
                            <input type="password" name="password" id="reg-password" class="form-control"
                                   placeholder="Min. 6 karakter" autocomplete="new-password"
                                   oninput="updateStrength(this.value)" required>
                            <button type="button" class="pw-eye" onclick="togglePwd('reg-password','eye2')">
                                <i class="fa-solid fa-eye" id="eye2"></i>
                            </button>
                        </div>
                        <div class="strength-bar-wrap">
                            <div class="strength-bar" id="strength-bar"></div>
                        </div>
                    </div>

                    <!-- Confirm -->
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="reg-confirm">Konfirmasi Kata Sandi *</label>
                        <div class="pw-wrap">
                            <input type="password" name="confirm_password" id="reg-confirm" class="form-control"
                                   placeholder="Ulangi kata sandi" autocomplete="new-password" required>
                            <button type="button" class="pw-eye" onclick="togglePwd('reg-confirm','eye3')">
                                <i class="fa-solid fa-eye" id="eye3"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Terms checkbox -->
                    <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; font-size:0.83rem; color:var(--text-secondary); font-family:'Poppins',sans-serif; line-height:1.55;">
                        <input type="checkbox" name="agree" style="margin-top:2px; accent-color:var(--clr-green);" required>
                        Saya menyetujui 
                        <a href="#" style="color:var(--clr-orange); font-weight:700; margin-left:4px;">Syarat &amp; Ketentuan</a>
                        &nbsp;serta&nbsp;
                        <a href="#" style="color:var(--clr-orange); font-weight:700;">Kebijakan Privasi</a>
                    </label>

                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">
                        <i class="fa-solid fa-user-plus"></i> Daftar
                    </button>
                </div>
            </form>

            <div class="auth-or">atau</div>

            <p style="text-align:center; font-size:0.875rem; color:var(--text-muted); font-family:'Poppins',sans-serif;">
                Sudah memiliki akun?
                <a href="<?= BASE_URL ?>/auth/login.php" style="color:var(--clr-orange); font-weight:700; text-decoration:underline; margin-left:4px;">Masuk di sini.</a>
            </p>
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

document.addEventListener('DOMContentLoaded', function() {
    const regForm = document.getElementById('reg-form');
    if (regForm) {
        regForm.addEventListener('submit', function(e) {
            if (!regForm.checkValidity()) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Pendaftaran Gagal',
                    text: 'Mohon lengkapi semua data',
                    confirmButtonColor: 'var(--clr-green)'
                });
            }
        });
    }
});

(function(){
    const t = document.cookie.split(';').map(c=>c.trim()).find(c=>c.startsWith('theme='));
    if (t) document.documentElement.setAttribute('data-theme', t.split('=')[1]);
})();
</script>
<script>
    const BASE_URL = '<?= BASE_URL ?>';
    window.BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
