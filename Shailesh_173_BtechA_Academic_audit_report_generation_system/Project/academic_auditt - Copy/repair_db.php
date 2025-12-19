<?php
require_once "db.php";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add email column
$sql = "ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE NOT NULL AFTER username";
if ($conn->query($sql) === TRUE) {
    echo "Column 'email' added successfully.<br>";
} else {
    echo "Error adding 'email': " . $conn->error . "<br>";
}

// Add incharge_role column
$sql = "ALTER TABLE users ADD COLUMN incharge_role VARCHAR(100) NOT NULL AFTER email";
if ($conn->query($sql) === TRUE) {
    echo "Column 'incharge_role' added successfully.<br>";
} else {
    echo "Error adding 'incharge_role': " . $conn->error . "<br>";
}

echo "Database repair complete. Please delete this file after use.";
?>
