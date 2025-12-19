<?php
session_start();
require_once "db.php";

// --- Check if user is logged in ---
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: teacher_login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'];

// --- Validate report ID ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid report ID.");
}

$report_id = (int)$_GET['id'];

// --- Fetch filename based on role ---
if ($role === 'teacher') {
    // Teachers can download only their own reports
    $stmt = $conn->prepare("SELECT filename FROM reports WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $report_id, $user_id);
} elseif ($role === 'head') {
    // Head can download any report
    $stmt = $conn->prepare("SELECT filename FROM reports WHERE id = ?");
    $stmt->bind_param("i", $report_id);
} else {
    die("Access denied.");
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("File not found or access denied.");
}

$row = $result->fetch_assoc();
$filename = $row['filename'];

// --- Absolute path to uploads folder ---
$filepath = __DIR__ . "/uploads/" . $filename;

if (!file_exists($filepath)) {
    die("File not found on server.");
}

// --- Determine mime type ---
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$mime_types = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];
$content_type = $mime_types[$ext] ?? 'application/octet-stream';

// --- Send headers ---
header("Content-Description: File Transfer");
header("Content-Type: $content_type");
header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
header("Content-Length: " . filesize($filepath));
header("Cache-Control: must-revalidate");
header("Pragma: public");
header("Expires: 0");

// --- Clean output buffer and read file ---
if (ob_get_level()) ob_end_clean();
readfile($filepath);
exit;
