<?php
session_start();
include 'db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in'])) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
  exit;
}

$stmt = $conn->prepare("
  UPDATE attendance 
  SET time_out_approved = 1, 
      time_out = IFNULL(time_out, NOW()) 
  WHERE id = ?
");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  echo json_encode(['success' => true, 'message' => 'Time Out approved successfully']);
} else {
  echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
