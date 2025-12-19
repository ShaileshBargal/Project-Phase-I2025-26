<?php
require_once "db.php";

// Disable foreign key checks to allow truncation
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$t1 = $conn->query("TRUNCATE TABLE role_templates");
$t2 = $conn->query("TRUNCATE TABLE role_history");

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

if($t1 && $t2){
    echo "<h2 style='color:green'>✓ SUCCESS: All roles and history deleted.</h2>";
    echo "<p>You can now define roles from scratch.</p>";
    echo "<a href='manage_incharge.php'>Go back to Manage Incharge</a>";
} else {
    echo "<h2 style='color:red'>✗ Error: " . $conn->error . "</h2>";
}
?>
