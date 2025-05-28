const scanner = new Html5Qrcode('reader');

function startScanner() {
  scanner.start(
    { facingMode: 'environment' },
    { fps: 10, qrbox: 250 },
    (qrCodeMessage) => {
      scanner.stop().then(() => {
        // The token is expected to be a plain string (not JSON)
        const token = qrCodeMessage.trim();

        fetch('', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'token=' + encodeURIComponent(token),
        })
          .then((res) => res.text())
          .then((html) => {
            document.getElementById('result').innerHTML = html;
            setTimeout(() => {
              startScanner();
            }, 3000);
          })
          .catch(() => {
            document.getElementById(
              'result'
            ).innerHTML = `<div class="alert alert-danger">❌ Error processing QR code.</div>`;
            setTimeout(() => {
              startScanner();
            }, 3000);
          });
      });
    },
    (errorMessage) => {
      // Ignore scan errors
    }
  );
}

startScanner();
