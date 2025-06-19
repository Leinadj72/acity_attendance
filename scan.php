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

    #status-message {
      font-weight: bold;
      font-size: 1.1rem;
      text-align: center;
    }

    .alert {
      font-size: 0.95rem;
    }

    .btn-lg {
      min-width: 140px;
    }

    .action-buttons {
      display: flex;
      justify-content: center;
      gap: 1.5rem;
      margin-top: 2rem;
    }

    footer {
      margin-top: 3rem;
      text-align: center;
      font-size: 0.9rem;
      color: #6c757d;
    }
  </style>
</head>

<body class="container py-5">

  <h2 class="text-center mb-4">📸 QR Device Management</h2>

  <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-info text-center"><?= htmlspecialchars($_GET['message']) ?></div>
  <?php endif; ?>

  <div class="action-buttons">
    <a href="timein.php" class="btn btn-success btn-lg">🟢 Time In</a>
    <a href="timeout.php" class="btn btn-danger btn-lg">🔴 Time Out</a>
  </div>

  <footer class="mt-5">
    &copy; <?= date("Y") ?> ACC Device Management
  </footer>

</body>

</html>