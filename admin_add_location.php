<?php
include 'db.php';
$msg = '';

if (isset($_GET['msg'])) {
  if ($_GET['msg'] === 'updated') {
    $msg = "✅ Location updated.";
  } elseif ($_GET['msg'] === 'added') {
    $msg = "✅ Location added.";
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $location = trim($_POST['location'] ?? '');
  $edit_id = $_POST['edit_id'] ?? '';

  if ($location !== '') {
    if ($edit_id) {
      $stmt = $conn->prepare("UPDATE locations SET location_name = ? WHERE id = ?");
      $stmt->bind_param("si", $location, $edit_id);
      $stmt->execute();
      header("Location: admin_add_location.php?msg=updated");
      exit;
    } else {
      $stmt = $conn->prepare("INSERT INTO locations (location_name) VALUES (?)");
      $stmt->bind_param("s", $location);
      $stmt->execute();
      header("Location: admin_add_location.php?msg=added");
      exit;
    }
  } else {
    $msg = "❌ Location name required.";
  }
}

if (isset($_GET['delete'])) {
  $del_id = intval($_GET['delete']);
  $conn->query("DELETE FROM locations WHERE id = $del_id");
  header("Location: admin_add_location.php");
  exit;
}

$edit_location = null;
if (isset($_GET['edit'])) {
  $id = intval($_GET['edit']);
  $result = $conn->query("SELECT * FROM locations WHERE id = $id");
  $edit_location = $result->fetch_assoc();
}

$locations = $conn->query("SELECT * FROM locations ORDER BY location_name ASC");
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
    <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form method="POST" class="mb-4">
    <input type="hidden" name="edit_id" value="<?= $edit_location['id'] ?? '' ?>">
    <div class="mb-3">
      <label class="form-label"><?= $edit_location ? 'Edit Location' : 'Add New Location' ?></label>
      <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($edit_location['location_name'] ?? '') ?>" required>
    </div>
    <button type="submit" class="btn btn-<?= $edit_location ? 'warning' : 'primary' ?>">
      <?= $edit_location ? 'Update' : 'Add' ?> Location
    </button>
    <?php if ($edit_location): ?>
      <a href="admin_add_location.php" class="btn btn-secondary">Cancel</a>
    <?php endif; ?>
  </form>

  <h2>Location List</h2>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Name</th>
        <th style="width: 150px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($loc = $locations->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($loc['location_name']) ?></td>
          <td>
            <a href="admin_add_location.php?edit=<?= $loc['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <a href="admin_add_location.php?delete=<?= $loc['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this location?');">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>

</html>