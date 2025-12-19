<?php
session_start();
require_once 'db.php';              // Database connection
require 'vendor/autoload.php';      // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists in head table
        $stmt = $conn->prepare("SELECT id FROM head WHERE email = ?");
        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows === 1) {
                // Generate 6-digit OTP
                $otp = random_int(100000, 999999);

                // Store OTP and expiration in session
                $_SESSION['head_otp'] = (string)$otp;
                $_SESSION['head_email'] = $email;
                $_SESSION['head_otp_expires'] = time() + 300; // 5 minutes

                // Send OTP via PHPMailer
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'shaileshbargal19@gmail.com'; // Your Gmail
                    $mail->Password   = 'nrhtsgmmwwflaelx';           // 16-char App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom($mail->Username, 'Academic Audit System');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'OTP for Password Reset';
                    $mail->Body    = "<p>Hello,</p>
                                      <p>Your OTP for password reset is: <strong>{$otp}</strong></p>
                                      <p>This OTP will expire in 5 minutes.</p>";

                    $mail->send();

                    // Redirect to OTP verification page
                    header("Location: verify_otp2.php");
                    exit;

                } catch (Exception $e) {
                    $error = "Failed to send OTP. Mailer Error: " . htmlspecialchars($mail->ErrorInfo);
                }
            } else {
                $error = "No account found with that email address.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Forgot Password | Academic Audit System</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />
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
h2 { color:#2563eb; margin-bottom:18px; font-weight:600; }
input { width:100%; padding:12px; border-radius:10px; border:1px solid #cbd5e1; margin-bottom:14px; font-size:1rem; }
.btn { width:100%; background:#2563eb; color:#fff; border:none; padding:12px; font-weight:600; border-radius:10px; cursor:pointer; }
.msg { margin-top:12px; font-weight:600; }
.msg.error { color:#b91c1c; background:#fee2e2; padding:10px; border-radius:8px; }
.msg.info { color:#166534; background:#dcfce7; padding:10px; border-radius:8px; }
a { color:#2563eb; text-decoration:none; display:inline-block; margin-top:12px; }
</style>
</head>
<body>
  <div class="container">
    <h2>Forgot Password</h2>
    <form method="post" novalidate>
      <input type="email" name="email" placeholder="Enter your registered email" required>
      <button type="submit" class="btn">Send OTP</button>
    </form>

    <?php if ($error): ?>
      <div class="msg error"><?php echo $error; ?></div>
    <?php elseif ($message): ?>
      <div class="msg info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <a href="head_login.php">Back to Login</a>
  </div>
</body>
</html>
