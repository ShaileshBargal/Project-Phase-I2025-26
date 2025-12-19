<?php
session_start();
require_once "db.php";

if(!isset($_SESSION['head_id']) || ($_SESSION['role'] ?? '') !== 'head'){
    exit("Unauthorized");
}

// soften ALTER failure
// Check if incharge_name column exists
$checkI = $conn->query("SHOW COLUMNS FROM role_templates LIKE 'incharge_name'");
if($checkI && $checkI->num_rows == 0){
    $conn->query("ALTER TABLE role_templates ADD COLUMN incharge_name VARCHAR(191) DEFAULT ''");
    // Update unique key: Drop old keys and add new one allowing duplicates
    try { $conn->query("ALTER TABLE role_templates DROP INDEX unique_role_semester"); } catch(Exception $e){}
    try { $conn->query("ALTER TABLE role_templates DROP INDEX unique_role_sem_year"); } catch(Exception $e){}
    try { $conn->query("ALTER TABLE role_templates ADD UNIQUE KEY unique_role_incharge (role_name, incharge_name, semester, year)"); } catch(Exception $e){}
} else {
    // Ensure the key is correct if column exists but key might be old
    try { $conn->query("ALTER TABLE role_templates DROP INDEX unique_role_sem_year"); } catch(Exception $e){}
    try { $conn->query("ALTER TABLE role_templates ADD UNIQUE KEY unique_role_incharge (role_name, incharge_name, semester, year)"); } catch(Exception $e){}
}

// Check if incharge_name in history
$check2I = $conn->query("SHOW COLUMNS FROM role_history LIKE 'incharge_name'");
if($check2I && $check2I->num_rows == 0){
    $conn->query("ALTER TABLE role_history ADD COLUMN incharge_name VARCHAR(191) DEFAULT ''");
}

