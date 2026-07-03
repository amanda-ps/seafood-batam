<?php
/**
 * db.php — Koneksi database MySQL untuk seafood_batam
 * Gunakan: require_once __DIR__ . '/db.php';
 *          $pdo = get_db();
 */

define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'seafood_batam');
define('DB_USER',    'root');       // ganti sesuai user MySQL kamu
define('DB_PASS',    '');           // ganti sesuai password MySQL kamu
define('DB_CHARSET', 'utf8mb4');

/**
 * Mengembalikan instance PDO (singleton).
 */
function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Jangan tampilkan detail error di production
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Koneksi database gagal.',
                'error'   => $e->getMessage(), // hapus baris ini di production
            ]);
            exit;
        }
    }

    return $pdo;
}
