<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_username'])) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$admin_username = $_SESSION['admin_username'];
$id = intval($_POST['id'] ?? 0);

if (!$id) {
  echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
  exit;
}

// Get tag and item for future use (optional)
$stmt = $conn->prepare("SELECT tag_number, item FROM attendance WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
  echo json_encode(['success' => false, 'message' => 'Attendance record not found']);
  exit;
}

$row = $result->fetch_assoc();
$tag_number = $row['tag_number'];
$item = $row['item'];
$stmt->close();

// Update attendance to reject the request, and record who rejected it
$stmt = $conn->prepare("
  UPDATE attendance 
  SET time_out_requested = 0, 
      time_out_approved = 0,
      rejected_by = ?
  WHERE id = ?
");
$stmt->bind_param("si", $admin_username, $id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Time Out request rejected']);
$conn->close();
