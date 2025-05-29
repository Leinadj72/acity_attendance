const form = document.getElementById('qrForm');
const qrcodeDiv = document.getElementById('qrcode');
const downloadBtn = document.getElementById('downloadBtn');
const loadingSpinner = document.getElementById('loadingSpinner');
const submitBtn = form.querySelector('button[type="submit"]');

form.addEventListener('submit', async function (e) {
  e.preventDefault();

  // Clear previous QR code & hide download button
  qrcodeDiv.innerHTML = '';
  downloadBtn.style.display = 'none';

  const formData = new FormData(form);
  const date = new Date().toISOString().slice(0, 10);
  const rollNumber = formData.get('roll_number').trim();
  const location = formData.get('location').trim();
  const item = formData.get('item').trim();

  if (!/^\d{11}$/.test(rollNumber)) {
    alert('Roll Number must be exactly 11 digits.');
    document.getElementById('rollNumberInput').focus();
    return;
  }

  if (!location || !item) {
    alert('Please fill in all fields.');
    return;
  }

  // Validate roll number (exactly 11 digits)
  /* if (!/^\d{11}$/.test(rollNumber)) {
    alert('Roll Number must be exactly 11 digits.');
    document.getElementById('rollNumberInput').focus();
    return;
  } */

  // Disable submit button & show loading
  submitBtn.disabled = true;
  loadingSpinner.style.display = 'inline-block';

  const token = [...crypto.getRandomValues(new Uint8Array(8))]
    .map((b) => b.toString(36))
    .join('')
    .slice(0, 16);

  try {
    const response = await fetch('save_token.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token, date, rollNumber, location, item }),
    });

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.error || 'Failed to save token');
    }

    // Generate QR code
    new QRCode(qrcodeDiv, {
      text: token,
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
        link.download = 'attendance_qr.png';
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
