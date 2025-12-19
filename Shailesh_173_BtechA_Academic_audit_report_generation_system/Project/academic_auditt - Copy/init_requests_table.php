<?php
require_once "db.php";

// Create requests table
$sql = "CREATE TABLE IF NOT EXISTS requests (
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
    echo "✓ SUCCESS: requests table created successfully!<br>";
} else {
    echo "✗ ERROR: " . $conn->error . "<br>";
}

// Verify table exists
$result = $conn->query("SHOW TABLES LIKE 'requests'");
if($result && $result->num_rows > 0){
    echo "✓ Table 'requests' exists in database<br>";
    
    // Show table structure
    $structure = $conn->query("DESCRIBE requests");
    echo "<br><strong>Table Structure:</strong><br>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while($row = $structure->fetch_assoc()){
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "✗ Table does not exist!<br>";
}
?>
