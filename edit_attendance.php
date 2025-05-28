<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_logged_in'])) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$id = intval($_POST['id'] ?? 0);
$date = $_POST['date'] ?? '';
$roll_number = $_POST['roll_number'] ?? '';
$location = $_POST['location'] ?? '';
$item = $_POST['item'] ?? '';
$time_in = $_POST['time_in'] ?? null;
$time_out = $_POST['time_out'] ?? null;

if (!$id || !$date || !$roll_number || !$location || !$item) {
  echo json_encode(['success' => false, 'message' => 'Missing required fields']);
  exit;
}

$stmt = $conn->prepare("UPDATE attendance SET date=?, roll_number=?, location=?, item=?, time_in=?, time_out=? WHERE id=?");
$stmt->bind_param("ssssssi", $date, $roll_number, $location, $item, $time_in, $time_out, $id);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$stmt->close();
$conn->close();
