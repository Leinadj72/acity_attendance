<?php include 'db.php'; ?>
<?php date_default_timezone_set("Africa/Accra"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>QR Attendance Scan</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-5">
  <h2 class="mb-4">Scan QR to Log Attendance</h2>

  <div id="reader" style="width: 300px;"></div>
  <div id="result" class="mt-4"></div>

  <script src="https://unpkg.com/html5-qrcode@2.3.7/html5-qrcode.min.js"></script>
  <script src="scan.js"></script>
</body>
</html>
