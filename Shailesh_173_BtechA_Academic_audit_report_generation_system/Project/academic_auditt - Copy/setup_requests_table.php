<?php
require_once "db.php";

echo "<h2>Checking and Creating Requests Table</h2>";

// Drop table if it exists (fresh start)
$conn->query("DROP TABLE IF EXISTS requests");
echo "✓ Cleared old requests table<br>";

// Create table with correct structure
$sql = "CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    head_id INT NOT NULL,
    requirement VARCHAR(255) NOT NULL,
    role_id INT NULL,
    role_name VARCHAR(191) NOT NULL,
    semester VARCHAR(10) NOT NULL,
    year VARCHAR(10) DEFAULT '2025',
    status VARCHAR(50) DEFAULT 'pending',
    document_path VARCHAR(255) NULL,
    incharge_id INT NULL,
    uploaded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (head_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES role_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (incharge_id) REFERENCES users(id) ON DELETE SET NULL
)";

if($conn->query($sql)){
    echo "✓ <strong style='color:green;'>SUCCESS: Requests table created!</strong><br><br>";
} else {
    echo "✗ <strong style='color:red;'>ERROR: " . $conn->error . "</strong><br><br>";
    exit;
}

// Verify structure
$result = $conn->query("DESCRIBE requests");
echo "<h3>Table Structure:</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
echo "<tr style='background:#f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while($row = $result->fetch_assoc()){
    echo "<tr>";
    echo "<td><strong>{$row['Field']}</strong></td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><br><h3 style='color:green;'>✓ All Done! Table is ready for use.</h3>";
echo "<p><a href='send_request.php'>Go to Send Request</a> | <a href='teacher.php'>Go to Teacher Panel</a></p>";
?>
