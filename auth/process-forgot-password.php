<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
    exit;
}

$email = sanitize(trim($_POST['email'] ?? ''));

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Alamat email tidak valid.']);
    exit;
}

try {
    $pdo = get_db();
    if (!$pdo) {
        throw new Exception("Koneksi database gagal.");
    }

    // 2. Cek apakah email terdaftar di tabel user
    $stmtUser = $pdo->prepare("SELECT id_user, username, email FROM user WHERE email = ?");
    $stmtUser->execute([$email]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Untuk keamanan, beri pesan error atau pesan generik
        echo json_encode(['status' => 'error', 'message' => 'Alamat email tidak terdaftar di sistem kami.']);
        exit;
    }

    // 3. Generate token unik (kedaluwarsa 1 jam diatur langsung oleh MySQL NOW)
    $token = bin2hex(random_bytes(32));

    // Hapus token lama untuk email ini jika ada, lalu simpan token baru menggunakan zona waktu MySQL
    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
    
    $stmtInsert = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
    $stmtInsert->execute([$email, $token]);

    // 4. Siapkan tautan reset dengan URL absolut lengkap (http://hostname/...)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base_url_full = rtrim($protocol . $host . BASE_URL, '/');
    $reset_link = $base_url_full . "/auth/reset-password.php?token=" . urlencode($token);

    // 5. Pengiriman Email menggunakan PHPMailer (Jika library tersedia di server)
    if (file_exists(__DIR__ . '/../vendor/autoload.php') || class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->setLanguage('id');
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'amandapuspita004@gmail.com';
            $mail->Password   = 'fnfyrzwajfhsyoiz'; // Ganti dengan App Password Gmail
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('amandapuspita004@gmail.com', 'SEAFOOD BATAM');
            $mail->addAddress($email, $user['username']);

            $mail->isHTML(true);
            $mail->Subject = 'Reset Kata Sandi Akun - SEAFOOD BATAM';
            $mail->Body    = '
                <div style="font-family: Arial, sans-serif; color: #333; max-width:600px; padding:20px; border:1px solid #eee; border-radius:10px;">
                    <h3 style="color:#E65100;">Halo, ' . htmlspecialchars($user['username']) . '!</h3>
                    <p>Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda di <b>SEAFOOD BATAM</b>.</p>
                    <p>Silakan klik tombol di bawah ini untuk membuat kata sandi baru (tautan berlaku selama 1 jam):</p>
                    <p style="margin:25px 0;">
                        <a href="' . $reset_link . '" style="background-color: #E65100; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight:bold; display:inline-block;">
                            Reset Kata Sandi Saya
                        </a>
                    </p>
                    <p style="font-size:0.85rem; color:#666;">Atau salin dan tempel tautan berikut di browser Anda:<br>
                    <a href="' . $reset_link . '">' . $reset_link . '</a></p>
                    <hr style="border:none; border-top:1px solid #ddd; margin:20px 0;">
                    <p style="font-size:0.8rem; color:#888;">Jika Anda tidak merasa meminta pergantian kata sandi, abaikan saja email ini. Kata sandi Anda akan tetap aman.</p>
                    <p style="font-size:0.9rem;">Salam hangat,<br><b>Tim SEAFOOD BATAM</b></p>
                </div>
            ';
            $mail->send();
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim email SMTP: ' . $mail->ErrorInfo]);
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Library PHPMailer belum terpasang di sistem.']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Tautan reset kata sandi telah berhasil dibuat dan dikirim ke alamat email Anda.'
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    exit;
}
