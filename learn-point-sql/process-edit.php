<?php
/* ---------------------------------------------------------------------
 * process-edit.php
 * - POST (form edit) -> ubah titik lalu kembali ke edit.php
 * ------------------------------------------------------------------- */

/* Konfigurasi database */
$host = 'localhost';
$db   = 'sig31';
$user = 'root';
$pass = '';

/* Id titik yang diedit */
$id = (int) ($_POST['id'] ?? 0);

/* Harus dipanggil lewat POST dan membawa id */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !$id) {
    header('Location: read.php');
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . $e->getMessage());
}

/* Ambil data dari form edit.php */
$name = trim($_POST['nama'] ?? '');
$lat  = trim($_POST['lat'] ?? '');
$lng  = trim($_POST['lng'] ?? '');

/* Validasi: nama wajib (maks. 64 karakter), koordinat harus angka dan dalam rentang yang wajar */
$valid = $name !== ''
    && mb_strlen($name) <= 64
    && is_numeric($lat) && is_numeric($lng)
    && (float) $lat >= -90 && (float) $lat <= 90
    && (float) $lng >= -180 && (float) $lng <= 180;

if (!$valid) {
    // Kirim alasan kegagalan supaya pesan di halaman lebih jelas
    if ($name === '') {
        $reason = 'nama';
    } elseif (mb_strlen($name) > 64) {
        $reason = 'nama_panjang';
    } else {
        $reason = 'koordinat';
    }
    header('Location: edit.php?id=' . $id . '&status=error&reason=' . $reason);
    exit;
}

try {
    // SRID 4326 + urutan POINT(latitude longitude), sesuai cara MySQL membacanya
    $stmt = $pdo->prepare("UPDATE points SET nama = ?, loc = ST_GeomFromText(?, 4326) WHERE id = ?");
    $stmt->execute([$name, "POINT($lat $lng)", $id]);
} catch (PDOException $e) {
    // Catat error asli ke log Apache/PHP untuk mempermudah pengecekan
    error_log('Gagal mengubah titik: ' . $e->getMessage());
    header('Location: edit.php?id=' . $id . '&status=error&reason=db');
    exit;
}

header('Location: edit.php?id=' . $id . '&status=ok');
exit;
