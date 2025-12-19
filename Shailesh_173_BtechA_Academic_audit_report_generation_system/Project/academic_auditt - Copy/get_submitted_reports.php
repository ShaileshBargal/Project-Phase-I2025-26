<?php
session_start();
require_once "db.php";

// Return JSON header
header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['head_id']) || ($_SESSION['role'] ?? '') !== 'head') {
    echo json_encode(['ok'=>false, 'msg'=>'Unauthorized']);
    exit;
}

// Get parameters
$semester = $_GET['semester'] ?? 'odd';
$year = $_GET['year'] ?? '2024';

try {
    $q = "SELECT * FROM submitted_reports 
          WHERE semester=? AND year=?
          ORDER BY submitted_at DESC";
    
    $stmt = $conn->prepare($q);
    if(!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    
    $stmt->bind_param("ss", $semester, $year);
    if(!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
    
    $result = $stmt->get_result();
    
    $reports = [];
    while($row = $result->fetch_assoc()){
        // Check for file existence to provide correct path
        $filename = $row['filename'];
        $path1 = "uploads/" . $filename;
        $path2 = "uploads/requests/" . $filename;
        
        if(file_exists(__DIR__ . "/" . $path2)){
            $row['file_url'] = $path2;
        } else {
            $row['file_url'] = $path1; // Default to main uploads
        }
        
        $reports[] = $row;
    }
    
    echo json_encode(['ok'=>true, 'reports'=>$reports]);
} catch(Exception $e){
    echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]);
}
?>
