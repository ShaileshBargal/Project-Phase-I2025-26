<?php
require_once "db.php";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create submitted_reports table
$sql = "CREATE TABLE IF NOT EXISTS submitted_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    incharge_name VARCHAR(100) NOT NULL,
    incharge_role VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    semester VARCHAR(10) NOT NULL,
    year VARCHAR(10) NOT NULL,
    status ENUM('approved', 'rejected') DEFAULT 'approved',
    remarks TEXT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY(teacher_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'submitted_reports' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

echo "Database setup complete!";
?>
