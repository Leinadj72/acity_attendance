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
  <script>
    const resultDiv = document.getElementById('result');
    const html5QrCode = new Html5Qrcode("reader");

    function onScanSuccess(decodedText, decodedResult) {
      html5QrCode.stop();

      const tokenData = JSON.parse(decodedText); // assuming it's a JSON string
      const token = tokenData.token;

      // Send token to PHP for verification
      fetch('scan_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'token=' + encodeURIComponent(token)
      })
      .then(res => res.text())
      .then(html => {
        resultDiv.innerHTML = html;

        // Restart scanner after 5 seconds
        setTimeout(() => {
          resultDiv.innerHTML = '';
          html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: 250 }, onScanSuccess);
        }, 5000);
      })
      .catch(() => {
        resultDiv.innerHTML = '<div class="alert alert-danger">⚠️ Failed to process QR code.</div>';
      });
    }

    Html5Qrcode.getCameras().then(devices => {
      if (devices.length) {
        html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: 250 }, onScanSuccess);
      } else {
        resultDiv.innerHTML = '<div class="alert alert-warning">No camera found</div>';
      }
    });
  </script>
</body>
</html>
