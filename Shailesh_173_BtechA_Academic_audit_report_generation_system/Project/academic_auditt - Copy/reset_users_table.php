<?php
require_once "db.php";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Drop existing users table
$conn->query("SET FOREIGN_KEY_CHECKS = 0"); // Disable FK checks to avoid errors with reports table
$sql_drop = "DROP TABLE IF EXISTS users";
if ($conn->query($sql_drop) === TRUE) {
    echo "Table 'users' dropped successfully.\n";
} else {
    echo "Error dropping table: " . $conn->error . "\n";
}

// Recreate users table with proper schema
$sql_create = "CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    incharge_role VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('teacher', 'head') NOT NULL DEFAULT 'teacher',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_create) === TRUE) {
    echo "Table 'users' created successfully with new schema.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1"); // Re-enable FK checks

echo "Users table reset complete.\n";
?>
