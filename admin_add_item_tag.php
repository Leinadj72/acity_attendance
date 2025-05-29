<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $item_name = $_POST['item_name'] ?? '';
  $tag_code = $_POST['tag_code'] ?? '';

  if ($item_name && $tag_code) {
    // Check if tag_code already exists
    $checkStmt = $conn->prepare("SELECT id FROM items_tags WHERE tag_code = ?");
    $checkStmt->bind_param('s', $tag_code);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
      $msg = "Error: Tag number already exists.";
    } else {
      // Proceed to insert
      $stmt = $conn->prepare("INSERT INTO items_tags (item_name, tag_code) VALUES (?, ?)");
      $stmt->bind_param('ss', $item_name, $tag_code);
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
</head>
<body>
  <h1>Add Item & Tag</h1>
  <?php if (!empty($msg)) echo "<p>$msg</p>"; ?>
  <form method="post" action="">
    <input type="text" name="item_name" placeholder="Item Name" required>
    <input type="text" name="tag_code" placeholder="Tag Code" required>
    <button type="submit">Add Item & Tag</button>
  </form>

  <h2>Existing Item Tags</h2>
  <table border="1" cellpadding="5" cellspacing="0">
    <thead><tr><th>Item Name</th><th>Tag Code</th><th>Available</th></tr></thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()) { ?>
      <tr>
        <td><?= htmlspecialchars($row['item_name']) ?></td>
        <td><?= htmlspecialchars($row['tag_code']) ?></td>
        <td><?= $row['is_available'] ? 'Yes' : 'No' ?></td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</body>
</html>
