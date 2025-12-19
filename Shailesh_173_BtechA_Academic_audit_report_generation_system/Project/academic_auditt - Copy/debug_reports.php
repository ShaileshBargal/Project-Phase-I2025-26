<?php
require_once "db.php";

echo "<h2>Checking Reports Table</h2>";

// Check if reports table exists
$result = $conn->query("SHOW TABLES LIKE 'reports'");
if($result->num_rows > 0){
    echo "<p style='color:green;'>✓ Reports table EXISTS</p>";
    
    // Show structure
    $structure = $conn->query("DESCRIBE reports");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th></tr>";
    while($row = $structure->fetch_assoc()){
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>✗ Reports table DOES NOT EXIST!</p>";
}
?>
