<?php
require_once "db.php";

echo "<h2>Checking Submitted Reports System</h2>";
echo "<pre>";

// Create submitted_reports table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS submitted_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    incharge_name VARCHAR(100) NOT NULL,
    incharge_role VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    semester VARCHAR(10) NOT NULL,
    year VARCHAR(10) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    remarks TEXT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL
)";

if($conn->query($sql)) {
    echo "✓ submitted_reports table exists or created successfully!\n\n";
} else {
    echo "✗ Error: " . $conn->error . "\n\n";
}

// Check table structure
echo "Table Structure:\n";
$result = $conn->query("DESCRIBE submitted_reports");
if($result) {
    while($row = $result->fetch_assoc()) {
        echo sprintf("  %-20s %-20s %s\n", $row['Field'], $row['Type'], $row['Null']);
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n-----------------------------------\n\n";

// Count reports
$result = $conn->query("SELECT COUNT(*) as count FROM submitted_reports");
if($result) {
    $count = $result->fetch_assoc()['count'];
    echo "Total Submitted Reports: $count\n\n";
}

// Show recent reports
echo "Recent Reports:\n";
$result = $conn->query("SELECT id, incharge_name, incharge_role, title, status, semester, year, submitted_at FROM submitted_reports ORDER BY submitted_at DESC LIMIT 5");
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo sprintf("  #%d - %s (%s) - %s [%s %s] - %s\n", 
            $row['id'], 
            $row['incharge_name'],
            $row['incharge_role'],
            $row['title'],
            $row['semester'],
            $row['year'],
            $row['status']
        );
    }
} else {
    echo "  No reports yet.\n";
}

echo "\n-----------------------------------\n";
echo "\n<strong style='color:green;'>✓ System Ready!</strong>\n";
echo "\nApproved requests will automatically appear in Submitted Reports.\n";

echo "</pre>";
echo "<style>body{font-family:monospace; padding:20px; background:#f5f5f5;} pre{background:white; padding:20px; border-radius:8px; border:1px solid #ddd;}</style>";
?>
