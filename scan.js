const scanner = new Html5Qrcode('reader');
let qrCodeMessage = '';

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

function setupTagFormSubmission(form) {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const tag = form.tag.value.trim();

    if (!tag) return alert('Please enter a tag number.');

    const formData = new URLSearchParams({
      token: qrCodeMessage,
      tag,
    });

    try {
      const res = await fetch('tag_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString(),
      });

      const text = await res.text();
      console.log('Raw response:', text);
      const result = JSON.parse(text);

      if (result.status === 'success') {
        showAlert('success', result.message);
        setTimeout(() => {
          updateStatus('📷 Looking for QR code...');
          startScanner();
        }, 5000);
      } else {
        showAlert('danger', `❌ ${result.message || result.status}`);
        // Re-display the tag form so user can retry
        DOM.result.innerHTML += `
          <form id="tag-form" class="mb-3 mt-3">
            <label for="tag" class="form-label">Tag Number:</label>
            <input type="text" id="tag" name="tag" class="form-control mb-2" required autocomplete="off" autofocus>
            <button type="submit" class="btn btn-primary w-100">Try Again</button>
          </form>
        `;
        const tagForm = document.getElementById('tag-form');
        if (tagForm) {
          setupTagFormSubmission(tagForm);
        }
      }
    } catch (err) {
      console.error('Tag form error:', err);
      showAlert('danger', '❌ Failed to submit tag. Check network.');
    }
  });
}

async function handleQRCodeScan(qrCode) {
  console.log('QR Code detected:', qrCode);
  await scanner.stop();
  qrCodeMessage = qrCode.trim();
  updateStatus('⌛ Validating QR token...');

  try {
    const res = await fetch('scan_handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `token=${encodeURIComponent(qrCodeMessage)}`,
    });

    const responseText = await res.text();
    let htmlOutput = '';

    try {
      const result = JSON.parse(responseText);
      console.log('Server response:', result);

      const messages = {
        success: `✅ Time In recorded successfully at ${result.time_in}.`,
        already_timed_in:
          '⚠️ Already timed in. Scan again to request Time Out.',
        timeout_requested: '⏳ Time Out requested. Waiting for approval.',
        timeout_approved: '✅ Time Out approved. Record completed.',
        invalid: '❌ Invalid or inactive QR token.',
        used: '⚠️ QR code already used.',
      };

      if (result.status === 'require_tag') {
        htmlOutput = `
          <div class="alert alert-info text-center">
            Please enter the tag number for <strong>${result.item}</strong>.
          </div>
          <form id="tag-form" class="mb-3">
            <label for="tag" class="form-label">Tag Number:</label>
            <input type="text" id="tag" name="tag" class="form-control mb-2" required autocomplete="off" autofocus>
            <button type="submit" class="btn btn-primary w-100">Submit Tag</button>
          </form>
        `;
      } else {
        htmlOutput = `<div class="alert alert-${
          result.status === 'success' || result.status === 'timeout_approved'
            ? 'success'
            : result.status === 'timeout_requested' ||
              result.status === 'already_timed_in'
            ? 'warning'
            : 'danger'
        } text-center">${
          messages[result.status] || '❌ Unknown error occurred.'
        }</div>`;
      }
    } catch {
      htmlOutput = `<div class="alert alert-danger text-center">${
        responseText || '❌ Invalid response from server.'
      }</div>`;
    }

    DOM.result.innerHTML = htmlOutput;

    const tagForm = document.getElementById('tag-form');
    if (tagForm) {
      setupTagFormSubmission(tagForm);
    } else {
      setTimeout(() => {
        updateStatus('📷 Looking for QR code...');
        startScanner();
      }, 10000);
    }
  } catch (err) {
    console.error('Fetch error:', err);
    showAlert('danger', '❌ Network or server error. Try again.');
    updateStatus('📷 Looking for QR code...');
    startScanner();
  }
}

function startScanner() {
  scanner
    .start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: 250 },
      handleQRCodeScan,
      (err) => {
        if (!err.includes('No MultiFormat Readers')) {
          console.warn('Scanner warning:', err);
        }
      }
    )
    .catch((err) => {
      console.error('Start scanner error:', err);
      showAlert(
        'danger',
        '❌ Camera access error. Allow permissions and refresh.'
      );
      updateStatus('Camera access required');
    });
}

// Initial run
updateStatus('📷 Looking for QR code...');
startScanner();
