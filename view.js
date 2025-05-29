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
      isProcessing = false;
      html5QrCode.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: 250 },
        onScanSuccess
      );
    });
}

function onScanSuccess(decodedText) {
  if (isProcessing || !decodedText.trim()) return;
  isProcessing = true;
  html5QrCode
    .stop()
    .then(() => postToken(decodedText))
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

$(document).ready(function () {
  const table = $('#records').DataTable({
    ajax: {
      url: 'fetch_attendance.php',
      data: function (d) {
        d.start_date = $('#start_date').val();
        d.end_date = $('#end_date').val();
        d.search = $('#search_roll_location').val();
        d.pending_only = $('#pending_only').is(':checked') ? '1' : '0';
      },
      dataSrc: 'data',
    },
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
          if (row.time_out_requested == 1 && row.time_out_approved == 0) {
            return `
              <button class="btn btn-success btn-sm approve-btn" data-id="${row.id}">Approve</button>
              <button class="btn btn-danger btn-sm reject-btn" data-id="${row.id}">Reject</button>
            `;
          } else {
            return `<span class="text-muted">No Action</span>`;
          }
        },
      },
    ],
  });

  $('#filterBtn').click(function () {
    table.ajax.reload();
  });

  $('#resetBtn').click(function () {
    $('#start_date, #end_date, #search_roll_location').val('');
    $('#pending_only').prop('checked', false);
    table.ajax.reload();
  });

  // Approve button click
  $('#records').on('click', '.approve-btn', function () {
    const button = $(this);
    const id = button.data('id');
    button.prop('disabled', true).text('Approving...');

    $.post(
      'approve_attendance.php',
      { id },
      function (response) {
        alert(response.message || 'Approved!');
        table.ajax.reload();
      },
      'json'
    )
      .fail(() => alert('Failed to approve. Try again.'))
      .always(() => {
        button.prop('disabled', false).text('Approve');
      });
  });

  // Reject button click
  $('#records').on('click', '.reject-btn', function () {
    const button = $(this);
    const id = button.data('id');
    button.prop('disabled', true).text('Rejecting...');

    $.post(
      'reject_attendance.php',
      { id },
      function (response) {
        alert(response.message || 'Rejected!');
        table.ajax.reload();
      },
      'json'
    )
      .fail(() => alert('Failed to reject. Try again.'))
      .always(() => {
        button.prop('disabled', false).text('Reject');
      });
  });
});
