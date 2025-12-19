<?php
// Make sure composer autoload is correctly included
require __DIR__ . '/../vendor/autoload.php';

require_once "db.php"; // Your database connection

use Dompdf\Dompdf;
use Dompdf\Options;

// --- Filters from GET request ---
$filter_status   = $_GET['status'] ?? 'all';
$filter_year     = $_GET['year'] ?? 'all';
$filter_semester = $_GET['semester'] ?? 'all';
$filter_incharge = $_GET['incharge'] ?? 'all';

// --- Build SQL query ---
$sql = "SELECT r.title, r.status, r.year, r.semester, r.submitted_at, 
               u.username AS incharge_name, u.incharge_role
        FROM reports r
        JOIN users u ON r.teacher_id = u.id
        WHERE 1=1";

$params = [];
$types  = "";

// Apply filters
if (in_array($filter_status, ['pending','approved','rejected'])) {
    $sql .= " AND r.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}
if (in_array($filter_year, ['2024','2025'])) {
    $sql .= " AND r.year = ?";
    $params[] = $filter_year;
    $types .= "s";
}
if (in_array($filter_semester, ['odd','even'])) {
    $sql .= " AND r.semester = ?";
    $params[] = $filter_semester;
    $types .= "s";
}
if ($filter_incharge !== 'all') {
    $sql .= " AND u.incharge_role = ?";
    $params[] = $filter_incharge;
    $types .= "s";
}

$sql .= " ORDER BY r.submitted_at DESC";

// --- Execute query ---
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// --- Build HTML for PDF ---
$html = '<h2 style="text-align:center;">Academic Audit Report</h2>
<table border="1" cellspacing="0" cellpadding="6" width="100%">
<tr style="background:#e5e7eb;">
  <th>Incharge</th>
  <th>Role</th>
  <th>Title</th>
  <th>Status</th>
  <th>Year</th>
  <th>Semester</th>
  <th>Submitted</th>
</tr>';

while ($row = $result->fetch_assoc()) {
    $html .= "<tr>
        <td>{$row['incharge_name']}</td>
        <td>{$row['incharge_role']}</td>
        <td>{$row['title']}</td>
        <td>{$row['status']}</td>
        <td>{$row['year']}</td>
        <td>{$row['semester']}</td>
        <td>{$row['submitted_at']}</td>
    </tr>";
}
$html .= "</table>";

// --- Initialize Dompdf ---
$options = new Options();
$options->set('isRemoteEnabled', true); // Needed if you have images or CSS links

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// --- Stream PDF to browser ---
$dompdf->stream("academic_audit_report.pdf", ["Attachment" => false]);
exit();
