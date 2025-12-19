<?php
require_once "db.php";

// Check if table exists and has data
$result = $conn->query("SELECT COUNT(*) as count FROM submitted_reports");
$count = $result->fetch_assoc()['count'];
echo "Total records in submitted_reports: $count<br><br>";

// Show all records
$result = $conn->query("SELECT * FROM submitted_reports ORDER BY submitted_at DESC LIMIT 10");
echo "<h3>Recent Submitted Reports:</h3>";
if($result->num_rows > 0){
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Incharge</th><th>Role</th><th>Title</th><th>Semester</th><th>Year</th><th>Status</th></tr>";
    while($row = $result->fetch_assoc()){
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['incharge_name']}</td>";
        echo "<td>{$row['incharge_role']}</td>";
        echo "<td>{$row['title']}</td>";
        echo "<td>{$row['semester']}</td>";
        echo "<td>{$row['year']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange'>No records found. The table is empty.</p>";
    echo "<p>This is expected if you haven't approved any requests yet.</p>";
}
?>
