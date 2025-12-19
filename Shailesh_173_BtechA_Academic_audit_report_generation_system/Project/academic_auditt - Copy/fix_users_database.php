<?php
require_once "db.php";

echo "<h1>Fixing Users Table Structure</h1>";
echo "<pre>";

// Check current structure
echo "Current table structure:\n";
$result = $conn->query("DESCRIBE users");
if($result) {
    while($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
}

echo "\n-----------------------------------\n\n";

// Add email column if it doesn't exist
echo "Adding 'email' column...\n";
$sql1 = "ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE NULL AFTER username";
if($conn->query($sql1)) {
    echo "✓ Email column added successfully!\n";
} else {
    if(strpos($conn->error, "Duplicate column") !== false) {
        echo "✓ Email column already exists.\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
}

// Add incharge_role column if it doesn't exist
echo "\nAdding 'incharge_role' column...\n";
$sql2 = "ALTER TABLE users ADD COLUMN incharge_role VARCHAR(100) NULL AFTER role";
if($conn->query($sql2)) {
    echo "✓ Incharge_role column added successfully!\n";
} else {
    if(strpos($conn->error, "Duplicate column") !== false) {
        echo "✓ Incharge_role column already exists.\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
}

// Modify role column to allow more values
echo "\nModifying 'role' column to VARCHAR...\n";
$sql3 = "ALTER TABLE users MODIFY COLUMN role VARCHAR(50) DEFAULT 'teacher'";
if($conn->query($sql3)) {
    echo "✓ Role column modified successfully!\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
}

echo "\n-----------------------------------\n\n";

// Show final structure
echo "Final table structure:\n";
$result = $conn->query("DESCRIBE users");
if($result) {
    while($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']}) {$row['Null']} {$row['Key']}\n";
    }
}

echo "\n-----------------------------------\n";
echo "\n<strong style='color:green; font-size:18px;'>✓ Database fix complete!</strong>\n";
echo "\nYou can now use the registration pages:\n";
echo "- <a href='register_head.php'>Register Head</a>\n";
echo "- <a href='register_incharge.php'>Register Incharge</a>\n";

echo "</pre>";

echo "<style>body{font-family:monospace; padding:20px; background:#f5f5f5;} pre{background:white; padding:20px; border-radius:8px; border:1px solid #ddd;}</style>";
?>
