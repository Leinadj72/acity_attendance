<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_logged_in'])) {
  header("Location: login.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin - Attendance Records</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" />
</head>
<body class="container py-5">

  <h1>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></h1>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">📋 Attendance Records</h2>
    <a href="logout.php" class="btn btn-danger">Logout</a>
  </div>

  <!-- Filters -->
  <div class="row mb-3">
    <div class="col-md-3">
      <label for="start_date" class="form-label">Start Date</label>
      <input type="date" id="start_date" class="form-control" />
    </div>
    <div class="col-md-3">
      <label for="end_date" class="form-label">End Date</label>
      <input type="date" id="end_date" class="form-control" />
    </div>
    <div class="col-md-3">
      <label for="search_roll_location" class="form-label">Roll Number or Location</label>
      <input type="text" id="search_roll_location" class="form-control" placeholder="Search roll or location" />
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <button id="filterBtn" class="btn btn-primary me-2">Filter</button>
      <button id="resetBtn" class="btn btn-secondary">Reset</button>
    </div>
  </div>

  <table id="records" class="table table-bordered table-striped" style="width:100%">
    <thead>
      <tr>
        <th>#</th>
        <th>Date</th>
        <th>Roll Number</th>
        <th>Location</th>
        <th>Item</th>
        <th>Time In</th>
        <th>Time Out</th>
        <th>Actions</th>
      </tr>
    </thead>
  </table>

  <!-- Edit Modal -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form id="editForm">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Attendance</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="edit_id" name="id" />
            <div class="mb-3">
              <label for="edit_date" class="form-label">Date</label>
              <input type="date" class="form-control" id="edit_date" name="date" required />
            </div>
            <div class="mb-3">
              <label for="edit_roll_number" class="form-label">Roll Number</label>
              <input type="text" class="form-control" id="edit_roll_number" name="roll_number" required />
            </div>
            <div class="mb-3">
              <label for="edit_location" class="form-label">Location</label>
              <input type="text" class="form-control" id="edit_location" name="location" required />
            </div>
            <div class="mb-3">
              <label for="edit_item" class="form-label">Item</label>
              <input type="text" class="form-control" id="edit_item" name="item" required />
            </div>
            <div class="mb-3">
              <label for="edit_time_in" class="form-label">Time In</label>
              <input type="time" class="form-control" id="edit_time_in" name="time_in" />
            </div>
            <div class="mb-3">
              <label for="edit_time_out" class="form-label">Time Out</label>
              <input type="time" class="form-control" id="edit_time_out" name="time_out" />
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

  <script>
    let table;

    $(document).ready(function () {
      table = $('#records').DataTable({
        ajax: {
          url: 'fetch_attendance.php',
          data: function (d) {
            d.start_date = $('#start_date').val();
            d.end_date = $('#end_date').val();
            d.search_query = $('#search_roll_location').val();
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
              return `<button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}">Edit</button>`;
            }
          }
        ],
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
      });

      $('#filterBtn').on('click', () => table.ajax.reload());
      $('#resetBtn').on('click', function () {
        $('#start_date, #end_date, #search_roll_location').val('');
        table.ajax.reload();
      });

      // Edit button event
      $('#records').on('click', '.edit-btn', function () {
        const data = table.row($(this).parents('tr')).data();
        $('#edit_id').val(data.id);
        $('#edit_date').val(data.date);
        $('#edit_roll_number').val(data.roll_number);
        $('#edit_location').val(data.location);
        $('#edit_item').val(data.item);
        $('#edit_time_in').val(data.time_in);
        $('#edit_time_out').val(data.time_out);
        $('#editModal').modal('show');
      });

      // Submit Edit Form
      $('#editForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
          url: 'update_attendance.php',
          type: 'POST',
          data: $(this).serialize(),
          success: function (response) {
            $('#editModal').modal('hide');
            table.ajax.reload();
          },
          error: function () {
            alert('Failed to update record.');
          }
        });
      });
    });
  </script>

</body>
</html>
