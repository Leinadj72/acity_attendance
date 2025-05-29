<?php
include 'db.php';

$items = [];
$result = $conn->query("SELECT DISTINCT item_name FROM items_tags ORDER BY item_name ASC");
while ($row = $result->fetch_assoc()) {
  $items[] = $row['item_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Generate QR Code</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
  <style>
    #qrcode {
      margin-top: 20px;
    }
    #downloadBtn {
      margin-top: 10px;
      display: none;
    }
    #loadingSpinner {
      display: none;
      margin-top: 10px;
    }
  </style>
</head>
<body class="container py-5">
  <h2 class="mb-4">Generate Attendance QR Code</h2>

  <form id="qrForm" novalidate>
    <!-- <div class="mb-3">
      <label for="dateInput" class="form-label">Date</label>
      <input id="dateInput" type="date" name="date" class="form-control" required />
    </div> -->

    <div class="mb-3">
      <label for="rollNumberInput" class="form-label">Roll Number / Staff ID</label>
      <input
        id="rollNumberInput"
        type="text"
        name="roll_number"
        class="form-control"
        required
        pattern="\d{11}"
        maxlength="11"
        placeholder="e.g. 12345678901"
      />
      <small class="text-muted">Must be exactly 11 digits</small>
    </div>

    <div class="mb-3">
      <label for="locationInput" class="form-label">Lecture Hall / Location</label>
      <input id="locationInput" type="text" name="location" class="form-control" required />
    </div>

    <div class="mb-3">
      <label for="itemSelect" class="form-label">Item</label>
      <select name="item" id="itemSelect" class="form-control" required>
        <option value="">-- Select Item --</option>
        <?php foreach ($items as $item) { ?>
          <option value="<?= htmlspecialchars($item) ?>"><?= htmlspecialchars($item) ?></option>
        <?php } ?>
      </select>
    </div>


    <button type="submit" class="btn btn-primary">Generate QR Code</button>
  </form>

  <div id="loadingSpinner" class="spinner-border text-primary" role="status" aria-hidden="true"></div>

  <div id="qrcode" class="mt-4 text-center"></div>

  <button id="downloadBtn" class="btn btn-success" aria-label="Download QR code">Download QR Code</button>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="generate.js"></script>
</body>
</html>
