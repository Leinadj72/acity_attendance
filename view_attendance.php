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
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" />
  <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" rel="stylesheet" />
</head>
<body class="container py-5">

  <h1>Welcome, <strong>Admin</strong></h1> <br>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">📋 Attendance Records</h2>
    <a href="logout.php" class="btn btn-danger">Logout</a>
  </div>

  <div class="row mb-3 g-3 align-items-end">
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
    <div class="col-md-2">
      <div class="form-check">
        <input type="checkbox" class="form-check-input" id="pending_only" />
        <label class="form-check-label" for="pending_only">Pending Time Out Only</label>
      </div>
    </div>
    <div class="col-md-1 d-grid">
      <button id="filterBtn" class="btn btn-primary">Filter</button>
    </div>
    <div class="col-md-1 d-grid">
      <button id="resetBtn" class="btn btn-secondary">Reset</button>
    </div>
  </div>

  <div class="table-responsive">
    <table id="records" class="table table-bordered table-striped" style="width:100%">
      <thead>
        <tr>
          <th>#</th>
          <th>Date</th>
          <th>Roll Number / Staff ID</th>
          <th>Location</th>
          <th>Item</th>
          <th>Time In</th>
          <th>Time Out</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  <div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;"></div>

  <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form id="editForm" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Edit Attendance Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit_id" name="id">
          <div class="mb-3">
            <label for="edit_date" class="form-label">Date</label>
            <input type="date" class="form-control" id="edit_date" name="date" required>
          </div>
          <div class="mb-3">
            <label for="edit_roll_number" class="form-label">Roll Number</label>
            <input type="text" class="form-control" id="edit_roll_number" name="roll_number" required>
          </div>
          <div class="mb-3">
            <label for="edit_location" class="form-label">Location</label>
            <input type="text" class="form-control" id="edit_location" name="location" required>
          </div>
          <div class="mb-3">
            <label for="edit_item" class="form-label">Item</label>
            <input type="text" class="form-control" id="edit_item" name="item" required>
          </div>
          <div class="mb-3">
            <label for="edit_time_in" class="form-label">Time In</label>
            <input type="time" class="form-control" id="edit_time_in" name="time_in" required>
          </div>
          <div class="mb-3">
            <label for="edit_time_out" class="form-label">Time Out</label>
            <input type="time" class="form-control" id="edit_time_out" name="time_out">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>


  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

  <script src="view.js"></script>
</body>
</html>
