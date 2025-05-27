<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Scan Time In</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <script src="assets/html5-qrcode.min.js"></script>
</head>
<body class="container py-5">
  <h2 class="mb-4">Scan QR - Record Time In</h2>

  <div id="reader" style="width: 300px;"></div>
  <div id="result" class="mt-4"></div>

  <?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;

    $stmt = $conn->prepare("INSERT INTO attendance (date, roll_number, location, item, time_in) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $data['date'], $data['roll_number'], $data['location'], $data['item'], $data['time_in']);

    if ($stmt->execute()) {
      echo "<div class='alert alert-success mt-3'>Time In recorded successfully!</div>";
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
          <input type="hidden" name="date" value="${obj.date}">
          <input type="hidden" name="roll_number" value="${obj.roll_number}">
          <input type="hidden" name="location" value="${obj.location}">
          <input type="hidden" name="item" value="${obj.item}">

          <p><strong>Date:</strong> ${obj.date}</p>
          <p><strong>Roll Number:</strong> ${obj.roll_number}</p>
          <p><strong>Location:</strong> ${obj.location}</p>
          <p><strong>Item:</strong> ${obj.item}</p>

          <div class="mb-3">
            <label class="form-label">Time In</label>
            <input type="time" name="time_in" class="form-control" required>
          </div>

          <button class="btn btn-primary" type="submit">Submit Time In</button>
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
