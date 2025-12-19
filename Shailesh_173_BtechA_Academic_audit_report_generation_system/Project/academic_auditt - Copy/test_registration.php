<?php
require_once "db.php";

echo "<h1 style='color:#2563eb;'>Registration System Status Check</h1>";
echo "<style>body{font-family:sans-serif;padding:30px;background:#f8fafc;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .section{background:white;padding:20px;margin:20px 0;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}</style>";

echo "<div class='section'>";
echo "<h2>1. Users Table Structure</h2>";
$result = $conn->query("DESCRIBE users");
if($result) {
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    $has_email = false;
    $has_incharge_role = false;
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
        if($row['Field'] == 'email') $has_email = true;
        if($row['Field'] == 'incharge_role') $has_incharge_role = true;
    }
    echo "</table>";
    
    if($has_email && $has_incharge_role) {
        echo "<p class='success'>✓ Users table has all required columns</p>";
    } else {
        echo "<p class='error'>✗ Missing columns: ";
        if(!$has_email) echo "email ";
        if(!$has_incharge_role) echo "incharge_role";
        echo "</p>";
        echo "<p><strong>Action needed:</strong> Run <a href='fix_users_database.php'>fix_users_database.php</a> to add missing columns</p>";
    }
} else {
    echo "<p class='error'>✗ Error checking users table: " . $conn->error . "</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>2. Registration Files Check</h2>";
$files = [
    'register_head.php' => 'Head Registration Form',
    'register_incharge.php' => 'Incharge Registration Form'
];

foreach($files as $file => $desc) {
    if(file_exists(__DIR__ . '/' . $file)) {
        echo "<p class='success'>✓ {$desc} exists - <a href='{$file}'>Test it</a></p>";
    } else {
        echo "<p class='error'>✗ {$desc} NOT FOUND</p>";
    }
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>3. Current Users in Database</h2>";
$result = $conn->query("SELECT id, username, email, role, incharge_role FROM users LIMIT 10");
if($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Incharge Role</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['username']}</td>";
        echo "<td>" . ($row['email'] ?? 'N/A') . "</td>";
        echo "<td>{$row['role']}</td>";
        echo "<td>" . ($row['incharge_role'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found in database</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>4. Quick Actions</h2>";
echo "<ul>";
echo "<li><a href='register_head.php' style='color:#2563eb;font-weight:bold;'>→ Test Head Registration</a></li>";
echo "<li><a href='register_incharge.php' style='color:#2563eb;font-weight:bold;'>→ Test Incharge Registration</a></li>";
echo "<li><a href='fix_users_database.php' style='color:#2563eb;font-weight:bold;'>→ Fix Database Structure</a></li>";
echo "<li><a href='index.php' style='color:#2563eb;font-weight:bold;'>→ Back to Home</a></li>";
echo "</ul>";
echo "</div>";
?>
