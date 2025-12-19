<?php
session_start();
require_once "db.php";

// ✅ Only head can reject
if (!isset($_SESSION['head_id']) || $_SESSION['role'] !== 'head') {
    header("Location: head_login.php");
    exit();
}

// ✅ Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: head.php");
    exit();
}

// ✅ Get POST data
$id = (int)($_POST['id'] ?? 0);
$remarks = trim($_POST['remarks'] ?? '');

// ✅ Update report status and mark it as unseen for incharge
$stmt = $conn->prepare("
    UPDATE reports 
    SET status = 'rejected', 
        remarks = ?, 
        reviewed_at = NOW(), 
        seen = 0  -- unseen so incharge gets alert
    WHERE id = ?
");
$stmt->bind_param("si", $remarks, $id);
$stmt->execute();

// ✅ Redirect back to head dashboard with success message
header("Location: head.php?msg=Report rejected successfully");
exit;
?>
