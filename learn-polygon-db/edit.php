<?php

$id = $_GET['id'];

$connection = mysqli_connect("localhost", "root", "", "sig31");

$query = "SELECT id, name, ST_AsGeoJSON(boundary) AS geojson, ST_AsText(boundary) AS wkt FROM areas WHERE id = $id LIMIT 1";

$result = mysqli_query($connection, $query);

$area = mysqli_fetch_assoc($result);
$geojson = json_decode($area['geojson'], true);

$ring = $geojson['coordinates'][0];

if(count($ring) > 1) {
    array_pop($ring);
}

$initial_coords = $ring;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Polygon</title>

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
    <form action="process-edit.php" method="post">
        <input type="hidden" name="id" id="id" value="<?= $area['id']; ?>">
        <label>Name
            <input type="text" name="name" id="name" value="<?= $area['name']; ?>">
        </label>
        <br>
        <label>Coordinates
            <textarea name="wkt_polygon" id="wkt_polygon"></textarea>
        </label>
        <br>
        <button type="submit">Submit</button>
    </form>

    <script>
        const initCoords = <?= json_encode($initial_coords); ?>;

        let center = [initCoords[0][0], initCoords[0][1]];
        var map = L.map('map').setView(center, 17);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        let markers = [];
        let polygonLayer = null;

        const wktInput = document.getElementById('wkt_polygon');

        map.on('click', function(e){
            addMarker(e.latlng.lat, e.latlng.lng);
            updatePolygon();
        });

        function loadInitialMarkers() {
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];

            initCoords.forEach(point => {
                addMarker(point[0], point[1]);
            });

            updatePolygon();
        }

        function addMarker(lat, lng) {
            const marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            markers.push(marker);

            marker.on('drag', updatePolygon);
            marker.on('dragend', updatePolygon);
            marker.on('contextmenu', function(){
                map.removeLayer(marker);
                markers = markers.filter(m => m !== marker);
                updatePolygon();
            });
        }

        function updatePolygon() {
            const coordinates = markers.map(marker => {
                const position = marker.getLatLng();
                return [position.lat, position.lng];
            });

            if(coordinates.length >= 3) {
                if(polygonLayer) { map.removeLayer(polygonLayer); }

                polygonLayer = L.polygon(coordinates).addTo(map);

                const wktPoints = coordinates.map(pt => `${pt[0]} ${pt[1]}`);
                wktPoints.push(`${coordinates[0][0]} ${coordinates[0][1]}`);
                const wktString = `POLYGON((${wktPoints.join(', ')}))`;
                wktInput.value = wktString;
            }
        }

        loadInitialMarkers();
    </script>
</body>
</html>
