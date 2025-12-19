<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['teacher_id'])) {
    header("Location: incharge_login.php");
    exit();
}

// Create requests table if not exists
$conn->query("
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    head_id INT NOT NULL,
    requirement VARCHAR(255) NOT NULL,
    role_id INT NULL,
    role_name VARCHAR(191) NOT NULL,
    semester VARCHAR(10) NOT NULL,
    year VARCHAR(10) DEFAULT '2025',
    status VARCHAR(50) DEFAULT 'pending',
    document_path VARCHAR(255) NULL,
    incharge_id INT NULL,
    uploaded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (head_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES role_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (incharge_id) REFERENCES users(id) ON DELETE SET NULL
)
");

$incharge_role = $_SESSION['incharge_role'] ?? '';
$incharge_id = $_SESSION['teacher_id'];
$username = $_SESSION['teacher_username'] ?? $_SESSION['username'] ?? 'Incharge';

// Handle file upload for request
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])){
    $request_id = intval($_POST['request_id'] ?? 0);
    $file = $_FILES['document'];
    
    if($request_id && $file['error'] === 0){
        $upload_dir = __DIR__ . '/uploads/requests';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','jpg','jpeg','png'];
        
        if(in_array($ext, $allowed) && $file['size'] < 10*1024*1024){
            $new_filename = time() . '_' . $request_id . '.' . $ext;
            $dest = $upload_dir . '/' . $new_filename;
            
            if(move_uploaded_file($file['tmp_name'], $dest)){
                $doc_path = 'uploads/requests/' . $new_filename;
                $stmt = $conn->prepare("UPDATE requests SET status='uploaded', document_path=?, incharge_id=?, uploaded_at=NOW() WHERE id=?");
                $stmt->bind_param("sii", $doc_path, $incharge_id, $request_id);
                if($stmt->execute()){
                    $success = "Document uploaded successfully!";
                }
            }
        }
    }
}

