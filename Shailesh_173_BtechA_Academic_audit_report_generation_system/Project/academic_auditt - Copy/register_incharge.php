<?php
session_start();
require_once "db.php";

$error = "";
$success = "";

// Fetch roles
$roles = [];
$res = $conn->query("SELECT DISTINCT role_name FROM role_templates ORDER BY role_name");
if($res) while($r = $res->fetch_assoc()) $roles[] = $r['role_name'];
if(!in_array('Teacher', $roles)) $roles[] = 'Teacher';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $role = $_POST['role'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if($role && $name && $email){
        // Check if EMAIL exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        
        if($stmt === false){
            $error = "Database error: " . htmlspecialchars($conn->error) . ". The users table may not exist. Please contact administrator.";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if($stmt->get_result()->num_rows > 0){
                 $error = "This email is already registered.";
            } else {
                 // Check if NAME (Username) exists
                 $stmt2 = $conn->prepare("SELECT id FROM users WHERE username = ?");
                 
                 if($stmt2 === false){
                     $error = "Database error: " . htmlspecialchars($conn->error);
                 } else {
                     $stmt2->bind_param("s", $name);
                     $stmt2->execute();
                     
                     if($stmt2->get_result()->num_rows > 0){
                         $error = "This name '$name' is already registered. Please use a unique identifier (e.g. '$name 2').";
                     } else {
                         // Create User
                         $dummy_pass = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
                         
                         $stmt = $conn->prepare("INSERT INTO users (username, email, incharge_role, password, role) VALUES (?, ?, ?, ?, 'teacher')");
                         
                         if($stmt === false){
                             $error = "Database error: " . htmlspecialchars($conn->error);
                         } else {
                             $stmt->bind_param("ssss", $name, $email, $role, $dummy_pass);
                             
                             if($stmt->execute()){
                                 $link = "set_incharge_password.php";
                                 $success = "Registration Successful! <br>User '$name' registered.<br><br> <a href='$link' class='btn' style='display:inline-block;width:auto;margin-top:10px;'>Verify Email & Set Password -></a>";
                             } else {
                                 $error = "Registration failed: " . $conn->error;
                             }
                         }
                     }
                     $stmt2->close();
                 }
            }
            $stmt->close();
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
  <title>Register Incharge</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
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
    .msg{margin-top:10px;padding:10px;border-radius:8px;font-weight:600;margin-bottom:20px}
    .msg.error{background:#fee2e2;color:#b91c1c}
    .msg.success{background:#d1fae5;color:#047857; text-align:center;}
    .links{margin-top:18px;display:flex;justify-content:center;font-size:.9rem}
    a{color:#2563eb;text-decoration:none;font-weight:600}
  </style>
</head>
<body>
  <div class="container">
    <h1>Academic Audit</h1>
    <h2>Register Incharge</h2>

    <?php if ($error): ?>
      <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="msg success"><?= $success ?></div>
    <?php endif; ?>

    <?php if(!$success): ?>
    <form method="post">
      <label>Select Role</label>
      <select name="role" required>
          <option value="">-- Select Role --</option>
          <?php foreach($roles as $r): ?>
             <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
          <?php endforeach; ?>
      </select>
      
      <label>Full Name</label>
      <input type="text" name="name" required placeholder="e.g. Dr. Smith" autocomplete="off">
      
      <label>Email Address</label>
      <input type="email" name="email" required placeholder="Enter your email">
      
      <div style="font-size:0.8rem;color:#64748b;margin-bottom:15px;text-align:left;">
        Note: You will use this email to verify OTP and set your password later.
      </div>

      <button type="submit" class="btn">Register</button>
    </form>
    <?php endif; ?>

    <div class="links">
      <a href="incharge_login.php">Back to Login</a>
    </div>
  </div>
</body>
</html>
