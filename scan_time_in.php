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
    $data = json_decode($_POST['data'], true);

    $date = $data['date'];
    $roll = $data['roll_number'];
    $location = $data['location'];
    $item = $data['item'];
    $timeIn = date("H:i:s"); // current server time

    // 🔍 Check if item at location is already scanned in but not returned
    $check = $conn->prepare("SELECT id FROM attendance WHERE item = ? AND location = ? AND time_out IS NULL");
    $check->bind_param("ss", $item, $location);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
      echo "<div class='alert alert-danger mt-3'>This item has already been checked out at this location and has not yet been returned.</div>";
    } else {
      // ✅ Safe to record new time_in
      $stmt = $conn->prepare("INSERT INTO attendance (date, roll_number, location, item, time_in) VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("sssss", $date, $roll, $location, $item, $timeIn);

      if ($stmt->execute()) {
        echo "<div class='alert alert-success mt-3'>Time In recorded for <strong>$roll</strong> at <strong>$timeIn</strong></div>";
      } else {
        echo "<div class='alert alert-danger mt-3'>Error: " . $conn->error . "</div>";
      }
    }
  }
  ?>

  <script>
    const scanner = new Html5Qrcode("reader");

    scanner.start(
      { facingMode: "environment" },
      { fps: 10, qrbox: 250 },
      qrCodeMessage => {
        scanner.stop().then(() => {
          // Send data to server using POST
          fetch("", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "data=" + encodeURIComponent(qrCodeMessage)
          })
          .then(res => res.text())
          .then(html => {
            document.body.innerHTML = html;
          });
        });
      },
      errorMessage => {
        // Silence scan errors
      }
    );
  </script>

</body>
</html>