// AJAX handler to get requests by semester
if(isset($_GET['action']) && $_GET['action'] === 'get_requests'){
    $semester = $_GET['semester'] ?? 'odd';
    $my_role = $_SESSION['incharge_role'] ?? '';
    
    $requests = [];
    
    // Only fetch if incharge has a role assigned
    if(!empty($my_role)){
        $username = $_SESSION['teacher_username'] ?? $_SESSION['username'] ?? 'Incharge';
        
        try {
            $stmt = $conn->prepare("
                SELECT r.*, u.username as head_name 
                FROM requests r
                LEFT JOIN users u ON r.head_id = u.id
                WHERE (r.role_name = ? OR r.role_name = ? OR r.role_name LIKE CONCAT(?, ' : %')) AND r.semester = ?
                ORDER BY r.created_at DESC
            ");
            
            if(!$stmt){
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $full_role = $my_role . ' : ' . $username;
            $stmt->bind_param("ssss", $my_role, $full_role, $my_role, $semester);
            
            if(!$stmt->execute()){
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            while($row = $result->fetch_assoc()){
                $requests[] = $row;
            }
        } catch(Exception $e){
             header('Content-Type: application/json');
             echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]);
             exit;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['ok'=>true, 'requests'=>$requests]);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Incharge Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
:root {
  --primary: #06b6d4;
  --primary-dark: #0891b2;
  --accent: #8b5cf6;
  --success: #10b981;
  --text-dark: #0f172a;
  --text-light: #64748b;
  --white: #ffffff;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
  --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
}

* {margin: 0; padding: 0; box-sizing: border-box;}

body {
  font-family: 'Inter', sans-serif;
  background: #f1f5f9;
  min-height: 100vh;
}

/* Header */
header {
  background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
  color: var(--white);
  padding: 24px 48px;
  box-shadow: 0 4px 20px rgba(8,145,178,0.25);
}

header h2 {
  font-size: 26px;
  font-weight: 800;
}

.user-info {
  margin-top: 8px;
  font-size: 15px;
  opacity: 0.95;
}

.logout-btn {
  color: var(--white);
  text-decoration: none;
  padding: 8px 20px;
  background: rgba(220,38,38,0.9);
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.3s;
}

.logout-btn:hover {
  background: rgba(185,28,28,1);
}

.container {
  max-width: 1400px;
  margin: 0 auto;
}

/* Tab Navigation */
.tab-navigation {
  display: flex;
  gap: 16px;
  background: var(--white);
  padding: 8px;
  border-radius: 16px;
  box-shadow: var(--shadow-md);
  margin: 32px 48px 24px;
}

.tab-btn {
  flex: 1;
  padding: 16px 28px;
  border: none;
  border-radius: 12px;
  background: transparent;
  color: var(--text-light);
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.3s;
}

.tab-btn:hover {
  background: linear-gradient(135deg, rgba(6,182,212,0.1) 0%, rgba(139,92,246,0.1) 100%);
  color: var(--primary);
}

.tab-btn.active {
  background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
  color: var(--white);
  box-shadow: 0 4px 16px rgba(6,182,212,0.3);
}

/* Tab Content */
.tab-content {
  display: none;
}

.tab-content.active {
  display: block;
}

.card {
  background: var(--white);
  padding: 28px;
  border-radius: 16px;
  box-shadow: var(--shadow-md);
  margin: 0 48px 40px;
}

.page-title {
  font-size: 24px;
  font-weight: 800;
  color: var(--text-dark);
  margin-bottom: 20px;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

thead {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

th {
  padding: 16px 12px;
  text-align: left;
  font-weight: 700;
  color: var(--text-dark);
  text-transform: uppercase;
  font-size: 12px;
  border-bottom: 2px solid #e2e8f0;
}

td {
  padding: 14px 12px;
  border-bottom: 1px solid #f1f5f9;
  color: var(--text-dark);
}

tbody tr:hover {
  background: linear-gradient(90deg, rgba(6,182,212,0.04) 0%, rgba(139,92,246,0.04) 100%);
}

.status-chip {
  padding: 4px 10px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 12px;
  display: inline-block;
}

.status-chip.pending {background: #f59e0b; color: white;}
.status-chip.uploaded {background: var(--primary); color: white;}
.status-chip.approved {background: var(--success); color: white;}
.status-chip.rejected {background: #dc2626; color: white;}

.upload-btn {
  padding: 8px 16px;
  background: var(--primary);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 13px;
}

.upload-btn:hover {
  background: var(--accent);
}

.file-input {
  padding: 6px;
  border: 2px solid #e2e8f0;
  border-radius: 6px;
  font-size: 13px;
  width: 200px;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-light);
}
</style>
</head>
<body>

<header>
  <div style="display:flex;justify-content:space-between;align-items:center;">
    <div>
      <h2>Incharge Panel</h2>
      <div class="user-info">
        Signed in as <strong><?= htmlspecialchars($username) ?></strong>
        <?php if($incharge_role): ?>
          (<strong><?= htmlspecialchars($incharge_role) ?></strong>)
        <?php endif; ?>
      </div>
    </div>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</header>

<div class="container">
  
  <?php if(isset($success)): ?>
    <div style="margin:20px 48px;padding:16px;background:#10b981;color:white;border-radius:12px;font-weight:600;">
      ✓ <?= $success ?>
    </div>
  <?php endif; ?>

  <!-- Tab Navigation -->
  <div class="tab-navigation">
    <button class="tab-btn active" onclick="switchTab('odd')" data-tab="odd">
      <span>📄</span> Odd Semester
    </button>
    <button class="tab-btn" onclick="switchTab('even')" data-tab="even">
      <span>📋</span> Even Semester
    </button>
  </div>

  <!-- Odd Semester Tab -->
  <div id="odd" class="tab-content active">
    <div class="card">
      <div class="page-title">My Requests — Odd Semester</div>
      
      <table>
        <thead>
          <tr>
            <th>Requirement</th>
            <th>Requested By</th>
            <th>Date</th>
            <th>Status</th>
            <th>Upload Document</th>
          </tr>
        </thead>
        <tbody id="requestsOdd">
          <tr><td colspan="5" class="empty-state">Loading...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Even Semester Tab -->
  <div id="even" class="tab-content">
    <div class="card">
      <div class="page-title">My Requests — Even Semester</div>
      
      <table>
        <thead>
          <tr>
            <th>Requirement</th>
            <th>Requested By</th>
            <th>Date</th>
            <th>Status</th>
            <th>Upload Document</th>
          </tr>
        </thead>
        <tbody id="requestsEven">
          <tr><td colspan="5" class="empty-state">Loading...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
let currentSemester = 'odd';

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
  
  loadRequests(semester);
}

function loadRequests(semester){
  const tbody = semester === 'odd' ? '#requestsOdd' : '#requestsEven';
  
  $(tbody).html('<tr><td colspan="5" class="empty-state">Loading...</td></tr>');
  
  // Use current file name
  $.get('incharge_panel.php', {action:'get_requests', semester}, function(resp){
    if(resp.ok && resp.requests.length > 0){
      let html = '';
      resp.requests.forEach(r => {
        const statusClass = r.status;
        const statusText = r.status.charAt(0).toUpperCase() + r.status.slice(1);
        
        let uploadCol = '';
        if(r.status === 'pending' || r.status === 'rejected'){
           const btnText = r.status === 'rejected' ? 'Re-Upload' : 'Upload';
           const btnStyle = r.status === 'rejected' ? 'background:#dc2626' : 'background:var(--primary)';
           uploadCol = `
            <form method="post" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;">
              <input type="hidden" name="request_id" value="${r.id}">
              <input type="file" name="document" class="file-input" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
              <button type="submit" class="upload-btn" style="${btnStyle}">${btnText}</button>
            </form>
           `;
        } else if(r.status === 'uploaded'){
           uploadCol = '<span style="color:var(--success);font-weight:600;">✓ Uploaded</span>';
        } else {
           uploadCol = '<span style="color:var(--text-light);">-</span>';
        }
        
        const date = new Date(r.created_at).toLocaleDateString();
        
        html += `
          <tr>
            <td>${r.requirement}</td>
            <td>Head</td>
            <td>${date}</td>
            <td><span class="status-chip ${statusClass}">${statusText}</span></td>
            <td>${uploadCol}</td>
          </tr>
        `;
      });
      $(tbody).html(html);
    } else {
      if(resp.ok){
         $(tbody).html('<tr><td colspan="5" class="empty-state">No requests found for this semester</td></tr>');
      } else {
         $(tbody).html(`<tr><td colspan="5" class="empty-state" style="color:red">Error: ${resp.msg}</td></tr>`);
      }
    }
  }, 'json').fail((xhr, status, error) => {
    $(tbody).html(`<tr><td colspan="5" class="empty-state" style="color:red">Network Error: ${error || 'Unknown'}</td></tr>`);
  });
}

// Initialize
loadRequests('odd');
</script>
</body>
</html>
