<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$id = intval($_POST['id'] ?? 0);

if (!$id) {
  echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
  exit;
}

// Reset time_out_requested and time_out_approved
$stmt = $conn->prepare("UPDATE attendance SET time_out_requested = 0, time_out_approved = 0 WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  echo json_encode(['success' => true, 'message' => 'Time Out request rejected']);
} else {
  echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
