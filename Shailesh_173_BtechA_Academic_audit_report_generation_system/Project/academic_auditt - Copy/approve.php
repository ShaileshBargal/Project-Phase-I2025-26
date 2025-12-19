<?php
session_start();
require_once "db.php";

// ✅ Only head can approve
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
$id      = (int)($_POST['id'] ?? 0);
$remarks = trim($_POST['remarks'] ?? '');
$action  = $_POST['action'] ?? 'approve'; // e.g., approve or reject

// ✅ Determine new status
$status = ($action === 'reject') ? 'rejected' : 'approved';

// ✅ Update report in database
$stmt = $conn->prepare("UPDATE reports 
    SET status = ?, 
        remarks = ?, 
        reviewed_at = NOW(),
        seen = 0 -- mark unseen for incharge alert
    WHERE id = ?");
$stmt->bind_param("ssi", $status, $remarks, $id);
$stmt->execute();

// ✅ Redirect back to head dashboard
header("Location: head.php?msg=Report " . ucfirst($status) . " successfully");
exit;
?>
