<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Academic Audit System</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<style>
  * { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
  }

  :root {
    --primary: #3b82f6;
    --primary-dark: #2563eb;
    --secondary: #8b5cf6;
    --accent: #06b6d4;
    --text: #0f172a;
    --text-light: #64748b;
    --bg: #f8fafc;
    --card-bg: rgba(255, 255, 255, 0.9);
    --shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
    --shadow-hover: 0 30px 60px rgba(59, 130, 246, 0.2);
  }

  body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: var(--text);
    min-height: 100vh;
    padding: 40px 20px;
    position: relative;
    overflow-x: hidden;
  }

  /* Animated Background Elements */
  body::before,
  body::after {
    content: '';
    position: fixed;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.3;
    animation: float 20s ease-in-out infinite;
    pointer-events: none;
  }

  body::before {
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.4), transparent);
    top: -200px;
    right: -200px;
    animation-delay: 0s;
  }

  body::after {
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(139, 92, 246, 0.4), transparent);
    bottom: -150px;
    left: -150px;
    animation-delay: 10s;
  }

  @keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-30px, 30px) scale(0.9); }
  }

  .container {
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
  }

  /* Header */
  header {
    text-align: center;
    margin-bottom: 60px;
    animation: fadeInDown 0.8s ease;
  }

  header h1 {
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 800;
    background: linear-gradient(135deg, #ffffff 0%, #e0e7ff 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 12px;
    letter-spacing: -1px;
    text-shadow: 0 4px 20px rgba(255, 255, 255, 0.3);
  }

  header p {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
    letter-spacing: 0.5px;
  }

  /* Login Cards */
  .login-section {
    display: flex;
    justify-content: center;
    gap: 30px;
    flex-wrap: wrap;
    margin-bottom: 70px;
    animation: fadeInUp 1s ease 0.2s backwards;
  }

  .login-card {
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 45px 40px;
    width: 300px;
    text-align: center;
    box-shadow: var(--shadow);
    border: 1px solid rgba(255, 255, 255, 0.3);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
  }

  .login-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.4s ease;
  }

  .login-card:hover::before {
    transform: scaleX(1);
  }

  .login-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-hover);
    border-color: rgba(59, 130, 246, 0.3);
  }

  .icon-wrapper {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
  }

  .login-card:hover .icon-wrapper {
    transform: rotate(5deg) scale(1.1);
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
  }

  .login-card i {
    font-size: 2.2rem;
    color: white;
  }

  .login-card h3 {
    font-size: 1.5rem;
    color: var(--text);
    margin-bottom: 20px;
    font-weight: 700;
  }

  .login-card a {
    display: inline-block;
    font-weight: 600;
    color: white;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    text-decoration: none;
    padding: 14px 40px;
    border-radius: 12px;
    transition: all 0.3s ease;
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
    font-size: 1rem;
  }

  .login-card a:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary) 100%);
    transform: scale(1.05);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.5);
  }

  /* Management Section */
  .section-title {
    text-align: center;
    color: white;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 40px;
    animation: fadeInUp 1s ease 0.4s backwards;
  }

  .section-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 30px;
    animation: fadeInUp 1.2s ease 0.6s backwards;
  }

  .section {
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 40px;
    box-shadow: var(--shadow);
    border: 1px solid rgba(255, 255, 255, 0.3);
    text-align: center;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
  }

  .section::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05));
    opacity: 0;
    transition: opacity 0.3s ease;
  }

  .section:hover::after {
    opacity: 1;
  }

  .section:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-hover);
    border-color: rgba(59, 130, 246, 0.3);
  }

  .section-icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
  }

  .section:hover .section-icon {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    transform: scale(1.1);
  }

  .section i {
    font-size: 2rem;
    color: var(--primary);
    transition: color 0.3s ease;
  }

  .section:hover i {
    color: white;
  }

  .section h2 {
    font-size: 1.5rem;
    color: var(--text);
    margin-bottom: 12px;
    font-weight: 700;
  }

  .section p {
    color: var(--text-light);
    font-size: 0.95rem;
    margin-bottom: 25px;
    line-height: 1.6;
  }

  .section button {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    padding: 12px 30px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.25);
  }

  .section button:hover {
    background: linear-gradient(135deg, var(--primary-dark), var(--secondary));
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
  }

  /* Footer */
  footer {
    margin-top: 80px;
    text-align: center;
    font-size: 0.95rem;
    color: rgba(255, 255, 255, 0.8);
    animation: fadeInUp 1.5s ease 0.8s backwards;
  }

  /* Animations */
  @keyframes fadeInDown {
    from { 
      opacity: 0; 
      transform: translateY(-30px); 
    }
    to { 
      opacity: 1; 
      transform: translateY(0); 
    }
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

  /* Responsive */
  @media (max-width: 768px) {
    body { padding: 30px 15px; }
    header h1 { font-size: 2.5rem; }
    .login-card, .section { padding: 30px 25px; }
    .section-container { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<div class="container">
  <header>
    <h1>🎓 Academic Audit System</h1>
    <p>Empowering institutional excellence through transparency</p>
  </header>

  <!-- Login Section -->
  <div class="login-section">
    <div class="login-card">
      <div class="icon-wrapper">
        <i class="fa-solid fa-user-tie"></i>
      </div>
      <h3>In-Charge Portal</h3>
      <a href="incharge_login.php">Login</a>
    </div>

    <div class="login-card">
      <div class="icon-wrapper">
        <i class="fa-solid fa-user-graduate"></i>
      </div>
      <h3>Head Portal</h3>
      <a href="head_login.php?role=head">Login</a>
    </div>
  </div>

  <!-- Management Section -->
  <h2 class="section-title">Quick Registration</h2>
  <div class="section-container">
    <div class="section">
      <div class="section-icon">
        <i class="fa-solid fa-user-plus"></i>
      </div>
      <h2>Register In-Charge</h2>
      <p>Create new incharge accounts for department faculty members</p>
      <button onclick="window.location.href='register_incharge.php'">Register Now</button>
    </div>

    <div class="section">
      <div class="section-icon">
        <i class="fa-solid fa-user-tie"></i>
      </div>
      <h2>Register Head</h2>
      <p>Register department heads for administrative access</p>
      <button onclick="window.location.href='register_head.php'">Register Now</button>
    </div>
  </div>
</div>

<footer>
  © 2025 Academic Audit System — Built for Excellence
</footer>

</body>
</html>
