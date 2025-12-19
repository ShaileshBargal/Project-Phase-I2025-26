<?php
// Test POST request handler
require_once "db.php";

// Simulate a request
$_POST['action'] = 'send_request';
$_POST['requirement'] = 'Test Requirement';
$_POST['role_id'] = 1;
$_POST['role_name'] = 'Test Role';
$_POST['semester'] = 'odd';

$head_id = 1; // Test head ID

echo "Testing send_request handler...<br><br>";

$requirement = $_POST['requirement'] ?? '';
$role_id = intval($_POST['role_id'] ?? 0);
$role_name = $_POST['role_name'] ?? '';
$semester = $_POST['semester'] ?? 'odd';
$year = '2025';

echo "Data received:<br>";
echo "- Requirement: $requirement<br>";
echo "- Role ID: $role_id<br>";
echo "- Role Name: $role_name<br>";
echo "- Semester: $semester<br>";
echo "- Year: $year<br>";
echo "- Head ID: $head_id<br><br>";

if(!empty($requirement) && !empty($role_name)){
    $stmt = $conn->prepare("INSERT INTO requests (head_id, requirement, role_id, role_name, semester, year, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    
    if(!$stmt){
        echo "✗ ERROR preparing statement: " . $conn->error . "<br>";
    } else {
        $stmt->bind_param("isissss", $head_id, $requirement, $role_id, $role_name, $semester, $year);
        
        if($stmt->execute()){
            echo "✓ SUCCESS: Request inserted! ID: " . $stmt->insert_id . "<br>";
            echo json_encode(['ok'=>true, 'msg'=>'Request sent successfully!']);
        } else {
            echo "✗ ERROR executing: " . $stmt->error . "<br>";
            echo json_encode(['ok'=>false, 'msg'=>'Failed to send request']);
        }
    }
} else {
    echo "✗ ERROR: Invalid request data<br>";
    echo json_encode(['ok'=>false, 'msg'=>'Invalid request data']);
}
?>
