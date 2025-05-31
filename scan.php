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
  <h2 class="text-center mb-4">📸 QR Attendance Scanner</h2>

  <div id="reader" class="border rounded shadow-sm"></div>

  <div id="status-message" class="text-muted mt-3">
    <div class="spinner-border text-secondary me-2" role="status">
      <span class="visually-hidden">Loading...</span>
    </div>
    Waiting for QR code...
  </div>

  <div id="result" class="mt-4"></div>

  <!-- HTML5 QR Scanner -->
  <script src="https://unpkg.com/html5-qrcode@2.3.7/html5-qrcode.min.js"></script>

  <!-- Scan Handler Script -->
  <script src="scan.js"></script>
</body>
</html>
