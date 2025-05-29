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
  downloadBtn.onclick = null;

  // Get form data
  const formData = new FormData(form);
  const date = formData.get('date');
  const rollNumber = formData.get('roll_number');
  const location = formData.get('location');
  const item = formData.get('item');

  // Validate roll number (exactly 11 digits)
  /* if (!/^\d{11}$/.test(rollNumber)) {
    alert('Roll Number must be exactly 11 digits.');
    document.getElementById('rollNumberInput').focus();
    return;
  } */

  // Disable submit button & show loading
  submitBtn.disabled = true;
  loadingSpinner.style.display = 'inline-block';

  // Generate a unique token for QR code
  const token = Math.random().toString(36).substr(2, 16);

  // Encode ONLY the token string in the QR code
  const tokenData = token;

  try {
    // Send full data (with token) to server to save in database
    const response = await fetch('save_token.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ token, date, rollNumber, location, item }),
    });

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.error || 'Failed to save token');
    }

    // Generate QR code with only token data inside qrcodeDiv
    new QRCode(qrcodeDiv, {
      text: tokenData,
      width: 300,
      height: 300,
      colorDark: '#000000',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.H,
    });

    // Show download button
    downloadBtn.style.display = 'inline-block';

    // Download QR code as PNG when clicked
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
