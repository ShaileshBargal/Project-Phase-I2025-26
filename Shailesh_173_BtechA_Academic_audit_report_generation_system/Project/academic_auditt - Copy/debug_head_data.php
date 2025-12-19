<?php
require_once "db.php";

echo "<h2>Database Structure Check</h2>";
echo "<pre>";

// Check users table for heads
echo "=== USERS TABLE (heads with role='head') ===\n";
$result = $conn->query("SELECT id, username, email, role FROM users WHERE role='head' OR role LIKE '%head%' LIMIT 5");
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Username: {$row['username']}, Email: {$row['email']}, Role: {$row['role']}\n";
    }
} else {
    echo "No heads found in users table\n";
}

echo "\n=== ALL USERS ===\n";
$result = $conn->query("SELECT id, username, email, role FROM users LIMIT 10");
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Username: {$row['username']}, Role: {$row['role']}\n";
    }
}

echo "\n=== SAMPLE REQUESTS ===\n";
$result = $conn->query("SELECT id, head_id, requirement, role_name, status, created_at FROM requests LIMIT 5");
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Request ID: {$row['id']}, Head ID: {$row['head_id']}, Requirement: {$row['requirement']}, Status: {$row['status']}\n";
    }
} else {
    echo "No requests found\n";
}

echo "\n=== CHECK FOR heads TABLE ===\n";
$result = $conn->query("SHOW TABLES LIKE 'heads'");
if($result && $result->num_rows > 0) {
    echo "heads table EXISTS\n";
    $result2 = $conn->query("SELECT * FROM heads LIMIT 5");
    if($result2 && $result2->num_rows > 0) {
        while($row = $result2->fetch_assoc()) {
            print_r($row);
        }
    }
} else {
    echo "heads table does NOT exist in current database\n";
}

echo "</pre>";
echo "<style>body{font-family:monospace; padding:20px; background:#f5f5f5;} pre{background:white; padding:20px; border-radius:8px;}</style>";
?>
