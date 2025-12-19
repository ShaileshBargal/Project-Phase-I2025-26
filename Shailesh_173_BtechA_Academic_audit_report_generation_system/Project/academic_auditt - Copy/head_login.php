<?php
session_start();
require_once "db.php";

// ✅ Redirect to dashboard if already logged in as head
if (!empty($_SESSION['head_id']) && !empty($_SESSION['role']) && $_SESSION['role'] === 'head') {
    header("Location: head.php");
    exit;
}

$error = "";

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Prepare and execute securely
    $stmt = $conn->prepare("SELECT id, username, password FROM head WHERE username = ? OR email = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $user = $res->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // ✅ Set session for head
                $_SESSION['head_id']  = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = 'head';
                header("Location: head.php");
                exit;
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "No account found with that username or email!";
        }
        $stmt->close();
    } else {
        $error = "Database error.";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Head Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box}
    body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#e0ecff,#fff);margin:0;height:100vh;display:flex;align-items:center;justify-content:center}
    .container{background:#fff;padding:45px;border-radius:18px;box-shadow:0 10px 35px rgba(0,0,0,.12);width:90%;max-width:420px;text-align:center;animation:fadeIn .9s}
    @keyframes fadeIn{from{opacity:0;transform:translateY(25px)}to{opacity:1;transform:translateY(0)}}
    h1{color:#1e3a8a;margin:0 0 6px;font-weight:700;font-size:1.6rem} h2{color:#2563eb;margin:0 0 22px;font-weight:600}
    label{display:block;text-align:left;margin-bottom:6px;font-weight:600;color:#374151;font-size:.95rem}
    input{width:100%;padding:12px 14px;border-radius:10px;border:1px solid #cbd5e1;margin-bottom:20px;background:#f9fafb;transition:.3s}
    input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 7px rgba(37,99,235,.3);background:#fff}
    .btn{width:100%;background:linear-gradient(90deg,#2563eb,#1e40af);color:#fff;padding:12px;border-radius:10px;border:0;font-weight:600;cursor:pointer}
    .btn:hover{transform:scale(1.02)}
    .msg{margin-top:10px;padding:10px;border-radius:8px;font-weight:600}
    .msg.error{background:#fee2e2;color:#b91c1c}
    .links{margin-top:18px;display:flex;justify-content:space-between;font-size:.9rem}
    a{color:#2563eb;text-decoration:none;font-weight:600}
  </style>
</head>
<body>
  <div class="container">
    <h1>Academic Audit Report System</h1>
    <h2>Head Login</h2>

    <?php if ($error): ?>
      <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <label>Username or Email</label>
      <input type="text" name="username" required placeholder="Enter username or email">
      <label>Password</label>
      <input type="password" name="password" required placeholder="Enter password">
      <button type="submit" class="btn">Login</button>
    </form>

    <div class="links">
      <a href="register_head.php">Create Account</a>
      <a href="forgot_password2.php">Forgot Password?</a>
    </div>
  </div>
</body>
</html>
