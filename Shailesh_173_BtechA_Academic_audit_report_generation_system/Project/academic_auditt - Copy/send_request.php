<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['head_id']) || ($_SESSION['role'] ?? '') !== 'head') {
    exit("Unauthorized");
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

$head_id = $_SESSION['head_id'];

// ========== ALL AJAX HANDLERS MUST BE HERE (BEFORE HTML) ==========

// Handler: Send new request
// Handler: Send new request
if(isset($_POST['action']) && $_POST['action'] === 'send_request'){
    $requirement = $_POST['requirement'] ?? '';
    // Simplify: Accept arrays for role_ids and role_names OR a structured array
    // Let's expect 'roles' as array of objects or just separate arrays.
    // Easiest is to send JSON string for roles or array of IDs/Names.
    // Let's use 'roles' array of objects: [{id:1, name:'HOD'}, ...]
    $roles = $_POST['roles'] ?? [];
    
    $semester = $_POST['semester'] ?? 'odd';
    $year = $_POST['year'] ?? '2025';
    
    header('Content-Type: application/json');
    
    if(!empty($requirement) && !empty($roles) && is_array($roles)){
        $success_count = 0;
        $stmt = $conn->prepare("INSERT INTO requests (head_id, requirement, role_id, role_name, semester, year, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        
        foreach($roles as $role){
            $role_id = intval($role['id'] ?? 0);
            $role_name = $role['name'] ?? 'Unknown';
            if($role_id > 0){
                $stmt->bind_param("isisss", $head_id, $requirement, $role_id, $role_name, $semester, $year);
                if($stmt->execute()) $success_count++;
            } else {
                $success_count = 0; // Force failure
                $stmt->error = "Invalid Role ID: " . var_export($role, true); 
            }
        }
        
        if($success_count > 0){
            echo json_encode(['ok'=>true, 'msg'=>"$success_count request(s) sent successfully!"]);
        } else {
            $debug = "Role ID: $role_id, Name: $role_name, Head: $head_id, Req: $requirement";
            echo json_encode(['ok'=>false, 'msg'=>'Failed to send. DB Error: ' . $stmt->error . ' ' . $conn->error . ' Debug: ' . $debug]);
        }
    } else {
        echo json_encode(['ok'=>false, 'msg'=>'Invalid data: requirement and roles required']);
    }
    exit;
}

// Handler: Get request status
if(isset($_GET['action']) && $_GET['action'] === 'get_request_status'){
    $requirement = $_GET['requirement'] ?? '';
    $semester = $_GET['semester'] ?? 'odd';
    $year = $_GET['year'] ?? '2025';
    
    header('Content-Type: application/json');
    
    if(!empty($requirement)){
        // Fetch all requests for this requirement (remove LIMIT 1)
        // Also fetch role_name so we can display who it is for
        $stmt = $conn->prepare("SELECT id, status, document_path, uploaded_at, role_name FROM requests WHERE head_id=? AND requirement=? AND semester=? AND year=? ORDER BY created_at DESC");
        $stmt->bind_param("isss", $head_id, $requirement, $semester, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $requests = [];
        while($row = $result->fetch_assoc()){
            $requests[] = $row;
        }
        
        echo json_encode(['ok'=>true, 'requests'=>$requests]);
    } else {
        echo json_encode(['ok'=>false, 'requests'=>[]]);
    }
    exit;
}

// Handler: Approve request
if(isset($_POST['action']) && $_POST['action'] === 'approve_request'){
    $request_id = intval($_POST['request_id'] ?? 0);
    
    header('Content-Type: application/json');
    
    if($request_id){
        // First, get the request details
        $stmt = $conn->prepare("SELECT * FROM requests WHERE id=? AND head_id=?");
        $stmt->bind_param("ii", $request_id, $head_id);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        
        if($request && $request['status'] === 'uploaded'){
            // Update request status to approved
            $stmt = $conn->prepare("UPDATE requests SET status='approved' WHERE id=?");
            $stmt->bind_param("i", $request_id);
            
            if($stmt->execute()){
                // Insert into submitted_reports table
                // Get incharge information
                $incharge_stmt = $conn->prepare("SELECT username, incharge_role FROM users WHERE id=?");
                
                if($incharge_stmt === false){
                    echo json_encode(['ok'=>false, 'msg'=>'Database error: ' . $conn->error]);
                    exit;
                }
                
                $incharge_id = $request['incharge_id'] ?? 0;
                $incharge_stmt->bind_param("i", $incharge_id);
                $incharge_stmt->execute();
                $incharge_data = $incharge_stmt->get_result()->fetch_assoc();
                $incharge_stmt->close();
                
                $stmt = $conn->prepare("INSERT INTO submitted_reports (teacher_id, incharge_name, incharge_role, title, filename, semester, year, status, remarks, submitted_at, reviewed_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'approved', 'Approved via Request', ?, NOW())");
                
                if($stmt === false){
                    echo json_encode(['ok'=>false, 'msg'=>'Database error: ' . $conn->error . '. Make sure submitted_reports table exists.']);
                    exit;
                }
                
                $teacher_id = $request['incharge_id'] ?? 0;
                $incharge_name = $incharge_data['username'] ?? 'Unknown';
                $incharge_role = $incharge_data['incharge_role'] ?? $request['role_name'] ?? 'Unknown';
                $title = $request['requirement'];
                $original_path = $request['document_path']; // e.g., 'uploads/requests/file.pdf'
                $filename = basename($original_path);
                
                // Copy file to main uploads directory if it's in a subdirectory
                $source_path = __DIR__ . '/' . $original_path;
                $dest_path = __DIR__ . '/uploads/' . $filename;
                
                if(file_exists($source_path) && !file_exists($dest_path)){
                    copy($source_path, $dest_path);
                }
                
                $semester = $request['semester'];
                $year = $request['year'];
                $submitted_at = $request['uploaded_at'];
                
                $stmt->bind_param("isssssss", $teacher_id, $incharge_name, $incharge_role, $title, $filename, $semester, $year, $submitted_at);
                
                if($stmt->execute()){
                    echo json_encode(['ok'=>true, 'msg'=>'Request approved and saved to reports!']);
                } else {
                    echo json_encode(['ok'=>false, 'msg'=>'Failed to save to submitted_reports: ' . $stmt->error]);
                }
            } else {
                echo json_encode(['ok'=>false, 'msg'=>'Failed to approve']);
            }
        } else {
            echo json_encode(['ok'=>false, 'msg'=>'Request not found or not uploaded']);
        }
    } else {
        echo json_encode(['ok'=>false, 'msg'=>'Invalid ID']);
    }
    exit;
}

// Handler: Reject request
if(isset($_POST['action']) && $_POST['action'] === 'reject_request'){
    $request_id = intval($_POST['request_id'] ?? 0);
    
    header('Content-Type: application/json');
    
    if($request_id){
        // First, get the request details
        $stmt = $conn->prepare("SELECT * FROM requests WHERE id=? AND head_id=?");
        $stmt->bind_param("ii", $request_id, $head_id);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        
        if($request && $request['status'] === 'uploaded'){
            // Update request status to rejected, clear document so new one can be uploaded
            $stmt = $conn->prepare("UPDATE requests SET status='rejected', document_path=NULL, uploaded_at=NULL WHERE id=?");
            $stmt->bind_param("i", $request_id);
            
            if($stmt->execute()){
                // Get incharge info for report
                $stmt_u = $conn->prepare("SELECT username, incharge_role FROM users WHERE id=?");
                $uid = $request['incharge_id'] ?? 0;
                $stmt_u->bind_param("i", $uid);
                $stmt_u->execute();
                $udata = $stmt_u->get_result()->fetch_assoc();
                $stmt_u->close();

                // Insert into submitted_reports
                $stmt = $conn->prepare("INSERT INTO submitted_reports (teacher_id, incharge_name, incharge_role, title, filename, semester, year, status, remarks, submitted_at, reviewed_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'rejected', 'Rejected via Request', ?, NOW())");
                
                $teacher_id = $request['incharge_id'] ?? 0;
                $incharge_name = $udata['username'] ?? 'Unknown';
                $incharge_role = $udata['incharge_role'] ?? $request['role_name'] ?? 'Unknown';
                $title = $request['requirement'];
                $filename = basename($request['document_path']);
                $semester = $request['semester'];
                $year = $request['year'];
                $submitted_at = $request['uploaded_at'];
                
                $stmt->bind_param("isssssss", $teacher_id, $incharge_name, $incharge_role, $title, $filename, $semester, $year, $submitted_at);
                $stmt->execute();
                
                echo json_encode(['ok'=>true, 'msg'=>'Request rejected!']);
            } else {
                echo json_encode(['ok'=>false, 'msg'=>'Failed to reject']);
            }
        } else {
            echo json_encode(['ok'=>false, 'msg'=>'Request not found or not uploaded']);
        }
    } else {
        echo json_encode(['ok'=>false, 'msg'=>'Invalid ID']);
    }
    exit;
}

// Handler: Get roles by semester and year
// Handler: Get roles by semester and year (Includes specific Incharges)
if(isset($_GET['action']) && $_GET['action'] === 'get_roles'){
    $semester = $_GET['semester'] ?? 'odd';
    $year = $_GET['year'] ?? '2025';
    
    // 1. Get Role Templates
    $templates = [];
    $stmt = $conn->prepare("SELECT id, role_name FROM role_templates WHERE semester=? AND year=? ORDER BY role_name");
    $stmt->bind_param("ss", $semester, $year);
    $stmt->execute();
    $res = $stmt->get_result();
    while($r = $res->fetch_assoc()) $templates[] = $r;
    $stmt->close();

    // 2. Get Registered Incharges (Users)
    $users = [];
    $u_res = $conn->query("SELECT username, incharge_role FROM users WHERE role='teacher' OR role='incharge'");
    while($u = $u_res->fetch_assoc()){
        $users[$u['incharge_role']][] = $u['username'];
    }

    $final_roles = [];
    foreach($templates as $t){
        $rname = $t['role_name'];
        
        // Show Specific Incharges only (RoleName : InchargeName format)
        if(isset($users[$rname])){
            foreach($users[$rname] as $uname){
                $final_roles[] = [
                    'id' => $t['id'],
                    'role_name' => $rname,
                    'incharge_name' => $uname // Display as "RoleName : UserName"
                ];
            }
        } else {
            // If no incharge registered, show generic role
            $final_roles[] = [
                'id' => $t['id'],
                'role_name' => $rname,
                'incharge_name' => null // Display as just "RoleName"
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['ok'=>true, 'roles'=>$final_roles]);
    exit;
}

// Handler: Add new requirement
if(isset($_POST['action']) && $_POST['action'] === 'add_requirement'){
    $req_name = trim($_POST['requirement_name'] ?? '');
    
    header('Content-Type: application/json');
    
    if(!empty($req_name)){
        // Check if exists
        $stmt = $conn->prepare("SELECT id FROM requirements_list WHERE requirement_name = ?");
        $stmt->bind_param("s", $req_name);
        $stmt->execute();
        if($stmt->get_result()->num_rows > 0){
             echo json_encode(['ok'=>false, 'msg'=>'Requirement already exists!']);
             exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO requirements_list (requirement_name) VALUES (?)");
        $stmt->bind_param("s", $req_name);
        
        if($stmt->execute()){
            echo json_encode(['ok'=>true, 'msg'=>'Requirement added successfully!']);
        } else {
            echo json_encode(['ok'=>false, 'msg'=>'Database error: ' . $conn->error]);
        }
    } else {
        echo json_encode(['ok'=>false, 'msg'=>'Invalid requirement name']);
    }
    exit;
}

// ========== END AJAX HANDLERS - HTML BELOW ==========
?>
<!-- Send Request Content (loaded via AJAX in head.php) -->
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

body::before{
  content:'';
  position:fixed;
  top:0;left:0;right:0;bottom:0;
  background:
    radial-gradient(circle at 20% 80%, rgba(139,92,246,0.15) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(59,130,246,0.15) 0%, transparent 50%);
  pointer-events:none;
  z-index:0;
}

.container{
  max-width:1200px;
  margin:0 auto;
  position:relative;
  z-index:1;
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

.card::before{
  content:'';
  position:absolute;
  top:0;left:0;right:0;
  height:4px;
  background:linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
}

.page-title{
  font-size:24px;
  font-weight:800;
  color:var(--text);
  margin-bottom:8px;
}

.page-desc{
  color:var(--muted);
  font-size:14px;
  margin-bottom:20px;
}

.search-box{
  margin-bottom:20px;
  text-align:right;
}

.search-box input{
  padding:12px 16px;
  width:280px;
  border:2px solid #e2e8f0;
  border-radius:10px;
  font-size:14px;
  outline:none;
  transition:all 0.2s;
}

.search-box input:focus{
  border-color:var(--primary);
  box-shadow:0 0 0 4px rgba(59,130,246,0.1);
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
  padding:16px 20px;
  text-align:left;
  font-weight:700;
  color:var(--text);
  text-transform:uppercase;
  font-size:12px;
  letter-spacing:0.5px;
  border-bottom:2px solid #e2e8f0;
}

td{
  padding:16px 20px;
  border-bottom:1px solid #f1f5f9;
  color:var(--text);
}

tbody tr{
  transition:all 0.2s;
}

tbody tr:hover{
  background:linear-gradient(90deg, rgba(59,130,246,0.04) 0%, rgba(139,92,246,0.04) 100%);
}

.select-box{
  width:100%;
  padding:10px 12px;
  border:2px solid #e2e8f0;
  border-radius:8px;
  font-family:inherit;
  font-size:14px;
  background:#fff;
  outline:none;
  cursor:pointer;
  transition:all 0.2s;
}

.select-box:focus{
  border-color:var(--primary);
  box-shadow:0 0 0 3px rgba(59,130,246,0.1);
}

.send-btn{
  background:var(--primary);
  color:#fff;
  padding:10px 18px;
  border:none;
  border-radius:8px;
  cursor:pointer;
  font-weight:600;
  font-size:13px;
  transition:all 0.2s;
}

.send-btn:hover{
  background:var(--accent);
  transform:translateY(-1px);
  box-shadow:0 4px 12px rgba(59,130,246,0.3);
}

.status-text{
  font-weight:600;
  color:var(--muted);
}

/* Status badges */
.status-pending{
  padding:4px 10px;
  background:#f59e0b;
  color:white;
  border-radius:6px;
  font-size:12px;
  font-weight:600;
  display:inline-block;
}

.status-uploaded{
  padding:4px 10px;
  background:var(--primary);
  color:white;
  border-radius:6px;
  font-size:12px;
  font-weight:600;
  display:inline-block;
}

.status-approved{
  padding:4px 10px;
  background:#10b981;
  color:white;
  border-radius:6px;
  font-size:12px;
  font-weight:600;
  display:inline-block;
}

.status-rejected{
  padding:4px 10px;
  background:#dc2626;
  color:white;
  border-radius:6px;
  font-size:12px;
  font-weight:600;
  display:inline-block;
}

.view-doc{
  color:var(--primary);
  font-weight:600;
  text-decoration:none;
  font-size:13px;
}

.view-doc:hover{
  text-decoration:underline;
}

.approve-btn, .reject-btn{
  padding:6px 12px;
  border:none;
  border-radius:6px;
  cursor:pointer;
  font-weight:600;
  font-size:12px;
  margin:4px 2px;
  transition:all 0.2s;
}

.approve-btn{
  background:#10b981;
  color:white;
}

.approve-btn:hover{
  background:#059669;
}

.reject-btn{
  background:#dc2626;
  color:white;
}

.reject-btn:hover{
  background:#b91c1c;
}

@media (max-width:820px){
  .container{padding:20px 16px}
  .tab-btn{padding:12px 16px;font-size:14px}
  th,td{padding:12px 16px}
}
/* Custom Multi-select */
.custom-select {
  position: relative;
  width: 100%;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  padding: 10px 12px;
  font-size: 14px;
  min-height: 42px; /* Ensure consistent height */
}
.custom-select .selected-text {
  color: #64748b;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dropdown-options {
  display: none;
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  z-index: 100;
  max-height: 200px;
  overflow-y: auto;
  padding: 8px;
  margin-top: 4px;
}
.dropdown-options.show {
  display: block;
}
.dropdown-options label {
  display: flex !important;
  align-items: center;
  padding: 8px;
  cursor: pointer;
  border-radius: 4px;
  transition: background 0.2s;
  color: #0f172a;
  margin-bottom: 0;
}
.dropdown-options label:hover {
  background: #f1f5f9;
}
.dropdown-options input {
  margin-right: 8px;
  width: 16px; 
  height: 16px;
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
        <select id="requestYearFilter" class="select-box" style="width:auto;padding:8px 12px;font-size:13px" onchange="changeYear()">
            <?php
            $current_year = date('Y');
            $years = range($current_year - 1, $current_year + 3);
            foreach($years as $y){
                $selected = ($y == 2025) ? 'selected' : ''; // Default to 2025 as per current logic
                echo "<option value='$y' $selected>$y</option>";
            }
            ?>
            <option value="add_new" style="font-weight:bold;color:var(--primary)">+ Add Year</option>
        </select>
    </div>
  </div>

  <!-- Odd Semester Tab -->
  <div id="odd" class="tab-content active">
    <div class="card">
      <div class="page-title">Send Requests — Odd Semester</div>
      <div class="page-desc">Search and send requirements to incharge roles</div>

      <div class="search-box">
        <input type="text" id="searchOdd" placeholder="Search requirements..." onkeyup="searchReq('odd')">
      </div>

      <table>
        <thead>
          <tr>
            <th style="width:40%">Requirement</th>
            <th style="width:25%">Select Role</th>
            <th style="width:15%">Send</th>
            <th style="width:20%">Status</th>
          </tr>
        </thead>
        <tbody id="reqBodyOdd">
          <?php include 'requirements_table.php'; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Even Semester Tab -->
  <div id="even" class="tab-content">
    <div class="card">
      <div class="page-title">Send Requests — Even Semester</div>
      <div class="page-desc">Search and send requirements to incharge roles</div>

      <div class="search-box">
        <input type="text" id="searchEven" placeholder="Search requirements..." onkeyup="searchReq('even')">
      </div>

      <table>
        <thead>
          <tr>
            <th style="width:40%">Requirement</th>
            <th style="width:25%">Select Role</th>
            <th style="width:15%">Send</th>
            <th style="width:20%">Status</th>
          </tr>
        </thead>
        <tbody id="reqBodyEven">
          <?php include 'requirements_table.php'; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add Requirement Section -->
  <div class="card" style="margin-top:40px; border-left: 5px solid var(--accent);">
    <div class="page-title">Add New Requirement</div>
    <div class="page-desc">Add a new requirement to the list for future use.</div>
    
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <input type="text" id="newReqInput" placeholder="Enter new requirement title..." style="flex:1; padding:12px; border:2px solid #e2e8f0; border-radius:8px; outline:none;">
        <button onclick="addRequirement()" class="send-btn" style="background:var(--accent);">+ Add Requirement</button>
    </div>
  </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function addRequirement(){
    const input = document.getElementById('newReqInput');
    const name = input.value.trim();
    
    if(!name){
        alert("Please enter a requirement name");
        return;
    }
    
    if(!confirm("Add '" + name + "' to the requirement list?")) return;
    
    // Disable button to prevent double submit
    const btn = event.target;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = "Adding...";
    
    $.post('send_request.php', {
        action: 'add_requirement',
        requirement_name: name
    }, function(resp){
        if(resp.ok){
            alert("✓ Requirement added successfully! Page will reload.");
            location.reload(); 
        } else {
            alert("Error: " + resp.msg);
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }, 'json').fail(function(){
        alert("Network Error");
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

var currentSemester = 'odd';
var rolesCache = {odd:[], even:[]};
var requestStatuses = {}; // Cache request statuses by requirement

// Switch between tabs
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
  
  // Load roles for this semester
  loadRoles(semester);
  // Load request statuses  
  loadAllRequestStatuses(semester);
}

// Load roles from server
function loadRoles(semester){
  const year = document.getElementById('requestYearFilter') ? document.getElementById('requestYearFilter').value : '2025';
  
  if(rolesCache[semester] && rolesCache[semester].length > 0 && rolesCache.year === year){
    populateDropdowns(semester);
    return;
  }
  
  // Clear cache if year changed
  if(rolesCache.year !== year) {
    rolesCache = {odd:[], even:[], year: year};
  }
  
  $.get('send_request.php', {action:'get_roles', semester, year}, function(resp){
    if(resp.ok){
      rolesCache[semester] = resp.roles;
      populateDropdowns(semester);
    }
  }, 'json');
}

// Handle Year Change
// Handle Year Change
function changeYear(){
    const select = document.getElementById('requestYearFilter');
    const value = select.value;
    
    if(value === 'add_new'){
        let newYear = prompt("Enter new year (YYYY):");
        if(newYear && /^\d{4}$/.test(newYear)){
             // Check existence
             let exists = false;
             for(let i=0; i<select.options.length; i++){
                 if(select.options[i].value === newYear){
                     exists = true;
                     select.value = newYear;
                     break;
                 }
             }

             if(!exists){
                 let option = new Option(newYear, newYear);
                 let lastOption = select.options[select.options.length - 1]; 
                 select.insertBefore(option, lastOption);
                 select.value = newYear;
             }
        } else {
             if(newYear !== null) alert("Invalid Year");
             // Revert to previous using cache or default
             if(rolesCache && rolesCache.year) select.value = rolesCache.year;
             else select.value = '2025'; 
             return; 
        }
    }

    const year = select.value;
    rolesCache = {odd:[], even:[], year: year}; // Clear cache
    loadRoles(currentSemester); // Reload roles
    
    loadAllRequestStatuses(currentSemester);
}

// Populate all dropdowns in the current tab
// Helper: Toggle Dropdown
function toggleDropdown(el){
    // Close other dropdowns
    document.querySelectorAll('.dropdown-options.show').forEach(d => {
        if(d !== el.querySelector('.dropdown-options')) d.classList.remove('show');
    });
    
    const options = el.querySelector('.dropdown-options');
    options.classList.toggle('show');
}

// Helper: Update selected text
function updateSelectedText(checkbox){
    const wrapper = checkbox.closest('.custom-select');
    const checkboxes = wrapper.querySelectorAll('input[type="checkbox"]:checked');
    const textSpan = wrapper.querySelector('.selected-text');
    
    if(checkboxes.length === 0){
        textSpan.textContent = textSpan.dataset.default || '-- Select Roles --';
    } else if(checkboxes.length === 1){
        const text = checkboxes[0].dataset.roleName;
        // Strip the incharge name for brevity if needed, or keep full
        textSpan.textContent = text.split(':')[0]; // Show just role name for compactness? Or full. Let's show full.
        textSpan.textContent = text;
    } else {
        textSpan.textContent = checkboxes.length + ' Roles Selected';
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e){
    if(!e.target.closest('.custom-select')){
        document.querySelectorAll('.dropdown-options.show').forEach(d=>d.classList.remove('show'));
    }
});

// Populate all dropdowns in the current tab
function populateDropdowns(semester){
  const bodyId = semester === 'odd' ? 'reqBodyOdd' : 'reqBodyEven';
  // Use .dropdown-options selector
  const dropdowns = document.querySelectorAll(`#${bodyId} .dropdown-options`);
  
  dropdowns.forEach(dd => {
    dd.innerHTML = '';
    if(rolesCache[semester]){
        rolesCache[semester].forEach(role => {
          const label = document.createElement('label');
          const inchargeText = role.incharge_name ? ` : ${role.incharge_name}` : '';
          const fullText = role.role_name + inchargeText;
          
          const checkbox = document.createElement('input');
          checkbox.type = 'checkbox';
          checkbox.value = role.id;
          checkbox.dataset.roleName = fullText;
          checkbox.onchange = function(){ updateSelectedText(this); };
          
          label.appendChild(checkbox);
          label.appendChild(document.createTextNode(fullText));
          dd.appendChild(label);
        });
    }
  });
}

// Send request
function sendRequest(btn, requirement){
  const row = btn.closest('tr');
  const wrapper = row.querySelector('.custom-select');
  const checkboxes = wrapper.querySelectorAll('input[type="checkbox"]:checked');
  
  if(checkboxes.length === 0){
    alert('Please select at least one role!');
    return;
  }
  
  const roles = [];
  checkboxes.forEach(cb => {
      roles.push({
          id: cb.value,
          name: cb.dataset.roleName
      });
  });
  
  if(!confirm(`Send request to ${roles.length} selected role(s)?`)) return;
  
  btn.disabled = true;
  btn.textContent = 'Sending...';
  
  $.post('send_request.php', {
    action: 'send_request',
    requirement: requirement,
    roles: roles,
    semester: currentSemester,
    year: document.getElementById('requestYearFilter').value
  }, function(resp){
    if(resp.ok){
      alert('✓ ' + (resp.msg || 'Requests sent!'));
      // Reload status for this row
      loadRequestStatus(requirement, currentSemester, row);
      
      // Clear selections?
      checkboxes.forEach(cb => cb.checked = false);
      const textSpan = wrapper.querySelector('.selected-text');
      textSpan.textContent = textSpan.dataset.default;
      
      btn.disabled = false;
      btn.textContent = 'Send';
      
    } else {
      alert('Error: ' + (resp.msg || 'Failed to send request'));
      btn.disabled = false;
      btn.textContent = 'Send';
    }
  }, 'json').fail(() => {
    alert('Network error!');
    btn.disabled = false;
    btn.textContent = 'Send';
  });
}

// Load request status for a single requirement
function loadRequestStatus(requirement, semester, row){
  const year = document.getElementById('requestYearFilter').value;
  $.get('send_request.php', {
    action: 'get_request_status',
    requirement: requirement,
    semester: semester,
    year: year
  }, function(resp){
      const statusCell = row.querySelector('.status-cell');
      statusCell.innerHTML = '-';
      
        if(resp.ok && resp.requests && resp.requests.length > 0){
        let html = '';
        
        resp.requests.forEach(req => {
            // Skip approved or rejected requests so they clear from the view
            if(req.status === 'approved' || req.status === 'rejected') return;
            
            let statusHtml = '';
            // Make badge
             if(req.status === 'pending'){
               statusHtml = `<span class="status-pending">PENDING</span>`;
            } else if(req.status === 'uploaded'){
               statusHtml = `<span class="status-uploaded">UPLOADED</span>`;
               if(req.document_path){
                   statusHtml += `<br><a href="${req.document_path}" target="_blank" class="view-doc">📄 View</a>`;
                   statusHtml += `<br><button onclick="approveRequest(${req.id}, this)" class="approve-btn">✓</button>`;
                   statusHtml += `<button onclick="rejectRequest(${req.id}, this)" class="reject-btn">✗</button>`;
               }
            } else if(req.status === 'rejected'){
               statusHtml = `<span class="status-rejected">REJECTED</span>`;
            }
            
            const roleName = req.role_name || 'Unknown Role';
            // Compact view
            html += `<div style="margin-bottom:8px; border-bottom:1px dashed #eee; padding-bottom:4px;">
                        <div style="font-weight:bold;font-size:11px;">${roleName}</div>
                        ${statusHtml}
                     </div>`;
        });
        
        statusCell.innerHTML = html || '-';
        
      } else {
          statusCell.innerHTML = '-';
      }
  }, 'json');
}

// Load all request statuses for current semester
function loadAllRequestStatuses(semester){
  const bodyId = semester === 'odd' ? 'reqBodyOdd' : 'reqBodyEven';
  const rows = document.querySelectorAll(`#${bodyId} tr`);
  
  rows.forEach(row => {
    const req = row.dataset.requirement;
    if(req){
      loadRequestStatus(req, semester, row);
    }
  });
}

// Approve request
function approveRequest(requestId, btn){
  if(!confirm('Approve this uploaded document?')) return;
  
  // Disable just this button
  btn.disabled = true;
  
  $.post('send_request.php', {
    action: 'approve_request',
    request_id: requestId
  }, function(resp){
    if(resp.ok){
      alert('✓ Request approved!');
      // Reload specific row logic?
      // Find row
      const row = btn.closest('tr');
      const req = row.dataset.requirement;
      loadRequestStatus(req, currentSemester, row);
    } else {
      alert('Error: ' + (resp.msg || 'Failed to approve'));
      btn.disabled = false;
    }
  }, 'json');
}

// Reject request
function rejectRequest(requestId, btn){
  if(!confirm('Reject this uploaded document?')) return;
  
  btn.disabled = true;
  
  $.post('send_request.php', {
    action: 'reject_request',
    request_id: requestId
  }, function(resp){
    if(resp.ok){
      alert('✗ Request rejected!');
       const row = btn.closest('tr');
      const req = row.dataset.requirement;
      loadRequestStatus(req, currentSemester, row);
    } else {
      alert('Error: ' + (resp.msg || 'Failed to reject'));
      btn.disabled = false;
    }
  }, 'json');
}

// Search functionality
function searchReq(semester){
  const inputId = semester === 'odd' ? 'searchOdd' : 'searchEven';
  const bodyId = semester === 'odd' ? 'reqBodyOdd' : 'reqBodyEven';
  const value = document.getElementById(inputId).value.toLowerCase();
  
  document.querySelectorAll(`#${bodyId} tr`).forEach(row => {
    row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
  });
}

// Initialize
loadRoles('odd');
loadAllRequestStatuses('odd');
</script>
