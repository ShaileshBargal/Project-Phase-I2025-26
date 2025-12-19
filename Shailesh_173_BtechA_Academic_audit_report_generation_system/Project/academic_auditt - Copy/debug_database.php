<?php
require_once "db.php";

echo "<h2>Debug: Checking Database and Table</h2>";

// Show current database
$result = $conn->query("SELECT DATABASE()");
$db = $result->fetch_row()[0];
echo "<p><strong>Current Database:</strong> $db</p>";

// Check if requests table exists
$result = $conn->query("SHOW TABLES LIKE 'requests'");
if($result->num_rows > 0){
    echo "<p style='color:green;'>✓ Requests table EXISTS</p>";
    
    // Show structure
    $structure = $conn->query("DESCRIBE requests");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th></tr>";
    while($row = $structure->fetch_assoc()){
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
    
    // Count rows
    $count = $conn->query("SELECT COUNT(*) as c FROM requests")->fetch_assoc()['c'];
    echo "<p>Total requests in table: <strong>$count</strong></p>";
    
} else {
    echo "<p style='color:red;'>✗ Requests table DOES NOT EXIST!</p>";
    echo "<p><a href='setup_requests_table.php'>Click here to create it</a></p>";
}

// Test the actual query from teacher.php
echo "<hr><h3>Testing Teacher.php Query:</h3>";
$test_role = 'HOD';
$test_semester = 'odd';

try {
    $stmt = $conn->prepare("
        SELECT r.*, u.username as head_name 
        FROM requests r
        LEFT JOIN users u ON r.head_id = u.id
        WHERE r.role_name = ? AND r.semester = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param("ss", $test_role, $test_semester);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<p style='color:green;'>✓ Query executed successfully!</p>";
    echo "<p>Found {$result->num_rows} requests for role '$test_role' in '$test_semester' semester</p>";
    
} catch(Exception $e) {
    echo "<p style='color:red;'>✗ Query FAILED: " . $e->getMessage() . "</p>";
}
?>
