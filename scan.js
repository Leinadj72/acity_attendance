const scanner = new Html5Qrcode('reader');

function startScanner() {
  scanner.start(
    { facingMode: 'environment' },
    { fps: 10, qrbox: 250 },
    async (qrCodeMessage) => {
      console.log('QR Code detected:', qrCodeMessage);

      try {
        await scanner.stop(); // stop scanning while processing
        const token = qrCodeMessage.trim();

        const res = await fetch('scan_handler.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'token=' + encodeURIComponent(token),
        });

        const text = await res.text();
        console.log('Response from server:', text);
        document.getElementById('result').innerHTML = text;
      } catch (error) {
        console.error('Fetch error:', error);
        document.getElementById(
          'result'
        ).innerHTML = `<div class="alert alert-danger">❌ Error processing QR code. Check console for details.</div>`;
      }

      setTimeout(() => {
        startScanner(); // restart scanning
      }, 3000);
    },
    (errorMessage) => {
      // Optional scanning error callback
    }
  );
}

startScanner();
