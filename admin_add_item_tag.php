<?php
include 'db.php';

$msg = '';
$edit_id = '';
$edit_data = null;

if (isset($_GET['delete'])) {
  $deleteId = intval($_GET['delete']);
  $conn->query("DELETE FROM items_tags WHERE id = $deleteId");
  header("Location: admin_add_item_tag.php");
  exit;
}

if (isset($_GET['edit'])) {
  $edit_id = intval($_GET['edit']);
  $result = $conn->query("SELECT * FROM items_tags WHERE id = $edit_id");
  $edit_data = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $item_name = trim($_POST['item_name'] ?? '');
  $tag_number = trim($_POST['tag_number'] ?? '');
  $edit_id = intval($_POST['edit_id'] ?? 0);

  if ($item_name && $tag_number) {
    if ($edit_id) {
      $stmt = $conn->prepare("UPDATE items_tags SET item_name = ?, tag_number = ? WHERE id = ?");
      $stmt->bind_param('ssi', $item_name, $tag_number, $edit_id);
      if ($stmt->execute()) {
        $msg = "✅ Item tag updated successfully.";
        header("Location: admin_add_item_tag.php");
        exit;
      } else {
        $msg = "Error updating tag.";
      }
    } else {
      $checkStmt = $conn->prepare("SELECT id FROM items_tags WHERE tag_number = ?");
      $checkStmt->bind_param('s', $tag_number);
      $checkStmt->execute();
      $checkStmt->store_result();

      if ($checkStmt->num_rows > 0) {
        $msg = "❌ Error: Tag number already exists.";
      } else {
        $stmt = $conn->prepare("INSERT INTO items_tags (item_name, tag_number) VALUES (?, ?)");
        $stmt->bind_param('ss', $item_name, $tag_number);
        if ($stmt->execute()) {
          $msg = "✅ Item and tag added successfully.";
        } else {
          $msg = "❌ Error: " . $conn->error;
        }
      }

      $checkStmt->close();
    }
  } else {
    $msg = "❌ Please fill all fields.";
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
</head>

<body class="container py-5">
  <h1 class="mb-4">Add Item & Tag</h1>

  <?php if (!empty($msg)): ?>
    <div class="alert <?= str_starts_with($msg, '✅') ? 'alert-success' : 'alert-danger' ?>">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <form method="post" action="" class="mb-4">
    <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?? '' ?>">
    <div class="mb-3">
      <label class="form-label">Item Name</label>
      <input type="text" class="form-control" name="item_name" value="<?= htmlspecialchars($edit_data['item_name'] ?? '') ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Tag Number</label>
      <input type="text" class="form-control" name="tag_number" value="<?= htmlspecialchars($edit_data['tag_number'] ?? '') ?>" required>
    </div>
    <button type="submit" class="btn btn-<?= $edit_data ? 'warning' : 'primary' ?>">
      <?= $edit_data ? 'Update' : 'Add' ?> Item Tag
    </button>
    <?php if ($edit_data): ?>
      <a href="admin_add_item_tag.php" class="btn btn-secondary">Cancel</a>
    <?php endif; ?>
  </form>

  <h2>Existing Item Tags</h2>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Item Name</th>
        <th>Tag Number</th>
        <th>Available</th>
        <th>QR Code</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['item_name']) ?></td>
          <td><?= htmlspecialchars($row['tag_number']) ?></td>
          <td><?= $row['is_available'] ? 'Yes' : 'No' ?></td>
          <td>
            <button class="btn btn-sm btn-outline-primary view-qr-btn" data-tag="<?= htmlspecialchars($row['tag_number']) ?>">View QR</button>
          </td>
          <td>
            <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this item tag?')" class="btn btn-sm btn-danger">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content text-center">
        <div class="modal-header">
          <h5 class="modal-title">Tag QR Code</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="modalQrCode"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-success" id="downloadModalQrBtn">Download QR</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
    const modalQrDiv = document.getElementById('modalQrCode');

    document.querySelectorAll('.view-qr-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const tag = this.getAttribute('data-tag');
        modalQrDiv.innerHTML = '';
        new QRCode(modalQrDiv, {
          text: tag,
          width: 200,
          height: 200,
          colorDark: '#000',
          colorLight: '#fff',
          correctLevel: QRCode.CorrectLevel.H
        });
        document.getElementById('downloadModalQrBtn').onclick = function() {
          const canvas = modalQrDiv.querySelector('canvas');
          const url = canvas.toDataURL();
          const link = document.createElement('a');
          link.href = url;
          link.download = `tag_${tag}.png`;
          link.click();
        };
        qrModal.show();
      });
    });
  </script>
</body>

</html>