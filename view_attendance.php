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
  <title>Admin - View Attendance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" />
  <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet" />

  <style>
    html,
    body {
      width: 100%;
      overflow-x: hidden;
    }

    .container-fluid {
      padding: 2rem;
    }

    .badge {
      font-size: 0.85rem;
      padding: 0.4em 0.6em;
    }

    .badge-Active {
      background-color: #0d6efd;
      color: white;
    }

    .badge-Pending {
      background-color: #ffc107;
      color: #212529;
    }

    .badge-Approved {
      background-color: #28a745;
      color: white;
    }

    .badge-Rejected {
      background-color: #dc3545;
      color: white;
    }

    .badge-Completed {
      background-color: #6c757d;
      color: white;
    }

    table.dataTable thead th {
      background-color: #f8f9fa;
      color: #333;
      font-weight: 600;
      border-bottom: 2px solid #dee2e6;
      padding: 0.75rem;
    }

    table.dataTable tbody td {
      padding: 0.6rem;
    }

    table.dataTable tbody tr:hover {
      background-color: #eef6ff;
    }

    table.dataTable td,
    table.dataTable th {
      border-color: #dee2e6;
    }

    .dt-buttons .btn {
      margin-right: 0.5rem;
    }

    @media print {

      .btn,
      .form-control,
      .form-check,
      .filter-row {
        display: none !important;
      }

      body,
      table.dataTable {
        font-size: 0.8rem;
      }

      .container-fluid {
        padding: 0.5rem;
      }
    }
  </style>
</head>

<body>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>📋 Device Management</h2>
      <a href="change_password.php" class="btn btn-warning me-2">Change Password</a>
      <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <div class="row g-3 mb-4 align-items-end filter-row">
      <div class="col-md-2">
        <label for="start_date" class="form-label">Start Date</label>
        <input type="date" id="start_date" class="form-control" />
      </div>
      <div class="col-md-2">
        <label for="end_date" class="form-label">End Date</label>
        <input type="date" id="end_date" class="form-control" />
      </div>
      <div class="col-md-3">
        <label for="search_roll_location" class="form-label">Roll Number or Location</label>
        <input type="text" id="search_roll_location" class="form-control" placeholder="Search..." />
      </div>
      <div class="col-md-2">
        <label for="search_tag_number" class="form-label">Tag Number</label>
        <input type="text" id="search_tag_number" class="form-control" />
      </div>
      <div class="col-md-2">
        <div class="form-check mt-4">
          <input type="checkbox" class="form-check-input" id="pending_only" />
          <label class="form-check-label" for="pending_only">Pending Time Out</label>
        </div>
      </div>
      <div class="col-md-1 d-grid">
        <button class="btn btn-primary" id="filterBtn">Filter</button>
        <button class="btn btn-secondary mt-2" id="resetBtn">Reset</button>
      </div>
    </div>

    <div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

    <div class="table-responsive">
      <table id="attendanceTable" class="table table-striped table-hover table-bordered align-middle w-100">
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Roll Number</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Item</th>
            <th>Tag</th>
            <th>Location</th>
            <th>Time In</th>
            <th>Time Out</th>
            <th>Requested At</th>
            <th>Status</th>
            <th>Approved By</th>
            <th>Rejected By</th>
            <th>Edited By</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <form class="modal-content" id="editForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Edit Attendance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body row g-3">
          <input type="hidden" id="edit_id" name="id" />
          <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" id="edit_date" name="date" class="form-control" required />
          </div>
          <div class="col-md-4">
            <label class="form-label">Roll Number</label>
            <input type="text" id="edit_roll_number" name="roll_number" class="form-control" required />
          </div>
          <div class="col-md-4">
            <label class="form-label">Location</label>
            <input type="text" id="edit_location" name="location" class="form-control" required />
          </div>
          <div class="col-md-4">
            <label class="form-label">Item</label>
            <input type="text" id="edit_item" name="item" class="form-control" required />
          </div>
          <div class="col-md-4">
            <label class="form-label">Time In</label>
            <input type="time" id="edit_time_in" name="time_in" class="form-control" />
          </div>
          <div class="col-md-4">
            <label class="form-label">Time Out</label>
            <input type="time" id="edit_time_out" name="time_out" class="form-control" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save Changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.68/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.68/vfs_fonts.js"></script>
  <script src="./view.js"></script>
</body>

</html>