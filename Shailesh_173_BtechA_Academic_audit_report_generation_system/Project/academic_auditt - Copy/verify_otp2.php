<?php
session_start();
$message = "";

// Redirect if no OTP set for head
if (!isset($_SESSION['head_otp'])) {
    header("Location: forgot_password2.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST['otp'];

    // Check OTP and expiration
    if ($entered_otp == $_SESSION['head_otp'] && time() <= $_SESSION['head_otp_expires']) {
        header("Location: reset_password2.php");
        exit;
    } else {
        $message = "Invalid or expired OTP! Try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify OTP | Academic Audit System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
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
.btn:hover {
  background: #1e40af;
  transform: scale(1.02);
}
.msg {
  color: red;
  margin-top: 10px;
}
a {
  color:#2563eb;
  text-decoration:none;
  display:inline-block;
  margin-top:12px;
}
</style>
</head>
<body>
<div class="container">
  <h2>Verify OTP</h2>
  <form method="POST">
    <input type="text" name="otp" placeholder="Enter OTP" required>
    <button type="submit" class="btn">Verify</button>
  </form>
  <p class="msg"><?php echo $message; ?></p>
  <a href="forgot_password2.php">Back to Forgot Password</a>
</div>
</body>
</html>
