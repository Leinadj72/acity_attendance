const form = document.getElementById('qrForm');
const qrcodeDiv = document.getElementById('qrcode');
const downloadBtn = document.getElementById('downloadBtn');
const loadingSpinner = document.getElementById('loadingSpinner');

form.addEventListener('submit', async function (e) {
  e.preventDefault();

  qrcodeDiv.innerHTML = '';
  downloadBtn.style.display = 'none';

  const formData = new FormData(form);
  const date = formData.get('date');
  const rollNumber = formData.get('roll_number');
  const location = formData.get('location');
  const item = formData.get('item');

  if (!/^\d{11}$/.test(rollNumber)) {
    alert('Roll Number must be exactly 11 digits.');
    return;
  }

  loadingSpinner.style.display = 'inline-block';

  // Generate unique token client-side (can also do it server-side if preferred)
  const token = Math.random().toString(36).substr(2, 16);

  // Send form data + token to server to save in DB
  try {
    const response = await fetch('save_token.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token, date, rollNumber, location, item }),
    });

    const result = await response.json();

    if (result.success) {
      // Generate QR code with just the token string (keep QR code small and clean)
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
    } else {
      alert('Failed to save token: ' + (result.error || 'Unknown error'));
    }
  } catch (err) {
    alert('Error saving token: ' + err.message);
  } finally {
    loadingSpinner.style.display = 'none';
  }
});
