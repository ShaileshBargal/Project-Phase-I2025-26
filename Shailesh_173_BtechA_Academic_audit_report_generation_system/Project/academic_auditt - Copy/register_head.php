<?php
require_once "db.php";
session_start();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    // Validation
    if ($password !== $confirm) {
        $message = "<div class='msg error'>Passwords do not match!</div>";

    } else {
        // Check if username, email, or mobile already exists
        $check = $conn->prepare("SELECT * FROM head WHERE username = ? OR email = ?");
        
        if ($check === false) {
            $message = "<div class='msg error'>Database error: " . htmlspecialchars($conn->error) . "</div>";
        } else {
            $check->bind_param("ss", $username, $email);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                $message = "<div class='msg error'>Username or Email already exists!</div>";
            } else {
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO head (username, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $email, $password_hashed);

                if ($stmt->execute()) {
                    $message = "<div class='msg success'>Registration successful! <a href='head_login.php'>Login here</a>.</div>";
                } else {
                    $message = "<div class='msg error'>Something went wrong. Try again!</div>";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register Head | Academic Audit</title>
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
  animation: fadeIn 1s ease-in-out;
}
@keyframes fadeIn {
  from {opacity: 0; transform: translateY(20px);}
  to {opacity: 1; transform: translateY(0);}
}
h1 { color: #1e40af; margin-bottom: 10px; font-weight: 700; font-size: 1.5rem; }
h2 { color: #2563eb; margin-bottom: 25px; font-size: 1.1rem; font-weight: 600; }
label { display: block; text-align: left; margin-bottom: 6px; font-weight: 600; color: #374151; }
input { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 18px; font-size: 1rem; transition: 0.3s ease; }
input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 6px rgba(37,99,235,0.3); }
.btn { width: 100%; background: #2563eb; color: white; border: none; padding: 12px; font-size: 1rem; font-weight: 600; border-radius: 10px; cursor: pointer; transition: 0.3s ease; }
.btn:hover { background: #1e40af; transform: scale(1.02); }
.msg { margin-top: 10px; padding: 10px; border-radius: 8px; font-weight: 600; }
.msg.error { background: #fee2e2; color: #b91c1c; }
.msg.success { background: #dcfce7; color: #166534; }
.links { margin-top: 16px; }
a { color: #2563eb; font-weight: 600; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="container">
  <h1>Academic Audit Report System</h1>
  <h2>Register Head</h2>

  <?php echo $message; ?>

  <form method="post">
    <label>Username</label>
    <input type="text" name="username" placeholder="Enter username" required>

    <label>Email</label>
    <input type="email" name="email" placeholder="Enter email" required>



    <label>Password</label>
    <input type="password" name="password" placeholder="Enter password" required>

    <label>Confirm Password</label>
    <input type="password" name="confirm_password" placeholder="Confirm password" required>

    <button type="submit" class="btn">Register</button>
  </form>

  <div class="links">
    <a href="head_login.php">Already have an account? Login</a>
  </div>
</div>
</body>
</html>
