<?php
require_once "db.php";
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE reports SET seen = 1 WHERE id = $id");
}
?>
