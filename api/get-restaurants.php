<?php
/**
 * api/get-restaurants.php
 * ─────────────────────────────────────────────────────────
 * Endpoint API untuk mengambil data restoran.
 * Sumber data: MySQL (seafood_batam) — dengan fallback ke
 * data/restaurants.json jika MySQL belum dikonfigurasi.
 *
 * METHOD : GET
 * URL    : /api/get-restaurants.php
 *
 * QUERY PARAMS (opsional):
 *   q          – Pencarian teks (nama restoran, deskripsi, nama menu)
 *   district   – Filter berdasarkan kecamatan (exact match)
 *   sort       – Urutan hasil: 'rating' (default), 'reviews_count', 'name'
 *   order      – Arah urutan: 'desc' (default), 'asc'
 *   limit      – Jumlah data per halaman (default: 200, maks: 200)
 *   offset     – Mulai dari baris ke-n (default: 0)
 *   id         – Ambil satu restoran berdasarkan ID
 * ─────────────────────────────────────────────────────────
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

/* ─── Helper: kirim respons JSON dan keluar ─── */
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function clean(string $value): string {
    return trim(strip_tags($value));
}

/* ─── Parameter GET ─── */
$id       = isset($_GET['id'])       ? (int)   $_GET['id']         : 0;
$q        = isset($_GET['q'])        ? clean(  $_GET['q'])          : '';
$district = isset($_GET['district']) ? clean(  $_GET['district'])   : '';
$sort     = isset($_GET['sort'])     ? clean(  $_GET['sort'])       : 'id';
$order    = isset($_GET['order'])    ? strtolower(clean($_GET['order'])) : 'asc';
$limit    = isset($_GET['limit'])    ? min((int)$_GET['limit'], 200) : 200;
$offset   = isset($_GET['offset'])   ? max((int)$_GET['offset'], 0)  : 0;

$allowed_sorts  = ['rating', 'reviews_count', 'name', 'id'];
$allowed_orders = ['asc', 'desc'];
if (!in_array($sort, $allowed_sorts))   $sort  = 'id';
if (!in_array($order, $allowed_orders)) $order = 'asc';

/* ─── Coba koneksi MySQL; fallback ke JSON jika gagal ─── */
$use_json_fallback = false;
$pdo = null;

try {
    require_once __DIR__ . '/../includes/db.php';
    $pdo = get_db();
} catch (Throwable $e) {
    $use_json_fallback = true;
}

/* ══════════════════════════════════════════════════════════
   FALLBACK: Baca dari data/restaurants.json
══════════════════════════════════════════════════════════ */
if ($use_json_fallback || $pdo === null) {
    $json_path = __DIR__ . '/../data/restaurants.json';
    $restaurants = file_exists($json_path)
        ? (json_decode(file_get_contents($json_path), true) ?: [])
        : [];

    // Filter by id
    if ($id > 0) {
        $found = null;
        foreach ($restaurants as $r) {
            if ($r['id'] == $id) { $found = $r; break; }
        }
        if (!$found) json_response(['success' => false, 'message' => 'Restoran tidak ditemukan.'], 404);
        json_response(['success' => true, 'data' => $found]);
    }

    // Filter by q
    if ($q !== '') {
        $ql = strtolower($q);
        $restaurants = array_values(array_filter($restaurants, function($r) use ($ql) {
            if (str_contains(strtolower($r['name'] ?? ''), $ql)) return true;
            if (str_contains(strtolower($r['description'] ?? ''), $ql)) return true;
            foreach ($r['menus'] ?? [] as $m) {
                if (str_contains(strtolower($m['name'] ?? ''), $ql)) return true;
            }
            return false;
        }));
    }

    // Filter by district
    if ($district !== '') {
        $restaurants = array_values(array_filter($restaurants,
            fn($r) => ($r['district'] ?? '') === $district));
    }

    // Sort
    usort($restaurants, function($a, $b) use ($sort, $order) {
        $va = $sort === 'reviews_count' ? ($a['reviews_count'] ?? 0)
            : ($sort === 'name'         ? ($a['name'] ?? '')
            : ($sort === 'id'           ? ($a['id'] ?? 0)
                                        : ($a['rating'] ?? 0)));
        $vb = $sort === 'reviews_count' ? ($b['reviews_count'] ?? 0)
            : ($sort === 'name'         ? ($b['name'] ?? '')
            : ($sort === 'id'           ? ($b['id'] ?? 0)
                                        : ($b['rating'] ?? 0)));
        return $order === 'asc' ? ($va <=> $vb) : ($vb <=> $va);
    });

    $total       = count($restaurants);
    $restaurants = array_slice($restaurants, $offset, $limit);

    // Normalize: tambah avg_rating, total_reviews, lat, lng aliases
    foreach ($restaurants as &$r) {
        $r['avg_rating']    = $r['rating']        ?? 0;
        $r['total_reviews'] = $r['reviews_count'] ?? 0;
        $r['lat']           = $r['latitude']      ?? $r['lat'] ?? null;
        $r['lng']           = $r['longitude']     ?? $r['lng'] ?? null;
    }
    unset($r);

    json_response([
        'success' => true,
        'source'  => 'json',
        'total'   => $total,
        'count'   => count($restaurants),
        'limit'   => $limit,
        'offset'  => $offset,
        'data'    => $restaurants,
    ]);
}

