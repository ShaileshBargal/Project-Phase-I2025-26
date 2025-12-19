<?php
$host = "localhost";
$user = "root"; // your MySQL username
$pass = "Shailesh19@#";     // your MySQL password
$dbname = "head_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
