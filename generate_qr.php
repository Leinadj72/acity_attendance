<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Test QR Code Generator (Roll Number Only)</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
  <style>
    #qrcode {
      margin-top: 20px;
    }
    #downloadBtn {
      margin-top: 10px;
      display: none;
    }
    #loadingSpinner {
      display: none;
      margin-top: 10px;
    }
  </style>
</head>
<body class="container py-5">
  <h2 class="mb-4">Generate QR Code (Roll Number Only)</h2>

  <form id="qrForm" novalidate>
    <div class="mb-3">
      <label for="rollNumberInput" class="form-label">Roll Number / Staff ID</label>
      <input
        id="rollNumberInput"
        type="text"
        name="roll_number"
        class="form-control"
        required
        placeholder="e.g. 12345678901"
      />
    </div>

    <button type="submit" class="btn btn-primary">Generate QR Code</button>
  </form>

  <div id="loadingSpinner" class="spinner-border text-primary" role="status" aria-hidden="true"></div>
  <div id="qrcode" class="mt-4 text-center"></div>
  <button id="downloadBtn" class="btn btn-success" aria-label="Download QR code">Download QR Code</button>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    const form = document.getElementById('qrForm');
    const qrcodeDiv = document.getElementById('qrcode');
    const downloadBtn = document.getElementById('downloadBtn');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      qrcodeDiv.innerHTML = '';
      downloadBtn.style.display = 'none';

      const rollNumber = form.roll_number.value.trim();

      if (!rollNumber) {
        alert('Please enter a valid roll number.');
        return;
      }

      submitBtn.disabled = true;
      loadingSpinner.style.display = 'inline-block';

      try {
        new QRCode(qrcodeDiv, {
          text: rollNumber,
          width: 300,
          height: 300,
          colorDark: '#000000',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.H,
        });

        downloadBtn.style.display = 'inline-block';
        downloadBtn.onclick = () => {
          const canvas = qrcodeDiv.querySelector('canvas');
          if (canvas) {
            const url = canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.href = url;
            link.download = `qr_${rollNumber}.png`;
            link.click();
          } else {
            alert('QR code not found!');
          }
        };
      } catch (error) {
        alert('Error: ' + error.message);
      } finally {
        loadingSpinner.style.display = 'none';
        submitBtn.disabled = false;
      }
    });
  </script>
</body>
</html>