/* ─── Koneksi ke database MySQL (sudah berhasil di atas) ─── */
require_once __DIR__ . '/../includes/functions.php';

try {
    /* ══════════════════════════════════════════════════════════
       CASE 1: Ambil satu restoran berdasarkan ID
    ══════════════════════════════════════════════════════════ */
    if ($id > 0) {
        $restaurant = get_restaurant($id);
        if (!$restaurant) {
            json_response(['success' => false, 'message' => 'Restoran tidak ditemukan.'], 404);
        }
        $restaurant['avg_rating']    = $restaurant['rating'] ?? 0;
        $restaurant['total_reviews'] = $restaurant['reviews_count'] ?? 0;
        $restaurant['lat']           = $restaurant['latitude'] ?? $restaurant['lat'] ?? null;
        $restaurant['lng']           = $restaurant['longitude'] ?? $restaurant['lng'] ?? null;
        json_response(['success' => true, 'data' => $restaurant]);
    }

    /* ══════════════════════════════════════════════════════════
       CASE 2: Ambil daftar restoran (dengan filter & paginasi)
    ══════════════════════════════════════════════════════════ */
    $all_restaurants = get_restaurants();

    // Filter by q
    if ($q !== '') {
        $ql = strtolower($q);
        $all_restaurants = array_values(array_filter($all_restaurants, function($r) use ($ql) {
            if (str_contains(strtolower($r['name'] ?? ''), $ql)) return true;
            if (str_contains(strtolower($r['description'] ?? ''), $ql)) return true;
            foreach ($r['menus'] ?? [] as $m) {
                if (str_contains(strtolower($m['name'] ?? ''), $ql)) return true;
            }
            return false;
        }));
    }

    // Filter by district
    if ($district !== '') {
        $all_restaurants = array_values(array_filter($all_restaurants,
            fn($r) => ($r['district'] ?? '') === $district));
    }

    // Sort
    usort($all_restaurants, function($a, $b) use ($sort, $order) {
        $va = $sort === 'reviews_count' ? ($a['reviews_count'] ?? 0)
            : ($sort === 'name'         ? ($a['name'] ?? '')
            : ($sort === 'id'           ? ($a['id'] ?? 0)
                                        : ($a['rating'] ?? 0)));
        $vb = $sort === 'reviews_count' ? ($b['reviews_count'] ?? 0)
            : ($sort === 'name'         ? ($b['name'] ?? '')
            : ($sort === 'id'           ? ($b['id'] ?? 0)
                                        : ($b['rating'] ?? 0)));
        return $order === 'asc' ? ($va <=> $vb) : ($vb <=> $va);
    });

    $total       = count($all_restaurants);
    $restaurants = array_slice($all_restaurants, $offset, $limit);

    foreach ($restaurants as &$r) {
        $r['avg_rating']    = $r['rating']        ?? 0;
        $r['total_reviews'] = $r['reviews_count'] ?? 0;
        $r['lat']           = $r['latitude']      ?? $r['lat'] ?? null;
        $r['lng']           = $r['longitude']     ?? $r['lng'] ?? null;
    }
    unset($r);

    json_response([
        'success' => true,
        'source'  => 'mysql',
        'total'   => $total,
        'count'   => count($restaurants),
        'limit'   => $limit,
        'offset'  => $offset,
        'data'    => $restaurants,
    ]);
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => 'Gagal mengambil data dari database: ' . $e->getMessage()], 500);
}
