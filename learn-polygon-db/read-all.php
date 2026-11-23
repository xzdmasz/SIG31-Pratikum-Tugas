<?php

$connection = mysqli_connect("localhost", "root", "", "sig31");

$query = "SELECT id, name, ST_AsGeoJSON(boundary) AS geojson FROM areas";

$result = mysqli_query($connection, $query);

$areas = [];

while($row = mysqli_fetch_assoc($result)){
    $row['geojson'] = json_decode($row['geojson'], true);
    $areas[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Read All Polygons</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <style>
        #map {
            width: 640px;
            height: 360px;
        }
    </style>
</head>
<body>
    <div id="map"></div>
    <?php foreach($areas as $area):?>
        <?= $area['name']; ?>
        <a href="edit.php?id=<?= $area['id']; ?>">Edit</a>
        <br>
    <?php endforeach;?>
    <a href="add.html">Add New Polygon</a>

    <script>
        let center = [-0.055495094034760356, 109.34949626784648];
        var map = L.map('map').setView(center, 17);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        const areas = <?php echo json_encode($areas); ?>;

        const polygonGroup = L.featureGroup();

        areas.forEach(function(area){
            const layer = L.geoJSON(area.geojson, {
                coordsToLatLng: function(coords) {
                    return new L.LatLng(coords[0], coords[1]);
                }
            });

            layer.bindPopup(area.name);
            polygonGroup.addLayer(layer);
        });

        polygonGroup.addTo(map);
    </script>
</body>
</html>
