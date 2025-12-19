<?php
require_once "db.php";
$res = $conn->query("SELECT COUNT(*) as cnt FROM requirements_list");
if($res){
    $cnt = $res->fetch_assoc()['cnt'];
    echo "Requirements in DB: $cnt";
} else {
    echo "Error: " . $conn->error;
}
?>
