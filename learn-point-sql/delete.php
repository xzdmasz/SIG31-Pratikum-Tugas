<?php

$connection = mysqli_connect('localhost', 'root', '', 'sig31');

$id = intval($_GET['id'] ?? 0);

if ($id > 0 ) {
    $query = "DELETE FROM points WHERE id = $id";
    mysqli_query($connection, $query);
}

mysqli_close($connection);
header('Location: read.php');
exit()

?>