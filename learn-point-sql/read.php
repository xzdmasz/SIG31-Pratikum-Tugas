<?php
// Membuka koneksi ke database
$connection = mysqli_connect("localhost", "root", "", "sig31");

// Mempersiapkan query untuk ambil data dari tabel
$query = "SELECT id, nama, nama AS name, ST_X(loc) AS lat, ST_Y(loc) AS lng FROM points";

// Mengambil semua baris di tabel
$result = mysqli_query($connection, $query);

// Simpan semua baris ke dalam array
$points = [];
while ($row = mysqli_fetch_assoc($result)) {
    $points[] = $row;
}
    
// Tutup kembali koneksi database
mysqli_close($connection);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map Points</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        html, body { width: 75%; height: 100%; margin: 0; }
        #map { width: 100%; height: 500px; }
    </style>
</head>
<body>
    <div id="map"></div>
    <!-- Menampilkan semua data dari PHP -->
    <?php foreach ($points as $pt) : ?>
        <div>
            <?php echo $pt['nama']; ?>
            <a href="edit.php?id=<?php echo $pt['id']; ?>">View</a>
            <a href="delete.php?id=<?php echo $pt['id']; ?>">Delete</a>
        </div>
    <?php endforeach; ?>
    <a href="create.php">Add New Point</a>
    <script>
        let center = [-0.04078400978905354, 109.33484041034588]; //[cite: 1]
        var map = L.map('map').setView(center, 15); //[cite: 1]

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map); //[cite: 1]

        // Mengkoversi array PHP ke dalam JSON JS
        const markersJSON = <?= json_encode($points); ?>; //[cite: 1]
        // Siapkan Array JS Kosong
        const bounds = []; //[cite: 1]

        markersJSON.forEach(function (item) {
            const lat = parseFloat(item.lat); //[cite: 1]
            const lng = parseFloat(item.lng); //[cite: 1]

            if (!isNaN(lat) && !isNaN(lng)) { //[cite: 1]
                // L.marker menerima format [Latitude, Longitude]
                const marker = L.marker([lat, lng]).addTo(map); //[cite: 1]
                marker.bindPopup(`<b>${item.nama}</b>`);
                
                bounds.push([lat, lng]); //[cite: 1]
            }
        });

        // Zoom otomatis ke seluruh titik marker
        if (bounds.length > 0) {
            map.fitBounds(bounds);
        }
    </script>
</body>
</html>