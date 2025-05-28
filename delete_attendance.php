<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_logged_in'])) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$id = intval($_POST['id'] ?? 0);
if (!$id) {
  echo json_encode(['success' => false, 'message' => 'Invalid ID']);
  exit;
}

$stmt = $conn->prepare("DELETE FROM attendance WHERE id=?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
