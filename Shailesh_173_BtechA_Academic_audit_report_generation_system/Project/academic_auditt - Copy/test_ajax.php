<!DOCTYPE html>
<html>
<head>
<title>AJAX Test</title>
</head>
<body>
<h2>Testing submitted_reports.php AJAX endpoint</h2>
<button onclick="testAjax()">Click to Test AJAX</button>
<div id="result" style="margin-top:20px; padding:10px; border:1px solid #ccc;"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function testAjax(){
  const resultDiv = document.getElementById('result');
  resultDiv.innerHTML = 'Loading...';
  
  console.log('Sending AJAX request...');
  
  $.get('submitted_reports.php', {
    action: 'get_reports',
    semester: 'odd',
    year: '2024'
  }, function(resp){
    console.log('Success response:', resp);
    resultDiv.innerHTML = '<pre style="background:#d4edda; padding:10px">SUCCESS!\n\n' + JSON.stringify(resp, null, 2) + '</pre>';
  }, 'json').fail(function(xhr, status, error){
    console.error('AJAX Failed:', status, error);
    console.error('Response:', xhr.responseText);
    resultDiv.innerHTML = '<pre style="background:#f8d7da; padding:10px; color:red">FAILED!\n\nStatus: ' + status + '\nError: ' + error + '\n\nResponse:\n' + xhr.responseText + '</pre>';
  });
}
</script>
</body>
</html>
