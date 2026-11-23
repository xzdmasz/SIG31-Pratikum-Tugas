<?php
/* ---------------------------------------------------------------------
 * process.php
 * - process.php?data=1  -> kirim semua titik sebagai GeoJSON
 * - POST (form)         -> simpan titik baru lalu kembali ke index.html
 * ------------------------------------------------------------------- */

/* Konfigurasi database */
$host = 'localhost';
$db   = 'sig31';
$user = 'root';
$pass = '';

/* true jika dipanggil untuk meminta data GeoJSON */
$mintaData = isset($_GET['data']);

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

    // Pastikan tabel points tersedia (SRID 4326 = WGS84, sama dengan Leaflet)
    $pdo->exec("CREATE TABLE IF NOT EXISTS points (
        id   INT NOT NULL AUTO_INCREMENT,
        nama VARCHAR(64) NOT NULL,
        loc  POINT NOT NULL SRID 4326,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    if ($mintaData) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Koneksi database gagal: ' . $e->getMessage()]);
        exit;
    }
    die('Koneksi database gagal: ' . $e->getMessage());
}

/* ---------------------------------------------------------------------
 * 1. Kirim data GeoJSON (dipanggil index.html saat halaman dibuka)
 * ------------------------------------------------------------------- */
if ($mintaData) {
    header('Content-Type: application/json; charset=utf-8');

    $features = [];
    // Pada MySQL, SRID 4326 memakai urutan axis latitude-longitude,
    // sehingga ST_X = latitude dan ST_Y = longitude.
    $rows = $pdo->query("SELECT id, nama, ST_X(loc) AS lat, ST_Y(loc) AS lng FROM points ORDER BY id")->fetchAll();

    foreach ($rows as $row) {
        $features[] = [
            'type'       => 'Feature',
            'properties' => [
                'id'   => (int) $row['id'],
                'nama' => $row['nama'],
                'name' => $row['nama'],
            ],
            'geometry' => [
                'type'        => 'Point',
                // GeoJSON memakai urutan [longitude, latitude]
                'coordinates' => [(float) $row['lng'], (float) $row['lat']],
            ],
        ];
    }

    echo json_encode([
        'type'     => 'FeatureCollection',
        'features' => $features,
    ]);
    exit;
}

/* ---------------------------------------------------------------------
 * 2. Simpan data dari form
 * ------------------------------------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    // Dibuka langsung tanpa POST -> balik ke halaman peta
    header('Location: index.html');
    exit;
}

// Form index.html mengirim field "nama", bukan "name"
$name = trim($_POST['nama'] ?? $_POST['name'] ?? '');
$lat  = trim($_POST['lat'] ?? '');
$lng  = trim($_POST['lng'] ?? '');

// Validasi: nama wajib (maks. 64 karakter), koordinat harus angka dan dalam rentang yang wajar
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
    header('Location: index.html?status=error&reason=' . $reason);
    exit;
}

try {
    // SRID 4326 + urutan POINT(latitude longitude), sesuai cara MySQL membacanya
    $stmt = $pdo->prepare("INSERT INTO points (nama, loc) VALUES (?, ST_GeomFromText(?, 4326))");
    $stmt->execute([$name, "POINT($lat $lng)"]);
} catch (PDOException $e) {
    // Catat error asli ke log Apache/PHP untuk mempermudah pengecekan
    error_log('Gagal menyimpan titik: ' . $e->getMessage());
    header('Location: index.html?status=error&reason=db');
    exit;
}

header('Location: index.html?status=ok');
exit;
