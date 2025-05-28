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
    #downloadBtn {
      margin-top: 10px;
      display: none;
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
      <input type="text" name="roll_number" class="form-control" required pattern="\d{11}" maxlength="11">
      <small class="text-muted">Must be exactly 11 digits</small>
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
  <button id="downloadBtn" class="btn btn-success">Download QR Code</button>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    const form = document.getElementById('qrForm');
    const qrcodeDiv = document.getElementById('qrcode');
    const downloadBtn = document.getElementById('downloadBtn');

    form.addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(form);
      const rollNumber = formData.get('roll_number');

      if (!/^\d{11}$/.test(rollNumber)) {
        alert("Roll Number must be exactly 11 digits.");
        return;
      }

      const data = {
        date: formData.get('date'),
        roll_number: rollNumber,
        location: formData.get('location'),
        item: formData.get('item')
      };

      const jsonData = JSON.stringify(data);
      qrcodeDiv.innerHTML = '';
      downloadBtn.style.display = 'none';

      const qr = new QRCode(qrcodeDiv, {
        text: jsonData,
        width: 256,
        height: 256
      });

      // Wait for QRCode to render before enabling download
      setTimeout(() => {
        const canvas = qrcodeDiv.querySelector('canvas');
        if (canvas) {
          downloadBtn.onclick = () => {
            const link = document.createElement('a');
            link.download = 'attendance_qr.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
          };
          downloadBtn.style.display = 'inline-block';
        }
      }, 500);
    });
  </script>

</body>
</html>
