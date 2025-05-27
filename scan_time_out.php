<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Scan Time Out</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <script src="assets/html5-qrcode.min.js"></script>
</head>
<body class="container py-5">
  <h2 class="mb-4">Scan QR - Record Time Out</h2>

  <div id="reader" style="width: 300px;"></div>
  <div id="result" class="mt-4"></div>

  <?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_number = $_POST['roll_number'];
    $date = $_POST['date'];
    $time_out = $_POST['time_out'];

    $stmt = $conn->prepare("UPDATE attendance SET time_out = ? WHERE roll_number = ? AND date = ?");
    $stmt->bind_param("sss", $time_out, $roll_number, $date);

    if ($stmt->execute()) {
      echo "<div class='alert alert-success mt-3'>Time Out recorded successfully!</div>";
    } else {
      echo "<div class='alert alert-danger mt-3'>Error: " . $conn->error . "</div>";
    }
  }
  ?>

  <script>
    function showForm(data) {
      const obj = JSON.parse(data);

      document.getElementById('result').innerHTML = `
        <form method="POST">
          <input type="hidden" name="roll_number" value="${obj.roll_number}">
          <input type="hidden" name="date" value="${obj.date}">

          <p><strong>Roll Number:</strong> ${obj.roll_number}</p>
          <p><strong>Date:</strong> ${obj.date}</p>

          <div class="mb-3">
            <label class="form-label">Time Out</label>
            <input type="time" name="time_out" class="form-control" required>
          </div>

          <button class="btn btn-success" type="submit">Submit Time Out</button>
        </form>
      `;
    }

    const scanner = new Html5Qrcode("reader");
    scanner.start(
      { facingMode: "environment" },
      { fps: 10, qrbox: 250 },
      qrCodeMessage => {
        scanner.stop().then(() => {
          showForm(qrCodeMessage);
        });
      },
      errorMessage => {}
    );
  </script>

</body>
</html>
