<?php include 'db.php'; ?>
<?php date_default_timezone_set("Africa/Accra"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>QR Attendance Scanner</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    #reader {
      width: 100%;
      max-width: 400px;
      margin: 0 auto;
    }
    #status-message {
      font-weight: bold;
      font-size: 1.1rem;
      text-align: center;
    }
    #result {
      margin-top: 1rem;
    }
    .alert {
      font-size: 0.95rem;
    }
    .spinner-border {
      width: 2rem;
      height: 2rem;
    }
  </style>
</head>
<body class="container py-5">
  <h2 class="text-center mb-4">📸 QR Attendance System</h2>

  <div class="text-center">
    <a href="timein.php" class="btn btn-success btn-lg me-3">🟢 Time In</a>
    <a href="timeout.php" class="btn btn-danger btn-lg">🔴 Time Out</a>
  </div>
</body>


</html>
