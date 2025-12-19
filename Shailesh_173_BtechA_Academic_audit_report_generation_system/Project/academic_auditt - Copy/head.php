
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['head_id']) || ($_SESSION['role'] ?? '') !== 'head') {
    header("Location: head_login.php");
    exit();
}


$username = $_SESSION['username'] ?? 'Head';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Head Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
:root {
  --primary: #06b6d4;
  --primary-dark: #0891b2;
  --accent: #8b5cf6;
  --success: #10b981;
  --text-dark: #0f172a;
  --text-light: #64748b;
  --white: #ffffff;
  --bg-light: #f8fafc;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
  --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
  --shadow-lg: 0 8px 24px rgba(0,0,0,0.15);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  background: #f1f5f9;
  min-height: 100vh;
}

/* Header */
header {
  background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
  color: var(--white);
  padding: 24px 48px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 4px 20px rgba(8,145,178,0.25);
  position: sticky;
  top: 0;
  z-index: 100;
}

header h2 {
  font-weight: 800;
  font-size: 26px;
  letter-spacing: -0.5px;
  display: flex;
  align-items: center;
  gap: 14px;
  text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

header h2::before {
  content: '📊';
  font-size: 32px;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
}

.user-info {
  display: flex;
  align-items: center;
  gap: 16px;
  font-size: 15px;
  font-weight: 600;
}

.user-name {
  padding: 10px 20px;
  background: rgba(255,255,255,0.2);
  border-radius: 25px;
  border: 2px solid rgba(255,255,255,0.3);
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

header a {
  color: var(--white);
  text-decoration: none;
  padding: 10px 24px;
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  border-radius: 10px;
  font-weight: 700;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 12px rgba(220,38,38,0.3);
  border: 2px solid rgba(255,255,255,0.2);
}

header a:hover {
  background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(220,38,38,0.4);
}

/* Navigation */
nav {
  margin: 32px 48px 24px;
  display: flex;
  gap: 16px;
  background: var(--white);
  padding: 8px;
  border-radius: 16px;
  box-shadow: var(--shadow-md);
}

nav button {
  flex: 1;
  padding: 16px 28px;
  border: none;
  border-radius: 12px;
  background: transparent;
  color: var(--text-light);
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  letter-spacing: 0.3px;
}

nav button:hover {
  background: linear-gradient(135deg, rgba(6,182,212,0.1) 0%, rgba(139,92,246,0.1) 100%);
  color: var(--primary);
  transform: translateY(-1px);
}

nav button.active {
  background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
  color: var(--white);
  box-shadow: 0 4px 16px rgba(6,182,212,0.3);
  transform: translateY(-1px);
}

nav button.active::after {
  content: '';
  position: absolute;
  bottom: -8px;
  left: 50%;
  transform: translateX(-50%);
  width: 40%;
  height: 3px;
  background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
  border-radius: 2px;
}

/* Content Area */
#content {
  margin: 0 48px 40px;
  background: transparent;
  min-height: 500px;
  animation: fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Footer */
footer {
  text-align: center;
  color: var(--text-light);
  margin: 32px 20px 24px;
  font-size: 14px;
  font-weight: 600;
  padding: 20px;
  background: var(--white);
  border-radius: 12px;
  box-shadow: var(--shadow-sm);
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
}

/* Loading State */
.loading {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  font-size: 18px;
  color: var(--text-dark);
  font-weight: 600;
  background: var(--white);
  border-radius: 16px;
  box-shadow: var(--shadow-md);
}

/* Responsive */
@media (max-width: 768px) {
  header {
    padding: 20px 24px;
    flex-direction: column;
    gap: 16px;
  }
  
  header h2 {
    font-size: 22px;
  }
  
  nav {
    margin: 20px 24px 16px;
    flex-direction: column;
    gap: 8px;
  }
  
  nav button {
    padding: 14px 20px;
  }
  
  nav button.active::after {
    display: none;
  }
  
  #content {
    margin: 0 24px 32px;
  }
  
  footer {
    margin: 24px 24px 20px;
  }
}

/* Smooth Scrollbar */
::-webkit-scrollbar {
  width: 10px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, var(--primary-dark) 0%, var(--accent) 100%);
}
</style>
</head>
<body>

<header>
  <h2>Head Dashboard</h2>
  <div class="user-info">
    <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
    <a href="logout.php">Logout</a>
  </div>
</header>



<nav>
  <button data-page="send_request.php">Send Request</button>
  <button data-page="submitted_reports_new.php">Submitted Reports</button>
  <button data-page="manage_incharge.php">Manage Incharge</button>
</nav>

<div id="content">Loading...</div>

<script>
function loadPage(p){
  $("#content").load(p, function(response, status, xhr){
    if(status == "error"){
      $("#content").html("<p style='color:red;'>Error loading page: " + xhr.status + "</p>");
    }
  });
}

$(document).ready(function(){
  loadPage('send_request.php');
  $('nav button').click(function(){
    loadPage($(this).data('page'));
  });
});
</script>

<footer>© Academic Audit System</footer>
</body>
</html>