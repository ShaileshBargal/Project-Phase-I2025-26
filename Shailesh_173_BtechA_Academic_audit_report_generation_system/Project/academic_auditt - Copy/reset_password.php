<?php
session_start();
require 'db.php';

if (!isset($_SESSION['email'])) {
    header("Location: forgot_password.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    if ($new_password != $confirm_password) {
        $message = "Passwords do not match!";
    } else {
        $email = $_SESSION["email"];
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password=? WHERE email=?");
        $stmt->bind_param("ss", $hashed, $email);

        if ($stmt->execute()) {
            $message = "Password changed successfully!";
            session_destroy();
            header("refresh:2;url=teacher_login.php");
        } else {
            $message = "Error updating password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password | Academic Audit Report System</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(120deg, #f0f4ff, #ffffff);
  margin: 0;
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
}
.container {
  background: #fff;
  padding: 40px;
  border-radius: 20px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  width: 90%;
  max-width: 420px;
  text-align: center;
}
h2 {
  color: #2563eb;
  margin-bottom: 25px;
  font-weight: 600;
}
input {
  width: 100%;
  padding: 12px;
  border-radius: 10px;
  border: 1px solid #cbd5e1;
  margin-bottom: 18px;
  font-size: 1rem;
}
.btn {
  width: 100%;
  background: #2563eb;
  color: white;
  border: none;
  padding: 12px;
  font-size: 1rem;
  font-weight: 600;
  border-radius: 10px;
  cursor: pointer;
}
.msg { color: red; margin-top: 10px; }
</style>
</head>
<body>
<div class="container">
  <h2>Reset Password</h2>
  <form method="POST">
    <input type="password" name="new_password" placeholder="New Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <button type="submit" class="btn">Reset Password</button>
  </form>
  <p class="msg"><?php echo $message ?? ''; ?></p>
</div>
</body>
</html>
