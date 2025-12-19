<?php
session_start();
require_once "db.php";

// AJAX: Get Names by Role
if(isset($_POST['action']) && $_POST['action'] === 'get_names'){
    $role = $_POST['role_name'] ?? '';
    $names = [];
    if($role){
        $stmt = $conn->prepare("SELECT username FROM users WHERE incharge_role = ? ORDER BY username ASC");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $res = $stmt->get_result();
        while($r = $res->fetch_assoc()){
            $names[] = $r['username'];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($names);
    exit;
}

// Redirect if already logged in as teacher/incharge
if (!empty($_SESSION['user_id']) && !empty($_SESSION['teacher_id']) && !empty($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
    header("Location: incharge_panel.php");
    exit;
}

$error = "";
$roles = [];

// Fetch roles for dropdown
$role_res = $conn->query("SELECT DISTINCT role_name FROM role_templates ORDER BY role_name");
if($role_res){
    while($r = $role_res->fetch_assoc()){
        $roles[] = $r['role_name'];
    }
}
if(!in_array('Teacher', $roles)) $roles[] = 'Teacher';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $name = $_POST['username'] ?? ''; // Selected Name
    $password = $_POST['password'] ?? '';

    if($role && $name && $password){
        // Fetch specific user
        $stmt = $conn->prepare("SELECT id, username, password, email FROM users WHERE incharge_role = ? AND username = ?");
        $stmt->bind_param("ss", $role, $name);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if($user = $res->fetch_assoc()){
            if (password_verify($password, $user['password'])) {
                // Login Success
                $_SESSION['user_id']  = (int)$user['id'];
                $_SESSION['teacher_id'] = (int)$user['id']; 
                $_SESSION['username'] = $user['username'];
                $_SESSION['email']    = $user['email'];
                $_SESSION['role']     = 'teacher'; 
                $_SESSION['incharge_role'] = $role;
                
                header("Location: incharge_panel.php");
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "User not found (Role/Name mismatch).";
        }
    } else {
        $error = "Please fill all fields.";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Incharge Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    *{box-sizing:border-box}
    body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#e0ecff,#fff);margin:0;height:100vh;display:flex;align-items:center;justify-content:center}
    .container{background:#fff;padding:45px;border-radius:18px;box-shadow:0 10px 35px rgba(0,0,0,.12);width:90%;max-width:420px;text-align:center;animation:fadeIn .9s}
    @keyframes fadeIn{from{opacity:0;transform:translateY(25px)}to{opacity:1;transform:translateY(0)}}
    h1{color:#1e3a8a;margin:0 0 6px;font-weight:700;font-size:1.6rem} h2{color:#2563eb;margin:0 0 22px;font-weight:600}
    label{display:block;text-align:left;margin-bottom:6px;font-weight:600;color:#374151;font-size:.95rem}
    input, select{width:100%;padding:12px 14px;border-radius:10px;border:1px solid #cbd5e1;margin-bottom:20px;background:#f9fafb;transition:.3s;font-family:inherit}
    input:focus, select:focus{outline:none;border-color:#2563eb;box-shadow:0 0 7px rgba(37,99,235,.3);background:#fff}
    .btn{width:100%;background:linear-gradient(90deg,#2563eb,#1e40af);color:#fff;padding:12px;border-radius:10px;border:0;font-weight:600;cursor:pointer}
    .btn:hover{transform:scale(1.02)}
    .msg{margin-top:10px;padding:10px;border-radius:8px;font-weight:600}
    .msg.error{background:#fee2e2;color:#b91c1c}
    .links{margin-top:18px;display:flex;justify-content:center;font-size:.9rem}
    a{color:#2563eb;text-decoration:none;font-weight:600}
  </style>
</head>
<body>
  <div class="container">
    <h1>Academic Audit</h1>
    <h2>Incharge Login</h2>

    <?php if ($error): ?>
      <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <label>Select Role</label>
      <select name="role" id="roleSelect" required>
          <option value="">-- Select Role --</option>
          <?php foreach($roles as $r): ?>
             <option value="<?= htmlspecialchars($r) ?>" <?= (isset($_POST['role']) && $_POST['role'] === $r) ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option>
          <?php endforeach; ?>
      </select>
      
      <div id="nameContainer" style="display:none;">
          <label>Select Name</label>
          <select name="username" id="nameSelect" required>
              <option value="">-- Select Name --</option>
          </select>
      </div>
      
      <label>Password</label>
      <input type="password" name="password" required placeholder="Enter password">
      
      <button type="submit" class="btn">Login</button>
    </form>

    <div class="links" style="display:flex;flex-direction:column;gap:10px;">
      <a href="set_incharge_password.php">Set / Forgot Password?</a>
      <a href="register_incharge.php" style="color:#64748b;font-weight:500">New User? Register</a>
    </div>
  </div>

<script>
$(document).ready(function(){
    $('#roleSelect').change(function(){
        var role = $(this).val();
        if(role){
            $.post('incharge_login.php', {action: 'get_names', role_name: role}, function(names){
                var opts = '<option value="">-- Select Name --</option>';
                names.forEach(function(n){
                    opts += '<option value="'+n+'">'+n+'</option>';
                });
                $('#nameSelect').html(opts);
                $('#nameContainer').slideDown();
            }, 'json');
        } else {
            $('#nameContainer').slideUp();
        }
    });

    // Validations?
});
</script>
</body>
</html>
