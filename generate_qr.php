<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Generate QR Code</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    #qrcode {
      margin-top: 20px;
    }
  </style>
</head>
<body class="container py-5">

  <h2 class="mb-4">Generate Attendance QR Code</h2>

  <form id="qrForm">
    <div class="mb-3">
      <label class="form-label">Date</label>
      <input type="date" name="date" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Roll Number</label>
      <input type="text" name="roll_number" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Lecture Hall / Location</label>
      <input type="text" name="location" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Item</label>
      <input type="text" name="item" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary">Generate QR Code</button>
  </form>

  <div id="qrcode"></div>

  <script src="assets/qrcode.min.js"></script>
  <script>
    const form = document.getElementById('qrForm');
    const qrcodeDiv = document.getElementById('qrcode');

    form.addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(form);
      const data = {
        date: formData.get('date'),
        roll_number: formData.get('roll_number'),
        location: formData.get('location'),
        item: formData.get('item')
      };

      const jsonData = JSON.stringify(data);
      qrcodeDiv.innerHTML = ''; // Clear previous
      new QRCode(qrcodeDiv, {
        text: jsonData,
        width: 256,
        height: 256
      });
    });
  </script>

</body>
</html>
