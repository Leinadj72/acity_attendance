<?php
include 'db.php';

$items = [];
$result = $conn->query("SELECT DISTINCT item_name FROM items_tags ORDER BY item_name ASC");
if (isset($_GET['delete'])) {
  $deleteId = intval($_GET['delete']);
  $conn->query("DELETE FROM items_tags WHERE id = $deleteId");
  header("Location: add_item_tag.php");
  exit;
}

while ($row = $result->fetch_assoc()) {
  $items[] = $row['item_name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $item_name = $_POST['item_name'] ?? '';
  $tag_number = $_POST['tag_number'] ?? '';

  if ($item_name && $tag_number) {
    $checkStmt = $conn->prepare("SELECT id FROM items_tags WHERE tag_number = ?");
    $checkStmt->bind_param('s', $tag_number);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
      $msg = "Error: Tag number already exists.";
    } else {
      $stmt = $conn->prepare("INSERT INTO items_tags (item_name, tag_number) VALUES (?, ?)");
      $stmt->bind_param('ss', $item_name, $tag_number);
      if ($stmt->execute()) {
        $msg = "Item and tag added successfully.";
      } else {
        $msg = "Error: " . $conn->error;
      }
    }

    $checkStmt->close();
  } else {
    $msg = "Please fill all fields.";
  }
}

$result = $conn->query("SELECT * FROM items_tags ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Admin: Add Item & Tag</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
  <style>
    .qr-code-container {
      margin-top: 10px;
    }

    .qr-code {
      width: 100px;
      height: 100px;
    }

    #tagQrCode {
      margin-top: 20px;
    }

    #downloadTagBtn {
      margin-top: 10px;
      display: none;
    }
  </style>
</head>

<body class="container py-5">
  <h1 class="mb-4">Add Item & Tag</h1>

  <?php if (!empty($msg)): ?>
    <div class="alert <?= strpos($msg, 'Error') === 0 ? 'alert-danger' : 'alert-success' ?>">
      <?= $msg ?>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-6">
      <form method="post" action="">
        <div class="mb-3">
          <label for="item_name" class="form-label">Item Name</label>
          <input type="text" class="form-control" name="item_name" placeholder="Item Name" required>
        </div>
        <div class="mb-3">
          <label for="tag_number" class="form-label">Tag Number</label>
          <input type="text" class="form-control" name="tag_number" id="tagCodeInput" placeholder="Tag Number" required>
        </div>
        <button type="submit" class="btn btn-primary">Add Item & Tag</button>
      </form>
    </div>

    <div class="col-md-6">
      <h3>Generate Tag QR Code</h3>
      <div class="mb-3">
        <label for="qrTagNumber" class="form-label">Tag Number to Generate QR</label>
        <input type="text" class="form-control" id="qrTagNumber" placeholder="Enter Tag Number">
      </div>
      <button id="generateTagQrBtn" class="btn btn-secondary">Generate QR Code</button>

      <div id="tagQrCode" class="mt-4 text-center"></div>
      <button id="downloadTagBtn" class="btn btn-success">Download QR Code</button>
    </div>
  </div>

  <h2 class="mt-5">Existing Item Tags</h2>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Item Name</th>
        <th>Tag Number</th>
        <th>QR Code</th>
        <th>Available</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['item_name']) ?></td>
          <td><?= htmlspecialchars($row['tag_number']) ?></td>
          <td>
            <div class="qr-code-container" data-tag="<?= htmlspecialchars($row['tag_number']) ?>"></div>
            <button class="btn btn-sm btn-outline-primary view-qr-btn" data-tag="<?= htmlspecialchars($row['tag_number']) ?>">
              View QR
            </button>
          </td>
          <td><?= $row['is_available'] ? 'Yes' : 'No' ?></td>
          <td>
            <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this tag?')">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tag QR Number</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <div id="modalQrCode"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="downloadModalQrBtn">Download</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));

    document.getElementById('generateTagQrBtn').addEventListener('click', function() {
      const tagCode = document.getElementById('qrTagNumber').value.trim();
      const qrCodeDiv = document.getElementById('tagQrCode');

      if (!tagCode) {
        alert('Please enter a tag code');
        return;
      }

      qrCodeDiv.innerHTML = '';
      new QRCode(qrCodeDiv, {
        text: tagCode,
        width: 200,
        height: 200,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
      });

      document.getElementById('downloadTagBtn').style.display = 'inline-block';
    });

    document.getElementById('downloadTagBtn').addEventListener('click', function() {
      const canvas = document.getElementById('tagQrCode').querySelector('canvas');
      if (canvas) {
        const url = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.href = url;
        link.download = 'tag_qr.png';
        link.click();
      }
    });

    document.querySelectorAll('.view-qr-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const tagCode = this.getAttribute('data-tag');
        const modalQrDiv = document.getElementById('modalQrCode');

        modalQrDiv.innerHTML = '';
        new QRCode(modalQrDiv, {
          text: tagCode,
          width: 200,
          height: 200,
          colorDark: '#000000',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.H
        });

        document.getElementById('downloadModalQrBtn').onclick = function() {
          const canvas = modalQrDiv.querySelector('canvas');
          if (canvas) {
            const url = canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.href = url;
            link.download = `tag_${tagCode}.png`;
            link.click();
          }
        };

        qrModal.show();
      });
    });

    document.querySelectorAll('td:nth-child(2)').forEach(td => {
      td.style.cursor = 'pointer';
      td.addEventListener('click', function() {
        document.getElementById('qrTagNumber').value = this.textContent.trim();
      });
    });
  </script>
</body>

</html>