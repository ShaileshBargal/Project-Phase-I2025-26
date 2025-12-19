<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once "db.php";

header("Content-Type: text/plain");

echo "=== Requests Table Schema ===\n";
$res = $conn->query("DESCRIBE requests");
if(!$res) echo "Error: ".$conn->error;
else while($r = $res->fetch_assoc()){
    print_r($r);
}

echo "\n=== Role Templates Schema ===\n";
$res = $conn->query("DESCRIBE role_templates");
if(!$res) echo "Error: ".$conn->error;
else while($r = $res->fetch_assoc()){
    print_r($r);
}

echo "\n=== Role Templates Content ===\n";
$res = $conn->query("SELECT * FROM role_templates");
if(!$res) echo "Error: ".$conn->error;
else {
    $count = 0;
    while($r = $res->fetch_assoc()){
        print_r($r);
        $count++;
    }
    echo "Total Rows: $count\n";
}

echo "\n=== Users Content (Incharges) ===\n";
$res = $conn->query("SELECT * FROM users WHERE role='teacher' OR role='incharge'");
if(!$res) echo "Error: ".$conn->error;
else {
    $count = 0;
    while($r = $res->fetch_assoc()){
        print_r($r);
        $count++;
    }
    echo "Total Rows: $count\n";
}
?>
