<!DOCTYPE html>
<html>
<head>
<title>Button Test</title>
<style>
body { font-family: Arial; padding: 50px; background: #f0f0f0; }
.test-btn {
    background: #2563eb;
    color: white;
    padding: 15px 30px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    margin: 10px;
}
.test-btn:hover {
    background: #1e40af;
}
</style>
</head>
<body>

<h1>Registration Button Test</h1>
<p>Click these buttons to test the registration pages:</p>

<button class="test-btn" onclick="window.location.href='register_incharge.php'">Test Incharge Registration</button>
<br>
<button class="test-btn" onclick="window.location.href='register_head.php'">Test Head Registration</button>

<hr>
<p><strong>If the buttons work here but not on index.php, the issue might be:</strong></p>
<ul>
<li>JavaScript conflicts or errors on the index page</li>
<li>CSS preventing button clicks (z-index or pointer-events issues)</li>
<li>Browser console showing errors</li>
</ul>

<p>Check browser console (F12) for JavaScript errors when clicking buttons on index.php</p>

</body>
</html>
