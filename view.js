const html5QrCode = new Html5Qrcode('reader');
const resultDiv = document.getElementById('result');
let isProcessing = false;

function postToken(token) {
  fetch('view_attendance.php', {
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

$('#records').DataTable({
  ajax: {
    url: 'fetch_attendance.php',
    data: function (d) {
      d.start_date = $('#start_date').val();
      d.end_date = $('#end_date').val();
      d.search = $('#search').val();
    },
    dataSrc: 'data',
  },
  destroy: true, // Allow reinitialization
  columns: [
    { data: 'index' },
    { data: 'date' },
    { data: 'roll_number' },
    { data: 'location' },
    { data: 'item' },
    { data: 'time_in' },
    { data: 'time_out' },
    {
      data: null,
      render: function (data, type, row) {
        return `<button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}">Edit</button>`;
      },
    },
  ],
});