// Ensure tables exist
$sql_template = "
CREATE TABLE IF NOT EXISTS role_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(191) NOT NULL,
    incharge_name VARCHAR(191) DEFAULT '',
    description TEXT,
    semester VARCHAR(10) DEFAULT 'odd',
    year VARCHAR(10) DEFAULT '2025',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_incharge (role_name, incharge_name, semester, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
if(!$conn->query($sql_template)) {
    die("Error creating role_templates: " . $conn->error);
}

$sql_history = "
CREATE TABLE IF NOT EXISTS role_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NULL,
    role_name VARCHAR(191),
    incharge_name VARCHAR(191),
    semester VARCHAR(10) DEFAULT 'odd',
    year VARCHAR(10) DEFAULT '2025',
    action VARCHAR(50),
    old_value TEXT,
    new_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_role_history_role FOREIGN KEY (role_id) REFERENCES role_templates(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
if(!$conn->query($sql_history)) {
    die("Error creating role_history: " . $conn->error);
}

// Handle AJAX actions
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])){
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'];

    // Add role
    if($action === 'add_role'){
        $role = trim($_POST['role'] ?? '');
        $incharge = trim($_POST['incharge'] ?? '');
        $semester = trim($_POST['semester'] ?? 'odd');
        $year = trim($_POST['year'] ?? '2025');
        
        if(!$role || !$incharge){ echo json_encode(['ok'=>false,'msg'=>'Role and Incharge Name are required']); exit; }

        // Check for duplicates (Role + Incharge + Semester + Year must be unique)
        $stmt = $conn->prepare("SELECT id FROM role_templates WHERE role_name=? AND incharge_name=? AND semester=? AND year=?");
        $stmt->bind_param("ssss",$role,$incharge,$semester,$year); $stmt->execute();
        $res = $stmt->get_result();
        if($res && $res->num_rows>0){ $stmt->close(); echo json_encode(['ok'=>false,'msg'=>'This Role + Incharge combination already exists for this semester and year']); exit; }
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO role_templates (role_name, incharge_name, semester, year) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss",$role,$incharge,$semester,$year);
        if(!$stmt->execute()){ echo json_encode(['ok'=>false,'msg'=>'Database error: '.$stmt->error]); $stmt->close(); exit; }
        $role_id = $stmt->insert_id; $stmt->close();

        // insert CREATED history
        $new_val = "$role : $incharge";
        $stmt = $conn->prepare("INSERT INTO role_history (role_id, role_name, incharge_name, semester, year, action, new_value) VALUES (?, ?, ?, ?, ?, 'CREATED', ?)");
        $stmt->bind_param("isssss",$role_id,$role,$incharge,$semester,$year,$new_val);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT id, role_name, incharge_name, semester, year, created_at FROM role_templates WHERE id=?");
        $stmt->bind_param("i",$role_id); $stmt->execute();
        $newRole = $stmt->get_result()->fetch_assoc(); $stmt->close();

        echo json_encode(['ok'=>true,'role'=>$newRole]); exit;
    }

    // Update role
    if($action === 'update_role'){
        $role_id = intval($_POST['role_id'] ?? 0);
        $role_name = trim($_POST['role_name'] ?? '');
        $incharge_name = trim($_POST['incharge_name'] ?? '');
        if(!$role_id || !$role_name){ echo json_encode(['ok'=>false,'msg'=>'Missing data']); exit; }

        // fetch old
        $stmt = $conn->prepare("SELECT role_name, incharge_name, semester, year FROM role_templates WHERE id=?");
        $stmt->bind_param("i",$role_id); $stmt->execute();
        $oldRole = $stmt->get_result()->fetch_assoc(); $stmt->close();
        $semester = $oldRole['semester'] ?? 'odd';
        $year = $oldRole['year'] ?? '2025';

        // uniqueness check (Role + Incharge + Sem + Year)
        $stmt = $conn->prepare("SELECT id FROM role_templates WHERE role_name=? AND incharge_name=? AND semester=? AND year=? AND id!=?");
        $stmt->bind_param("ssssi",$role_name,$incharge_name,$semester,$year,$role_id); $stmt->execute();
        $r = $stmt->get_result();
        if($r && $r->num_rows>0){ $stmt->close(); echo json_encode(['ok'=>false,'msg'=>'This Role + Incharge combination already exists']); exit; }
        $stmt->close();

        // update main table
        $stmt = $conn->prepare("UPDATE role_templates SET role_name=?, incharge_name=? WHERE id=?");
        $stmt->bind_param("ssi",$role_name,$incharge_name,$role_id);
        if(!$stmt->execute()){ echo json_encode(['ok'=>false,'msg'=>'Error updating role: '.$stmt->error]); $stmt->close(); exit; }
        $stmt->close();

        // Prepare old/new strings
        $old_val = ($oldRole['role_name'] ?? '') . ($oldRole['incharge_name'] ? ' : '.$oldRole['incharge_name'] : '');
        $new_val = $role_name . ($incharge_name ? ' : '.$incharge_name : '');

        // Try to find original CREATED history row and update it
        $stmt = $conn->prepare("SELECT id, new_value FROM role_history WHERE role_id=? AND action='CREATED' ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i",$role_id); $stmt->execute();
        $found = $stmt->get_result()->fetch_assoc(); $stmt->close();

        if($found && isset($found['id'])){
            $history_id = intval($found['id']);
            $prev_new = $found['new_value'] ?? '';
            $stmt = $conn->prepare("UPDATE role_history SET role_name=?, incharge_name=?, old_value=?, new_value=?, created_at=NOW() WHERE id=?");
            $stmt->bind_param("ssssi",$role_name,$incharge_name,$prev_new,$new_val,$history_id);
            $stmt->execute(); $stmt->close();
        } else {
            // fallback: insert UPDATED row
            $year = $oldRole['year'] ?? '2025';
            $stmt = $conn->prepare("INSERT INTO role_history (role_id, role_name, incharge_name, semester, year, action, old_value, new_value) VALUES (?, ?, ?, ?, ?, 'UPDATED', ?, ?)");
            $stmt->bind_param("issssss",$role_id,$role_name,$incharge_name,$semester,$year,$old_val,$new_val);
            $stmt->execute(); $stmt->close();
        }

        echo json_encode(['ok'=>true]); exit;
    }

    // Delete role (keeps DELETED entry)
    if($action === 'delete_role'){
        $role_id = intval($_POST['role_id'] ?? 0);
        if(!$role_id){ echo json_encode(['ok'=>false,'msg'=>'Invalid ID']); exit; }

        // get existing role data including semester
        $stmt = $conn->prepare("SELECT role_name, description, semester FROM role_templates WHERE id=?");
        $stmt->bind_param("i",$role_id); $stmt->execute();
        $role_data = $stmt->get_result()->fetch_assoc(); $stmt->close();
        
        if(!$role_data){ echo json_encode(['ok'=>false,'msg'=>'Role not found']); exit; }

        // log DELETED BEFORE deleting (to avoid foreign key constraint error)
        $old_val = ($role_data['role_name'] ?? '') . ($role_data['description'] ? ' | '.$role_data['description'] : '');
        $role_name = $role_data['role_name'] ?? '';
        $semester = $role_data['semester'] ?? 'odd';
        $stmt = $conn->prepare("INSERT INTO role_history (role_id, role_name, semester, action, old_value) VALUES (?, ?, ?, 'DELETED', ?)");
        $stmt->bind_param("isss",$role_id,$role_name,$semester,$old_val); $stmt->execute(); $stmt->close();

        // NOW delete from templates
        $stmt = $conn->prepare("DELETE FROM role_templates WHERE id=?");
        $stmt->bind_param("i",$role_id);
        if(!$stmt->execute()){ echo json_encode(['ok'=>false,'msg'=>'Error deleting role: '.$stmt->error]); $stmt->close(); exit; }
        $stmt->close();

        echo json_encode(['ok'=>true]); exit;
    }

    // Return a single role
    if($action === 'get_role'){
        $role_id = intval($_POST['role_id'] ?? 0);
        if(!$role_id){ echo json_encode(['ok'=>false,'msg'=>'Invalid ID']); exit; }
        $stmt = $conn->prepare("SELECT id, role_name, incharge_name, semester, year FROM role_templates WHERE id=?");
        $stmt->bind_param("i",$role_id); $stmt->execute();
        $role = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if(!$role){ echo json_encode(['ok'=>false,'msg'=>'Role not found']); exit; }
        echo json_encode(['ok'=>true,'role'=>$role]); exit;
    }

    // Fetch all roles (unused in UI but kept)
    if($action === 'fetch_all'){
        $roles = [];
        $rres = $conn->query("SELECT id, role_name, description, created_at FROM role_templates ORDER BY created_at DESC");
        if($rres){ while($row = $rres->fetch_assoc()) $roles[] = $row; }
        echo json_encode(['ok'=>true,'roles'=>$roles]); exit;
    }

    // Fetch history + current roles merged
    if($action === 'fetch_history'){
        $semester = trim($_POST['semester'] ?? 'odd');
        $queryYear = trim($_POST['year'] ?? '');
        $history = [];
        
        // fetch role_history for this semester AND year (if provided)
        $sql = "SELECT id, role_id, role_name, semester, year, action, old_value, new_value, created_at FROM role_history WHERE semester=?";
        $params = ["s", $semester];
        
        if($queryYear && $queryYear !== 'all'){
            $sql .= " AND year=?";
            $params[0] .= "s";
            $params[] = $queryYear;
        }
        $sql .= " ORDER BY created_at DESC LIMIT 500";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(...$params);
        $stmt->execute();
        $hres = $stmt->get_result();
        if($hres){
            while($row = $hres->fetch_assoc()){
                $row['exists'] = false;
                $history[] = $row;
            }
        }
        $stmt->close();

        // fetch current roles for this semester AND year
        $existing_roles = [];
        $sql2 = "SELECT id, role_name, incharge_name, semester, year, created_at FROM role_templates WHERE semester=?";
        $params2 = ["s", $semester];
        
        if($queryYear && $queryYear !== 'all'){
            $sql2 .= " AND year=?";
            $params2[0] .= "s";
            $params2[] = $queryYear;
        }
        $sql2 .= " ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($sql2);
        $stmt->bind_param(...$params2);
        $stmt->execute();
        $rres = $stmt->get_result();
        if($rres){
            while($r = $rres->fetch_assoc()){
                $existing_roles[intval($r['id'])] = $r;
            }
        }
        $stmt->close();

        // mark history items that point to existing roles AND track creation events
        $created_role_ids = [];
        foreach($history as &$h){
            if(!empty($h['role_id']) && isset($existing_roles[intval($h['role_id'])])){
                $h['exists'] = true;
            } else {
                $h['exists'] = false;
            }
            
            // Track if we already have a CREATED event for this role
            if(!empty($h['role_id']) && ($h['action'] === 'CREATED')){
                $created_role_ids[intval($h['role_id'])] = true;
            }
        }
        unset($h);

        // append current roles as synthetic "CREATED" rows ONLY if not already in history
        foreach($existing_roles as $rid => $r){
            // Skip if we already have a CREATED record for this ID
            if(isset($created_role_ids[$rid])) continue;

            $new_val = $r['role_name'] . ($r['incharge_name'] ? ' : '.$r['incharge_name'] : '');
            $history[] = [
                'id' =>null,
                'role_id' => $rid,
                'role_name' => $r['role_name'],
                'incharge_name' => $r['incharge_name'],
                'semester' => $r['semester'],
                'year' => $r['year'] ?? '2025',
                'action' => 'CREATED',
                'old_value' => '',
                'new_value' => $new_val,
                'created_at' => $r['created_at'],
                'exists' => true
            ];
        }

        // sort by created_at DESC
        usort($history, function($a,$b){
            $ta = strtotime($a['created_at'] ?? '1970-01-01 00:00:00');
            $tb = strtotime($b['created_at'] ?? '1970-01-01 00:00:00');
            return $tb <=> $ta;
        });

        echo json_encode(['ok'=>true,'history'=>$history]); exit;
    }

    echo json_encode(['ok'=>false,'msg'=>'Unknown action']); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Role History & Management</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg-start:#0f172a;
  --bg-end:#1e293b;
  --card:#ffffff;
  --card-dark:#f8fafc;
  --muted:#64748b;
  --text-primary:#0f172a;
  --primary:#3b82f6;
  --primary-dark:#2563eb;
  --accent:#8b5cf6;
  --accent-dark:#7c3aed;
  --success:#10b981;
  --success-bg:#d1fae5;
  --danger:#ef4444;
  --danger-bg:#fee2e2;
  --warning:#f59e0b;
  --info:#06b6d4;
  --radius:16px;
  --shadow-sm: 0 2px 8px rgba(15,23,42,0.04);
  --shadow-md: 0 8px 24px rgba(15,23,42,0.08);
  --shadow-lg: 0 20px 50px rgba(15,23,42,0.12);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

*{
  box-sizing:border-box;
  margin:0;
  padding:0;
}

html,body{
  height:100%;
  margin:0;
  background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color:var(--text-primary);
  overflow-x:hidden;
}

body::before{
  content:'';
  position:fixed;
  top:0;
  left:0;
  right:0;
  bottom:0;
  background:
    radial-gradient(circle at 20% 80%, rgba(139,92,246,0.15) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(59,130,246,0.15) 0%, transparent 50%);
  pointer-events:none;
  z-index:0;
}

.container{
  max-width:1200px;
  margin:0 auto;
  padding:24px;
  position:relative;
  z-index:1;
  min-height:100vh;
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
  border:1px solid rgba(226,232,240,0.8);
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

.tab-icon{
  font-size:20px;
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
  from{
    opacity:0;
    transform:translateY(10px);
  }
  to{
    opacity:1;
    transform:translateY(0);
  }
}

/* Remove old header styles - keeping minimal for compatibility */
.header{
  display:none;
}
  padding:32px 36px;
  border-radius:var(--radius);
  color:#fff;
  box-shadow:var(--shadow-lg);
  margin-bottom:32px;
  position:relative;
  overflow:hidden;
}

.header::before{
  content:'';
  position:absolute;
  top:-50%;
  right:-20%;
  width:400px;
  height:400px;
  background:radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
  border-radius:50%;
}

.header-content{
  position:relative;
  z-index:1;
}

.header h1{
  margin:0 0 8px 0;
  font-size:32px;
  font-weight:800;
  letter-spacing:-0.5px;
}

.header .lead{
  margin:0;
  opacity:0.95;
  font-size:15px;
  color:rgba(255,255,255,0.9);
  font-weight:400;
}

.controls{
  display:flex;
  gap:12px;
  align-items:center;
  margin-top:20px;
}

/* Button Styles */
.btn{
  border:0;
  padding:12px 20px;
  border-radius:12px;
  cursor:pointer;
  font-weight:600;
  font-size:14px;
  transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display:inline-flex;
  align-items:center;
  gap:8px;
}

.btn:hover{
  transform:translateY(-2px);
}

.btn-ghost{
  background:rgba(255,255,255,0.15);
  color:#fff;
  border:1px solid rgba(255,255,255,0.2);
  backdrop-filter:blur(10px);
}

.btn-ghost:hover{
  background:rgba(255,255,255,0.25);
  box-shadow:0 8px 20px rgba(0,0,0,0.15);
}

.btn-primary{
  background:#fff;
  color:var(--primary);
  box-shadow:0 4px 12px rgba(0,0,0,0.1);
}

.btn-primary:hover{
  background:#f8fafc;
  box-shadow:0 8px 24px rgba(0,0,0,0.15);
}

/* Section Layout */
.sections-grid{
  display:grid;
  grid-template-columns:1fr;
  gap:24px;
  margin-top:24px;
}

/* Card Styles */
.card{
  background:var(--card);
  padding:28px;
  border-radius:var(--radius);
  box-shadow:var(--shadow-md);
  border:1px solid rgba(226,232,240,0.8);
  position:relative;
  overflow:hidden;
  transition:all 0.3s ease;
}

.card::before{
  content:'';
  position:absolute;
  top:0;
  left:0;
  right:0;
  height:4px;
  background:linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
}

.card:hover{
  box-shadow:var(--shadow-lg);
  transform:translateY(-2px);
}

.section-header{
  display:flex;
  align-items:center;
  gap:12px;
  margin-bottom:20px;
}

.section-icon{
  width:48px;
  height:48px;
  border-radius:12px;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:24px;
  font-weight:700;
  background:linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
  color:#fff;
  box-shadow:0 4px 12px rgba(59,130,246,0.3);
}

.section-title{
  flex:1;
}

.card-title{
  font-weight:800;
  margin:0 0 6px 0;
  color:var(--text-primary);
  font-size:22px;
  letter-spacing:-0.3px;
}

.section-description{
  color:var(--muted);
  font-size:14px;
  margin:0;
  line-height:1.5;
}

/* Form Styles */
.form-container{
  background:var(--card-dark);
  padding:24px;
  border-radius:12px;
  border:1px solid #e2e8f0;
  margin-top:16px;
}

.form-row{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:16px;
  margin-bottom:16px;
}

.form-group-full{
  grid-column:1 / -1;
}

.form-label{
  display:block;
  font-weight:600;
  font-size:13px;
  color:var(--text-primary);
  margin-bottom:8px;
  text-transform:uppercase;
  letter-spacing:0.5px;
}

.form-input,
.form-textarea{
  width:100%;
  padding:12px 16px;
  border:2px solid #e2e8f0;
  border-radius:10px;
  font-family:inherit;
  font-size:14px;
  background:#fff;
  color:var(--text-primary);
  outline:none;
  transition:all 0.2s ease;
}

.form-input:focus,
.form-textarea:focus{
  border-color:var(--primary);
  box-shadow:0 0 0 4px rgba(59,130,246,0.1);
}

.form-textarea{
  min-height:100px;
  resize:vertical;
}

.form-actions{
  display:flex;
  justify-content:flex-end;
  gap:12px;
  margin-top:20px;
}

/* Table Styles */
.table-container{
  margin-top:20px;
  border-radius:12px;
  overflow:hidden;
  border:1px solid #e2e8f0;
  background:#fff;
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
  vertical-align:middle;
  font-weight:700;
  color:var(--text-primary);
  text-transform:uppercase;
  font-size:12px;
  letter-spacing:0.5px;
  border-bottom:2px solid #e2e8f0;
}

td{
  padding:16px 20px;
  border-bottom:1px solid #f1f5f9;
  vertical-align:middle;
  color:var(--text-primary);
}

tbody tr{
  transition:all 0.2s ease;
}

tbody tr:hover{
  background:linear-gradient(90deg, rgba(59,130,246,0.04) 0%, rgba(139,92,246,0.04) 100%);
}

tbody tr:last-child td{
  border-bottom:none;
}

.empty{
  padding:48px 20px;
  text-align:center;
  color:var(--muted);
  font-size:15px;
}

/* Badge Styles */
.badge{
  display:inline-flex;
  align-items:center;
  padding:6px 12px;
  border-radius:20px;
  font-weight:700;
  font-size:11px;
  text-transform:uppercase;
  letter-spacing:0.5px;
}

.badge-created{
  background:var(--success-bg);
  color:#065f46;
}

.badge-updated{
  background:#fef3c7;
  color:#92400e;
}

.badge-deleted{
  background:var(--danger-bg);
  color:#991b1b;
}

/* Action Buttons */
.action-btn{
  padding:8px 14px;
  border-radius:8px;
  border:0;
  cursor:pointer;
  font-weight:600;
  margin-left:6px;
  transition:all 0.2s ease;
  font-size:13px;
}

.action-btn:hover{
  transform:translateY(-1px);
  box-shadow:0 4px 12px rgba(0,0,0,0.15);
}

.btn-edit{
  background:#dbeafe;
  color:var(--primary-dark);
}

.btn-edit:hover{
  background:#bfdbfe;
}

.btn-delete{
  background:var(--danger-bg);
  color:var(--danger);
}

.btn-delete:hover{
  background:#fecaca;
}

/* Modal Styles */
.modal{
  position:fixed;
  inset:0;
  display:none;
  align-items:center;
  justify-content:center;
  background:rgba(15,23,42,0.6);
  backdrop-filter:blur(4px);
  z-index:100;
  padding:20px;
}

.modal.show{
  display:flex;
}

.modal-box{
  background:var(--card);
  padding:32px;
  border-radius:var(--radius);
  min-width:320px;
  max-width:560px;
  width:100%;
  box-shadow:var(--shadow-lg);
  animation:modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn{
  from{
    opacity:0;
    transform:translateY(-20px);
  }
  to{
    opacity:1;
    transform:translateY(0);
  }
}

.modal-header{
  font-weight:800;
  margin-bottom:20px;
  font-size:20px;
  color:var(--text-primary);
}

.modal-body{
  margin:16px 0;
}

.modal-body input,
.modal-body textarea{
  width:100%;
  padding:12px 16px;
  border:2px solid #e2e8f0;
  border-radius:10px;
  font-family:inherit;
  font-size:14px;
  background:#fff;
  color:var(--text-primary);
  outline:none;
  margin-bottom:12px;
  transition:all 0.2s ease;
}

.modal-body input:focus,
.modal-body textarea:focus{
  border-color:var(--primary);
  box-shadow:0 0 0 4px rgba(59,130,246,0.1);
}

.modal-body textarea{
  min-height:100px;
  resize:vertical;
}

.modal-actions{
  display:flex;
  justify-content:flex-end;
  gap:12px;
  margin-top:24px;
}

/* Message Styles */
.success-msg{
  color:#065f46;
  padding:14px 18px;
  background:var(--success-bg);
  border-left:4px solid var(--success);
  border-radius:10px;
  margin-bottom:16px;
  font-weight:500;
  animation:slideDown 0.3s ease;
}

.error-msg{
  color:#991b1b;
  padding:14px 18px;
  background:var(--danger-bg);
  border-left:4px solid var(--danger);
  border-radius:10px;
  margin-bottom:16px;
  font-weight:500;
  animation:slideDown 0.3s ease;
}

@keyframes slideDown{
  from{
    opacity:0;
    transform:translateY(-10px);
  }
  to{
    opacity:1;
    transform:translateY(0);
  }
}

/* Responsive Design */
@media (max-width:820px){
  .container{
    padding:20px 16px;
  }
  
  .header{
    padding:24px 20px;
  }
  
  .header h1{
    font-size:24px;
  }
  
  .form-row{
    grid-template-columns:1fr;
  }
  
  .section-icon{
    width:40px;
    height:40px;
    font-size:20px;
  }
  
  .card-title{
    font-size:18px;
  }
  
  th,td{
    padding:12px 16px;
  }
  
  .action-btn{
    margin-left:0;
    margin-top:6px;
    display:block;
    width:100%;
  }
  
  .modal-box{
    padding:24px;
  }
}

/* Utility Classes */
.text-center{
  text-align:center;
}

.mt-2{
  margin-top:16px;
}

.mb-2{
  margin-bottom:16px;
}
</style>
</head>
<body>
<div class="container">
  <!-- Tab Navigation -->
  <div class="tab-navigation">
    <button class="tab-btn active" onclick="switchTab('addRole')" data-tab="addRole">
      <span class="tab-icon">➕</span>
      <span>Add Roles</span>
    </button>
    <button class="tab-btn" onclick="switchTab('roleHistory')" data-tab="roleHistory">
      <span class="tab-icon">📋</span>
      <span>Role History</span>
    </button>
  </div>

  <!-- Tab Content: Add Role Section -->
  <div id="addRole" class="tab-content active">
    <div class="card">
      <div class="section-header">
        <div class="section-icon">➕</div>
        <div class="section-title">
          <h2 class="card-title">Add New Role</h2>
          <p class="section-description">Create a new role that will be available across the system</p>
        </div>
      </div>
      
      <div id="addRoleMsg"></div>
      
      <div class="form-container">
        <!-- Semester Selector -->
        <div class="form-group-full" style="margin-bottom:20px">
          <label class="form-label">Select Semester & Year</label>
          <div style="display:flex;gap:16px;margin-top:8px;align-items:center;flex-wrap:wrap">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
              <input type="radio" name="semester" value="odd" checked onchange="currentSemester='odd'" style="width:18px;height:18px;cursor:pointer">
              <span style="font-weight:600;color:var(--text-primary)">Odd Semester</span>
            </label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
              <input type="radio" name="semester" value="even" onchange="currentSemester='even'" style="width:18px;height:18px;cursor:pointer">
              <span style="font-weight:600;color:var(--text-primary)">Even Semester</span>
            </label>
            
            <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
                <label class="form-label" style="margin:0">Year:</label>
                <select id="roleYear" class="form-input" style="width:auto;padding:8px 12px;" onchange="handleYearChange(this)">
                    <option value="2024">2024</option>
                    <option value="2025" selected>2025</option>
                    <option value="2026">2026</option>
                    <option value="2027">2027</option>
                    <option value="add_new" style="font-weight:bold;color:var(--primary)">+ Add Year</option>
                </select>
            </div>
          </div>
        </div>
        
        <div class="form-row">
          <div>
            <label class="form-label">Role Name</label>
            <input id="newRole" class="form-input" type="text" placeholder="e.g., HOD, Coordinator">
          </div>
          <div>
            <label class="form-label">Incharge Name</label>
            <input id="roleIncharge" class="form-input" type="text" placeholder="e.g., Mr. S. G. Salve">
          </div>
        </div>
        
        <div class="form-actions">
          <button class="btn btn-primary" onclick="addRole()">✓ Create Role</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Tab Content: Role History Section -->
  <div id="roleHistory" class="tab-content">
    <div class="card">
      <div class="section-header">
        <div class="section-icon">📋</div>
        <div class="section-title">
          <h2 class="card-title">Role History</h2>
          <p class="section-description">View all role changes with complete audit trail and manage existing roles</p>
        </div>
      </div>
      
      <!-- Semester Filter + Year Filter -->
      <div style="margin:16px 0;display:flex;gap:12px;align-items:center;flex-wrap:wrap">
        <button class="btn btn-primary" onclick="filterHistory('odd')" id="btnOdd" style="font-size:14px">Odd Semester</button>
        <button class="btn btn-ghost" onclick="filterHistory('even')" id="btnEven" style="font-size:14px;background:#e2e8f0;color:var(--text-primary)">Even Semester</button>
        
        <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
           <label style="font-weight:600;font-size:13px;color:var(--muted)">Filter Year:</label>
           <select id="historyYearFilter" class="form-input" style="width:auto;padding:8px 12px;font-size:13px" onchange="loadHistory()">
               <option value="all">All Years</option>
               <option value="2024">2024</option>
               <option value="2025" selected>2025</option>
               <option value="2026">2026</option>
               <option value="2027">2027</option>
           </select>
        </div>
      </div>

      <div class="table-container">
        <table id="historyTable" aria-describedby="historyCaption">
          <caption id="historyCaption" style="display:none">Role change history</caption>
          <thead>
            <tr>
              <th style="min-width:200px">Role Name</th>
              <th style="width:120px">Action</th>
              <th>Old Value</th>
              <th>New Value</th>
              <th style="width:180px">Date & Time</th>
              <th style="width:200px">Actions</th>
            </tr>
          </thead>
          <tbody id="historyTableBody">
            <tr><td colspan="6" class="empty">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Edit modal -->
<div id="editModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal-box">
    <div class="modal-header" id="modalTitle">Edit Role</div>
    <div id="editMsg"></div>
    <div class="modal-body">
      <label class="form-label">Role Name</label>
      <input id="editRoleName" type="text" placeholder="Role Name">
      <label class="form-label" style="margin-top:10px">Incharge Name</label>
      <input id="editRoleIncharge" type="text" placeholder="Incharge Name">
    </div>
    <div class="modal-actions">
      <button class="btn btn-ghost" onclick="closeEditModal()">Cancel</button>
      <button class="btn btn-primary" onclick="saveRoleEdit()">Save Changes</button>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
var editingRoleId = null;
var currentSemester = 'odd'; // default semester
var historySemester = 'odd'; // current history filter

function escapeHtml(s){ return String(s||'').replace(/[&<>"'`]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#x60;'}[c])); }

function showMsg(elemId, msg, isError=false){
  const el=document.getElementById(elemId);
  if(!el) return;
  el.innerHTML = msg ? `<div class="${isError? 'error-msg':'success-msg'}">${escapeHtml(msg)}</div>` : '';
}

// Tab switching function
function switchTab(tabId){
  document.querySelectorAll('.tab-content').forEach(content => {
    content.classList.remove('active');
  });
  
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.classList.remove('active');
  });
  
  const selectedTab = document.getElementById(tabId);
  if(selectedTab) selectedTab.classList.add('active');
  
  const selectedBtn = document.querySelector(`[data-tab="${tabId}"]`);
  if(selectedBtn) selectedBtn.classList.add('active');
  
  if(tabId === 'roleHistory'){
    loadHistory(historySemester);
  }
}

// Filter history by semester
function filterHistory(semester){
  historySemester = semester;
  loadHistory(semester);
  
  // Update button styles
  const btnOdd = document.getElementById('btnOdd');
  const btnEven = document.getElementById('btnEven');
  if(btnOdd && btnEven){
    btnOdd.className = semester === 'odd' ? 'btn btn-primary' :  'btn btn-ghost';
    btnEven.className = semester === 'even' ? 'btn btn-primary' : 'btn btn-ghost';
    
    if(semester === 'even'){
      btnEven.style.background = '';
      btnEven.style.color = '';
    } else {
      btnOdd.style.background = '';
      btnOdd.style.color = '';
    }
  }
}

function formatDate(d){
  if(!d) return '-';
  return new Date(d).toLocaleString();
}

function handleYearChange(select){
    if(select.value === 'add_new'){
        const newYear = prompt("Enter new year:");
        if(newYear && newYear.match(/^\d{4}$/)){
             // Add option before the 'add_new' option
             const opt = document.createElement('option');
             opt.value = newYear;
             opt.text = newYear;
             opt.selected = true;
             select.add(opt, select.options[select.options.length - 1]);
             select.value = newYear;
        } else {
             select.value = '2025'; // reset if invalid
        }
    }
}

function addRole(){
  const role = document.getElementById('newRole').value.trim();
  const incharge = document.getElementById('roleIncharge').value.trim();
  const semester = document.querySelector('input[name="semester"]:checked').value;
  const year = document.getElementById('roleYear').value;
  
  if(!role){ showMsg('addRoleMsg','Please enter a role name', true); return; }
  if(!incharge){ showMsg('addRoleMsg','Please enter Incharge Name', true); return; }
  
  $.post('manage_incharge.php',{ action:'add_role', role, incharge, semester, year }, function(resp){
    if(resp.ok){ 
      document.getElementById('newRole').value=''; 
      document.getElementById('roleIncharge').value=''; 
      showMsg('addRoleMsg','✓ Role created successfully for ' + (semester === 'odd' ? 'Odd' : 'Even') + ' Semester ' + year); 
      setTimeout(()=> showMsg('addRoleMsg',''), 2000);
      loadHistory(); // Reload history if visible
    }
    else showMsg('addRoleMsg', resp.msg || 'Error', true);
  }, 'json').fail(()=> showMsg('addRoleMsg','Request failed', true));
}

function loadHistory(semester){
  if(!semester) semester = historySemester || 'odd';
  const year = document.getElementById('historyYearFilter') ? document.getElementById('historyYearFilter').value : '2025';

  $.post('manage_incharge.php',{ action:'fetch_history', semester, year }, function(resp){
    const tbody = document.getElementById('historyTableBody');
    if(!tbody) return;
    tbody.innerHTML = '';
    if(!resp.ok || !resp.history || resp.history.length===0){
      tbody.innerHTML = '<tr><td colspan="6" class="empty">No history found for this semester</td></tr>';
      return;
    }
    resp.history.forEach(item=>{
      const oldVal = item.old_value ? escapeHtml(item.old_value) : '-';
      const newVal = item.new_value ? escapeHtml(item.new_value) : '-';
      const action = escapeHtml(item.action || '');
      const badgeClass = action === 'CREATED' ? 'badge-created' : (action === 'DELETED' ? 'badge-deleted' : 'badge-updated');

      let actionsHtml = '<span style="color:var(--muted);font-weight:600">—</span>';
      if(item.exists && item.role_id && item.action !== 'DELETED'){
        actionsHtml = `<button class="action-btn btn-edit" onclick="openEditFromHistory(${item.role_id})">Edit</button>
                       <button class="action-btn btn-delete" onclick="deleteFromHistory(${item.role_id})">Delete</button>`;
      }

      const tr = document.createElement('tr');
      tr.innerHTML = `<td>
                        ${escapeHtml(item.role_name)}
                        <div style="font-size:11px;color:var(--muted)">Year: ${item.year||'2025'}</div>
                      </td>
                      <td><span class="badge ${badgeClass}">${action}</span></td>
                      <td>${oldVal}</td>
                      <td>${newVal}</td>
                      <td>${formatDate(item.created_at)}</td>
                      <td style="white-space:nowrap">${actionsHtml}</td>`;
      tbody.appendChild(tr);
    });
  }, 'json').fail(()=> {
    const tbody = document.getElementById('historyTableBody');
    if(tbody) tbody.innerHTML = '<tr><td colspan="6" class="empty">Error loading history</td></tr>';
  });
}

function openEditFromHistory(roleId){
  if(!roleId) return alert('Role ID not available');
  $.post('manage_incharge.php',{ action:'get_role', role_id: roleId }, function(resp){
    if(resp.ok && resp.role){
      editingRoleId = resp.role.id;
      document.getElementById('editRoleName').value = resp.role.role_name;
      document.getElementById('editRoleIncharge').value = resp.role.incharge_name || '';
      document.getElementById('editMsg').innerHTML = '';
      document.getElementById('editModal').classList.add('show');
    } else {
      alert(resp.msg || 'Role not found');
      loadHistory();
    }
  }, 'json').fail(()=> alert('Request failed'));
}

function closeEditModal(){ 
  const modal = document.getElementById('editModal');
  if(modal) modal.classList.remove('show'); 
  editingRoleId = null; 
}

function saveRoleEdit(){
  const roleName = document.getElementById('editRoleName').value.trim();
  const inchargeName = document.getElementById('editRoleIncharge').value.trim();
  if(!roleName){ showMsg('editMsg','Role name cannot be empty', true); return; }
  $.post('manage_incharge.php', { action:'update_role', role_id: editingRoleId, role_name: roleName, incharge_name: inchargeName }, function(resp){
    if(resp.ok){ 
      showMsg('editMsg','✓ Role updated successfully'); 
      setTimeout(()=>{ closeEditModal(); loadHistory(); }, 500); 
    }
    else showMsg('editMsg', resp.msg || 'Error', true);
  }, 'json').fail(()=> showMsg('editMsg','Request failed', true));
}

function deleteFromHistory(roleId){
  if(!confirm('Delete this role? This action will be recorded in history.')) return;
  $.post('manage_incharge.php', { action:'delete_role', role_id: roleId }, function(resp){
    if(resp.ok){ loadHistory(); } 
    else alert(resp.msg || 'Error deleting role');
  }, 'json').fail((xhr)=> alert('Request failed: ' + (xhr.responseText || xhr.statusText)));
}

function deleteHistoryEntry(historyId){
  if(!historyId) return;
  if(!confirm('Delete this history entry?')) return;
  $.post('manage_incharge.php', { action:'delete_history', history_id: historyId }, function(resp){
    if(resp && resp.ok){ loadHistory(); }
    else alert(resp.msg || 'Could not delete history entry');
  }, 'json').fail(()=> alert('Request failed'));
}

// init
$(function(){
  console.log("Manage Incharge: Initializing...");
  loadHistory('odd');
});
</script>
</body>
</html>