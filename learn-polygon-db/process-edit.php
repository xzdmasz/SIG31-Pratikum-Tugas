<?php

$connection = mysqli_connect("localhost", "root", "", "sig31");

$id = $_POST['id'];
$name = $_POST['name'];
$wkt_polygon = $_POST['wkt_polygon'];

$query = "UPDATE areas SET name = '$name', boundary = ST_GeomFromText('$wkt_polygon') WHERE id = $id";

mysqli_query($connection, $query);

mysqli_close($connection);

header("Location:read-all.php");
exit();

?>
