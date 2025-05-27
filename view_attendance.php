<?php
session_start();

include 'db.php';


if (!isset($_SESSION['admin_logged_in'])) {
  header("Location: login.php");
  exit;
}

// echo "Welcome, " . $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Attendance Records</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
</head>
<body class="container py-5">

  <h1> Welcome <strong>Admin</strong></h1>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">📋 Attendance Records</h2>
    <a href="logout.php" class="btn btn-danger">Logout</a>
  </div>

  <table id="records" class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>#</th>
        <th>Date</th>
        <th>Roll Number</th>
        <th>Location</th>
        <th>Item</th>
        <th>Time In</th>
        <th>Time Out</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $result = $conn->query("SELECT * FROM attendance ORDER BY date DESC, id DESC");
      $i = 1;
      while ($row = $result->fetch_assoc()) {
        echo "<tr>
          <td>{$i}</td>
          <td>{$row['date']}</td>
          <td>{$row['roll_number']}</td>
          <td>{$row['location']}</td>
          <td>{$row['item']}</td>
          <td>{$row['time_in']}</td>
          <td>{$row['time_out']}</td>
        </tr>";
        $i++;
      }
      ?>
    </tbody>
  </table>


  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

  <script>
    $(document).ready(function () {
      $('#records').DataTable({
        dom: 'Bfrtip',
        buttons: [
          'excelHtml5', 'csvHtml5', 'pdf', 'print'
        ]
      });
    });
  </script>

</body>
</html>
