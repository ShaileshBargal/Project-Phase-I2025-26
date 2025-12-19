<?php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = ''; // <-- if phpMyAdmin asks for password, put it here
$DB_NAME = 'academic_auditt';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>
