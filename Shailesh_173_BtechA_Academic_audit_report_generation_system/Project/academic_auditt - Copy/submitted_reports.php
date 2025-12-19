<?php
session_start();
require_once "db.php";

// --- AUTH CHECK ---
if (!isset($_SESSION['head_id']) || ($_SESSION['role'] ?? '') !== 'head') {
    if(isset($_GET['action'])){
        header('Content-Type: application/json');
        echo json_encode(['ok'=>false, 'msg'=>'Unauthorized']);
        exit;
    }
    exit("Unauthorized");
}

// AJAX handler to fetch reports by semester
if(isset($_GET['action']) && $_GET['action'] === 'get_reports'){
    $semester = $_GET['semester'] ?? 'odd';
    $year = $_GET['year'] ?? '2024';
    
    $q = "SELECT * FROM submitted_reports 
          WHERE semester=? AND year=?
          ORDER BY submitted_at DESC";
    
    try {
        $stmt = $conn->prepare($q);
        if(!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt->bind_param("ss", $semester, $year);
        if(!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
        
        $result = $stmt->get_result();
        
        $reports = [];
        while($row = $result->fetch_assoc()){
            $reports[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode(['ok'=>true, 'reports'=>$reports]);
    } catch(Exception $e){
        header('Content-Type: application/json');
        echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Submitted Reports</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --primary:#3b82f6;
  --accent:#8b5cf6;
  --bg-gradient:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --card:#ffffff;
  --text:#0f172a;
  --muted:#64748b;
  --shadow-md:0 8px 24px rgba(15,23,42,0.08);
  font-family:'Inter', sans-serif;
}

*{box-sizing:border-box;margin:0;padding:0}

body{
  background:var(--bg-gradient);
  min-height:100vh;
  padding:24px;
}

.container{
  max-width:1400px;
  margin:0 auto;
}

/* Tab Navigation */
.tab-navigation{
  display:flex;
  gap:8px;
  background:rgba(255,255,255,0.95);
  padding:12px;
  border-radius:16px;
  box-shadow:var(--shadow-md);
  margin-bottom:24px;
}

.tab-btn{
  flex:1;
  padding:14px 24px;
  border:none;
  background:transparent;
  color:var(--muted);
  font-weight:600;
  font-size:15px;
  border-radius:12px;
  cursor:pointer;
  transition:all 0.3s ease;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:10px;
}

.tab-btn:hover{
  background:rgba(59,130,246,0.08);
  color:var(--primary);
}

.tab-btn.active{
  background:linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
  color:#fff;
  box-shadow:0 4px 12px rgba(59,130,246,0.3);
}

/* Tab Content */
.tab-content{
  display:none;
}

.tab-content.active{
  display:block;
  animation:fadeIn 0.3s ease;
}

@keyframes fadeIn{
  from{opacity:0;transform:translateY(10px)}
  to{opacity:1;transform:translateY(0)}
}

.card{
  background:var(--card);
  padding:28px;
  border-radius:16px;
  box-shadow:var(--shadow-md);
  border:1px solid rgba(226,232,240,0.8);
}

.page-title{
  font-size:24px;
  font-weight:800;
  color:var(--text);
  margin-bottom:20px;
}

table{
  width:100%;
  border-collapse:collapse;
  font-size:14px;
}

thead{
  background:linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

th{
  padding:16px 12px;
  text-align:left;
  font-weight:700;
  color:var(--text);
  text-transform:uppercase;
  font-size:12px;
  letter-spacing:0.5px;
  border-bottom:2px solid #e2e8f0;
}

  td{
    padding:14px 12px;
    border-bottom:1px solid #f1f5f9;
    color:var(--text);
    font-size:13px;
    vertical-align: middle;
  }

tbody tr{
  transition:all 0.2s;
}

tbody tr:hover{
  background:linear-gradient(90deg, rgba(59,130,246,0.04) 0%, rgba(139,92,246,0.04) 100%);
}

.status-chip{
  padding:4px 10px;
  border-radius:6px;
  font-weight:600;
  font-size:12px;
  display:inline-block;
}

.status-chip.pending{background:#f59e0b;color:white}
.status-chip.approved{background:var(--primary);color:white}
.status-chip.rejected{background:#dc2626;color:white}

.download-btn{
  color:var(--primary);
  font-weight:600;
  text-decoration:none;
  padding:6px 12px;
  border-radius:6px;
  border:1px solid var(--primary);
  display:inline-block;
  transition:all 0.2s;
}

.download-btn:hover{
  background:var(--primary);
  color:white;
}

.small-input{
  padding:8px;
  width:100%;
  margin-bottom:8px;
  border:2px solid #e2e8f0;
  border-radius:6px;
  font-size:13px;
  outline:none;
}

.small-input:focus{
  border-color:var(--primary);
}

.btn-approve, .btn-reject{
  padding:8px 14px;
  border:none;
  border-radius:6px;
  cursor:pointer;
  font-weight:600;
  font-size:12px;
  transition:all 0.2s;
  margin-right:4px;
}

.btn-approve{
  background:var(--primary);
  color:white;
}

.btn-approve:hover{
  background:var(--accent);
  transform:translateY(-1px);
}

.btn-reject{
  background:#dc2626;
  color:white;
}

.btn-reject:hover{
  background:#b91c1c;
  transform:translateY(-1px);
}

.empty-state{
  text-align:center;
  padding:60px 20px;
  color:var(--muted);
  font-size:16px;
}
</style>
</head>
<body>
<div class="container">
  
  <!-- Tab Navigation -->
  <div class="tab-navigation" style="align-items:center;">
    <button class="tab-btn active" onclick="switchTab('odd')" data-tab="odd">
      <span>📄</span>
      <span>Odd Semester</span>
    </button>
    <button class="tab-btn" onclick="switchTab('even')" data-tab="even">
      <span>📋</span>
      <span>Even Semester</span>
    </button>
    
    <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
      <label style="font-weight:600;font-size:13px;color:var(--muted)">Year:</label>
      <select id="yearFilter" style="padding:8px 12px;border:2px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;cursor:pointer;" onchange="changeYear()">
        <option value="2024" selected>2024</option>
        <option value="2025">2025</option>
        <option value="2026">2026</option>
        <option value="2027">2027</option>
      </select>
    </div>
  </div>

  <!-- Odd Semester Tab -->
  <div id="odd" class="tab-content active">
    <div class="card">
      <div class="page-title">Submitted Reports — Odd Semester</div>
      
      <div style="overflow-x:auto">
        <table>
          <thead>
            <tr>
              <th>Role</th>
              <th>Requirements</th>
              <th>Status</th>
              <th>Year</th>
              <th>Submitted</th>
              <th>Reviewed</th>
              <th>File</th>
              <th style="width:220px">Remarks / Action</th>
            </tr>
          </thead>
          <tbody id="reportsOdd">
            <tr><td colspan="8" class="empty-state">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Even Semester Tab -->
  <div id="even" class="tab-content">
    <div class="card">
      <div class="page-title">Submitted Reports — Even Semester</div>
      
      <div style="overflow-x:auto">
        <table>
          <thead>
            <tr>
              <th>Role</th>
              <th>Requirements</th>
              <th>Status</th>
              <th>Year</th>
              <th>Submitted</th>
              <th>Reviewed</th>
              <th>File</th>
              <th style="width:220px">Remarks / Action</th>
            </tr>
          </thead>
          <tbody id="reportsEven">
            <tr><td colspan="8" class="empty-state">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Clear any cached variables
if(typeof currentSemester !== 'undefined') {
  console.warn('Clearing cached variables');
}
</script>
<script>
let currentSemester = 'odd';
let currentYear = '2024';

function changeYear(){
  currentYear = document.getElementById('yearFilter').value;
  loadReports(currentSemester);
}

function switchTab(semester){
  currentSemester = semester;
  
  document.querySelectorAll('.tab-content').forEach(content => {
    content.classList.remove('active');
  });
  
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.classList.remove('active');
  });
  
  document.getElementById(semester).classList.add('active');
  document.querySelector(`[data-tab="${semester}"]`).classList.add('active');
  
  loadReports(semester);
}

function loadReports(semester){
  const tbody = semester === 'odd' ? '#reportsOdd' : '#reportsEven';
  
  $(tbody).html('<tr><td colspan="8" class="empty-state">Loading...</td></tr>');
  
  console.log('Loading reports for semester:', semester, 'year:', currentYear);
  
  $.get('get_submitted_reports.php', {semester, year: currentYear}, function(resp){
    console.log('AJAX response received:', resp);
    if(resp.ok && resp.reports.length > 0){
      let html = '';
      resp.reports.forEach(r => {
        const statusClass = r.status;
        const statusText = r.status.charAt(0).toUpperCase() + r.status.slice(1);
        
        
        let actionCol = r.remarks || '-';
        
        html += `
          <tr>
            <td>${r.incharge_role || '-'}</td>
            <td>${r.title || '-'}</td>
            <td><span class="status-chip ${statusClass}">${statusText}</span></td>
            <td>${r.year || '-'}</td>
            <td>${r.submitted_at || '-'}</td>
            <td>${r.reviewed_at || '-'}</td>
            <td><a href="uploads/${r.filename}" target="_blank" class="download-btn">View</a></td>
            <td>${actionCol}</td>
          </tr>
        `;
      });
      $(tbody).html(html);
      } else {
         if(resp.ok){
            console.log('No reports found');
            $(tbody).html('<tr><td colspan="8" class="empty-state">No reports found for this semester</td></tr>');
         } else {
            console.error('Error response:', resp.msg);
            $(tbody).html(`<tr><td colspan="8" class="empty-state" style="color:red">Error: ${resp.msg}</td></tr>`);
         }
      }
    }, 'json').fail((xhr, status, error) => {
      console.error('AJAX Failed!');
      console.error('Status:', status);
      console.error('Error:', error);
      console.error('Response:', xhr.responseText);
      alert('Failed to load reports: ' + error + '\nStatus: ' + status);
      $(tbody).html(`<tr><td colspan="8" class="empty-state" style="color:red">Network Error: ${error || 'Unknown'}</td></tr>`);
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function(){
  loadReports('odd');
});
</script>
</body>
</html>
