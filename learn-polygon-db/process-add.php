<?php

$connection = mysqli_connect("localhost", "root", "", "sig31");

$name = $_POST['name'];
$wkt_polygon = $_POST['wkt_polygon'];

$query = "INSERT INTO areas (name, boundary) VALUES ('$name', ST_GeomFromText('$wkt_polygon'))";

mysqli_query($connection, $query);

mysqli_close($connection);

header("Location:read-all.php");
exit();

?>
