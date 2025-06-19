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
  <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" rel="stylesheet" />
  <style>
    .badge-Active {
      background-color: #0d6efd;
    }

    .badge-Pending {
      background-color: #ffc107;
      color: #000;
    }

    .badge-Approved {
      background-color: #28a745;
    }

    .badge-Rejected {
      background-color: #dc3545;
    }

    .badge-Completed {
      background-color: #6c757d;
    }
  </style>
</head>

<body class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>📋 Attendance Management</h2>
    <a href="logout.php" class="btn btn-danger">Logout</a>
  </div>

  <!-- Filters -->
  <div class="row g-3 mb-4 align-items-end">
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
    <div class="col-md-1">
      <button class="btn btn-primary w-100" id="filterBtn">Filter</button>
    </div>
  </div>

  <!-- Table -->
  <table id="attendanceTable" class="display nowrap table table-bordered" style="width:100%">
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
      </tr>
    </thead>
    <tbody></tbody>
  </table>

  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

  <script src="./view.js"></script>
</body>

</html>