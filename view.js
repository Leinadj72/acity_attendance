const html5QrCode = new Html5Qrcode('reader');
const resultDiv = document.getElementById('result');
let isProcessing = false;

function postToken(token) {
  fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'token=' + encodeURIComponent(token),
  })
    .then((res) => res.text())
    .then((html) => {
      resultDiv.innerHTML = html;
      // Restart scanner after 5 seconds
      setTimeout(() => {
        resultDiv.innerHTML = '';
        isProcessing = false;
        html5QrCode.start(
          { facingMode: 'environment' },
          { fps: 10, qrbox: 250 },
          onScanSuccess
        );
      }, 5000);
    })
    .catch(() => {
      resultDiv.innerHTML =
        '<div class="alert alert-danger">Error sending token to server</div>';
      // Restart scanner immediately on error
      isProcessing = false;
      html5QrCode.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: 250 },
        onScanSuccess
      );
    });
}

function onScanSuccess(decodedText, decodedResult) {
  if (isProcessing) return; // Prevent multiple scans while processing
  isProcessing = true;

  html5QrCode
    .stop()
    .then(() => {
      postToken(decodedText);
    })
    .catch((err) => {
      resultDiv.innerHTML = `<div class="alert alert-danger">Failed to stop scanner: ${err}</div>`;
      isProcessing = false;
    });
}

html5QrCode
  .start({ facingMode: 'environment' }, { fps: 10, qrbox: 250 }, onScanSuccess)
  .catch((err) => {
    resultDiv.innerHTML = `<div class="alert alert-danger">Unable to start scanner: ${err}</div>`;
  });
