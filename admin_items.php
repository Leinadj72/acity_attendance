<?php
include 'db.php';
$result = $conn->query("SELECT * FROM items_tags ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Admin: Item List</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
</head>

<body class="container py-5">
  <h1 class="mb-4">All Item Tags</h1>

  <table class="table table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>Item Name</th>
        <th>Tag Number</th>
        <th>Available</th>
        <th>Created At</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['item_name']) ?></td>
          <td><?= htmlspecialchars($row['tag_number']) ?></td>
          <td><?= $row['is_available'] ? 'Yes' : 'No' ?></td>
          <td><?= $row['created_at'] ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>

</html>