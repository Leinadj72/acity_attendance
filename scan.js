const scanner = new Html5Qrcode('reader');
let scannedRollNumber = '';
let scannerTimeout;

const DOM = {
  status: document.getElementById('status-message'),
  result: document.getElementById('result'),
};

function updateStatus(message) {
  DOM.status.textContent = message;
}

function showAlert(type, message) {
  DOM.result.innerHTML = `<div class="alert alert-${type} text-center">${message}</div>`;
}

async function handleQRCodeScan(qrCode) {
  console.log('QR Code detected:', qrCode);
  await scanner.stop();
  scannedRollNumber = qrCode.trim();
  updateStatus('⌛ Verifying roll number...');

  try {
    const res = await fetch('scan_handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `roll_number=${encodeURIComponent(scannedRollNumber)}`,
    });

    const result = await res.json();

    if (result.status !== 'require_inputs') {
      showAlert('danger', result.message);
      updateStatus('📷 Looking for QR code...');
      setTimeout(startScanner, 3000);
      return;
    }

    DOM.result.innerHTML = `
      <div class="alert alert-success text-center mb-3">
        👤 <strong>${result.roll_number}</strong> recognized. Proceed with Time In.
      </div>
      <form id="timein-form">
        <label for="item">Select Item:</label>
        <select id="item" name="item" class="form-select mb-2" required>
          <option value="">-- Select Item --</option>
        </select>

        <label for="tag">Enter or Scan Tag:</label>
        <input type="text" id="tag" name="tag" class="form-control mb-2" required autocomplete="off">
        <div id="tag-reader" class="mt-3 border rounded shadow-sm" style="max-width: 300px; margin: 0 auto;"></div>
        <div class="text-muted text-center mt-1" style="font-size: 0.9rem;">Or scan tag QR code</div>

        <label for="location">Select Location:</label>
        <select id="location" name="location" class="form-select mb-2" required>
          <option value="">-- Select Location --</option>
        </select>

        <button type="submit" class="btn btn-primary w-100 mt-2">Record Time In</button>
      </form>
    `;

    const itemSelect = document.getElementById('item');
    const locationSelect = document.getElementById('location');

    result.items.forEach((item) => {
      const opt = document.createElement('option');
      opt.value = item;
      opt.textContent = item;
      itemSelect.appendChild(opt);
    });

    result.locations.forEach((loc) => {
      const opt = document.createElement('option');
      opt.value = loc;
      opt.textContent = loc;
      locationSelect.appendChild(opt);
    });

    const tagInput = document.getElementById('tag');
    const tagScanner = new Html5Qrcode('tag-reader');
    tagScanner.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: 200 },
      async (code) => {
        const tag = code.trim();

        await tagScanner.stop();

        try {
          const res = await fetch('verify_tag.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `tag=${encodeURIComponent(tag)}&item=${encodeURIComponent(
              document.getElementById('item').value
            )}`,
          });

          const data = await res.json();

          if (data.valid) {
            tagInput.value = tag;
          } else {
            alert('❌ Invalid tag for selected item.');
            tagInput.value = '';
            startTagScanner();
          }
        } catch (err) {
          console.error('Error verifying tag:', err);
          alert('❌ Error verifying tag. Try again.');
        }
      },
      (err) => {}
    );

    const form = document.getElementById('timein-form');
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new URLSearchParams({
        roll_number: scannedRollNumber,
        item: form.item.value,
        tag: form.tag.value.trim(),
        location: form.location.value,
      });

      try {
        const res = await fetch('time_in_handler.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: formData.toString(),
        });

        const data = await res.json();
        if (data.status === 'success') {
          showAlert('success', data.message);
        } else {
          showAlert('danger', data.message);
        }
      } catch (err) {
        console.error('Submit error:', err);
        showAlert('danger', '❌ Network error.');
      }

      updateStatus('📷 Looking for QR code...');
      setTimeout(startScanner, 5000);
    });
  } catch (err) {
    console.error('Fetch error:', err);
    showAlert('danger', '❌ Network or server error.');
    updateStatus('📷 Looking for QR code...');
    startScanner();
  }
}

function startScanner() {
  updateStatus('📷 Looking for QR code...');
  scanner
    .start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: 250 },
      (qrCodeMessage) => {
        clearTimeout(scannerTimeout);
        handleQRCodeScan(qrCodeMessage);
      },
      (error) => {
        console.warn('QR scan error:', error);
      }
    )
    .then(() => {
      // Start a timeout to auto stop after 30 seconds
      /* scannerTimeout = setTimeout(() => {
        scanner.stop().then(() => {
          updateStatus('⏰ QR scan timed out. Please try again.');
          showAlert('warning', 'No QR code detected. Try again.');
        });
      }, 30000); */
    });
}

updateStatus('📷 Looking for QR code...');
startScanner();
