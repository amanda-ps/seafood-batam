<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/profile.php');
}

$user    = current_user();
$user_id = $user['id'];

$display_name = trim($_POST['display_name'] ?? '');
$username     = trim($_POST['username']     ?? '');
$bio          = trim($_POST['bio']          ?? '');
$email        = trim($_POST['email']        ?? '');
$phone        = trim($_POST['phone']        ?? '');
$new_password = $_POST['new_password']      ?? '';
$confirm_pwd  = $_POST['confirm_password']  ?? '';

$errors = [];

if (empty($username))  $errors[] = 'Nama pengguna tidak boleh kosong.';
if (empty($email))     $errors[] = 'Email tidak boleh kosong.';
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';

// Username/email uniqueness check
if (empty($errors)) {
    $all_users = get_users();
    foreach ($all_users as $u) {
        if ($u['id'] == $user_id) continue;
        if ($u['username'] === $username) { $errors[] = 'Nama pengguna sudah digunakan.'; break; }
        if ($u['email'] === $email)       { $errors[] = 'Email sudah digunakan oleh akun lain.'; break; }
    }
}

// Password validation
if (!empty($new_password)) {
    if (strlen($new_password) < 6) $errors[] = 'Kata sandi baru minimal 6 karakter.';
    elseif ($new_password !== $confirm_pwd) $errors[] = 'Konfirmasi kata sandi baru tidak cocok.';
}

if (!empty($errors)) {
    $_SESSION['edit_error'] = implode(' ', $errors);
    redirect('/profile.php?tab=edit');
}

// Avatar upload
$avatar_path = $user['avatar'] ?? '';
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $file    = $_FILES['avatar'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];

    if (!in_array($ext, $allowed)) {
        $_SESSION['edit_error'] = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WebP.';
        redirect('/profile.php?tab=edit');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        $_SESSION['edit_error'] = 'Ukuran foto maksimal 2MB.';
        redirect('/profile.php?tab=edit');
    }

    $upload_dir  = __DIR__ . '/../assets/images/avatars/';
    $new_name    = 'avatar_' . $user_id . '_' . uniqid() . '.' . $ext;
    $target_path = $upload_dir . $new_name;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        if (!empty($avatar_path) && str_contains($avatar_path, '/assets/images/avatars/')) {
            $old_path = __DIR__ . '/../' . ltrim($avatar_path, '/');
            if (file_exists($old_path)) @unlink($old_path);
        }
        $avatar_path = '/assets/images/avatars/' . $new_name;
    }
}

// Build update fields
$fields = [
    'display_name' => sanitize($display_name),
    'username'     => sanitize($username),
    'email'        => sanitize($email),
    'bio'          => sanitize($bio),
    'whatsapp'     => sanitize($phone),
    'avatar'       => $avatar_path,
];

if (!empty($new_password)) {
    $fields['password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);
}

update_user($user_id, $fields);

$_SESSION['edit_success'] = 'Profil berhasil diperbarui!';
redirect('/profile.php?tab=edit');
