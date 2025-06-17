<?php
include 'db.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $location = trim($_POST['location']);
  $edit_id = $_POST['edit_id'] ?? '';

  if ($location !== '') {
    if ($edit_id) {
      $stmt = $conn->prepare("UPDATE locations SET name = ? WHERE id = ?");
      $stmt->bind_param("si", $location, $edit_id);
      $stmt->execute();
      $msg = "✅ Location updated.";
    } else {
      $stmt = $conn->prepare("INSERT INTO locations (name) VALUES (?)");
      $stmt->bind_param("s", $location);
      $stmt->execute();
      $msg = "✅ Location added.";
    }
  } else {
    $msg = "❌ Location name required.";
  }
}

if (isset($_GET['delete'])) {
  $del_id = $_GET['delete'];
  $conn->query("DELETE FROM locations WHERE id = " . intval($del_id));
  header("Location: add_location.php");
  exit;
}

$edit_location = null;
if (isset($_GET['edit'])) {
  $id = $_GET['edit'];
  $result = $conn->query("SELECT * FROM locations WHERE id = " . intval($id));
  $edit_location = $result->fetch_assoc();
}

$locations = $conn->query("SELECT * FROM locations ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Locations</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
</head>
<body class="container py-5">
  <h1 class="mb-4">Manage Locations</h1>

  <?php if (!empty($msg)): ?>
    <div class="alert alert-info"><?= $msg ?></div>
  <?php endif; ?>

  <form method="POST" class="mb-4">
    <input type="hidden" name="edit_id" value="<?= $edit_location['id'] ?? '' ?>">
    <div class="mb-3">
      <label class="form-label"><?= $edit_location ? 'Edit Location' : 'Add New Location' ?></label>
      <input type="text" name="location" class="form-control" value="<?= $edit_location['name'] ?? '' ?>" required>
    </div>
    <button type="submit" class="btn btn-<?= $edit_location ? 'warning' : 'primary' ?>">
      <?= $edit_location ? 'Update' : 'Add' ?> Location
    </button>
    <?php if ($edit_location): ?>
      <a href="add_location.php" class="btn btn-secondary">Cancel</a>
    <?php endif; ?>
  </form>

  <h2>Location List</h2>
  <table class="table table-bordered">
    <thead>
      <tr><th>Name</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php while ($loc = $locations->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($loc['name']) ?></td>
          <td>
            <a href="?edit=<?= $loc['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <a href="?delete=<?= $loc['id'] ?>" onclick="return confirm('Delete this location?')" class="btn btn-sm btn-danger">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>
