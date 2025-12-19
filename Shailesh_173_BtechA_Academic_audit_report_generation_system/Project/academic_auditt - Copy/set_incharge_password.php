<?php
session_start();
require_once "db.php";

$error = "";
$success = "";
$step = $_SESSION['reset_step'] ?? 1;

// Fetch roles
$roles = [];
$res = $conn->query("SELECT DISTINCT role_name FROM role_templates ORDER BY role_name");
if($res) while($r = $res->fetch_assoc()) $roles[] = $r['role_name'];
if(!in_array('Teacher', $roles)) $roles[] = 'Teacher';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['action']) && $_POST['action'] === 'send_otp'){
        // Step 1
        $role = $_POST['role'] ?? '';
        $email = trim($_POST['email'] ?? '');
        
        if($role && $email){
            // Check DB
            $stmt = $conn->prepare("SELECT id FROM users WHERE incharge_role = ? AND email = ?");
            if(!$stmt) die("DB Error: ".$conn->error);
            $stmt->bind_param("ss", $role, $email);
            $stmt->execute();
            if($stmt->get_result()->num_rows > 0){
                $_SESSION['reset_otp'] = '1234'; // Simulated
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_role'] = $role;
                $_SESSION['reset_step'] = 2;
                $step = 2;
                $success = "OTP sent to $email! (Use 1234)";
            } else {
                $error = "No account found for Role: $role with Email: $email";
            }
        } else {
            $error = "Please fill all fields.";
        }
    } elseif(isset($_POST['action']) && $_POST['action'] === 'set_pass'){
        // Step 2
        $otp = $_POST['otp'] ?? '';
        $pass = $_POST['password'] ?? '';
        
        if($otp === ($_SESSION['reset_otp'] ?? 'xxx')){
            if(!empty($pass)){
                $email = $_SESSION['reset_email'];
                $role = $_SESSION['reset_role'];
                
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ? AND incharge_role = ?");
                $stmt->bind_param("sss", $hash, $email, $role);
                if($stmt->execute()){
                    // Reset session
                    unset($_SESSION['reset_step'], $_SESSION['reset_otp'], $_SESSION['reset_role'], $_SESSION['reset_email']);
                    $success = "Password updated successfully!";
                    $step = 3; // Done
                } else {
                    $error = "Database Error: " . $conn->error;
                }
            } else {
                $error = "Password cannot be empty.";
            }
        } else {
            $error = "Invalid OTP.";
        }
    }
}

// Reset logic if GET "reset"
if(isset($_GET['retry'])){
    unset($_SESSION['reset_step']);
    header("Location: set_incharge_password.php");
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Set Password</title>
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
    .msg{margin-top:10px;padding:10px;border-radius:8px;font-weight:600;margin-bottom:15px}
    .msg.error{background:#fee2e2;color:#b91c1c}
    .msg.success{background:#d1fae5;color:#047857}
    .links{margin-top:18px;display:flex;justify-content:center;font-size:.9rem}
    a{color:#2563eb;text-decoration:none;font-weight:600}
  </style>
</head>
<body>
  <div class="container">
    <h1>Academic Audit</h1>
    <h2>Set Incharge Password</h2>

    <?php if ($error): ?>
      <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="msg success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if($step === 1): ?>
        <form method="post">
            <input type="hidden" name="action" value="send_otp">
            <label>Select Your Role</label>
            <select name="role" required>
                <option value="">-- Select Role --</option>
                <?php foreach($roles as $r): ?>
                    <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
                <?php endforeach; ?>
            </select>
            
            <label>Registered Email</label>
            <input type="email" name="email" required placeholder="Enter your email">
            
            <button type="submit" class="btn">Send OTP</button>
        </form>
    <?php elseif($step === 2): ?>
        <form method="post">
            <input type="hidden" name="action" value="set_pass">
            <p style="margin-bottom:20px;color:#64748b;font-size:0.9rem">
                OTP sent to <strong><?= htmlspecialchars($_SESSION['reset_email']) ?></strong>
            </p>
            
            <label>Enter OTP</label>
            <input type="text" name="otp" required placeholder="Enter OTP (1234)">
            
            <label>New Password</label>
            <input type="password" name="password" required placeholder="Set new password">
            
            <button type="submit" class="btn">Set Password</button>
        </form>
        <div class="links">
             <a href="?retry=1">Incorrect Email? Retry</a>
        </div>
    <?php elseif($step === 3): ?>
        <p style="margin-bottom:20px">Your password has been set successfully.</p>
        <a href="incharge_login.php" class="btn" style="display:block;text-align:center">Login Now</a>
    <?php endif; ?>

    <?php if($step !== 3): ?>
    <div class="links">
      <a href="incharge_login.php">Back to Login</a>
    </div>
    <?php endif; ?>
  </div>
</body>
</html>
