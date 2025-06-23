<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_username'])) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => '❌ Unauthorized access.']);
  exit;
}

$admin_username = $_SESSION['admin_username'];

$id = intval($_POST['id'] ?? 0);
$date = trim($_POST['date'] ?? '');
$roll_number = trim($_POST['roll_number'] ?? '');
$location = trim($_POST['location'] ?? '');
$item = trim($_POST['item'] ?? '');
$time_in = $_POST['time_in'] !== '' ? $_POST['time_in'] : null;
$time_out = $_POST['time_out'] !== '' ? $_POST['time_out'] : null;

if (!$id || !$date || !$roll_number || !$location || !$item) {
  echo json_encode(['success' => false, 'message' => '❌ Missing required fields.']);
  exit;
}

try {
  $stmt = $conn->prepare("
    UPDATE attendance 
    SET date = ?, 
        roll_number = ?, 
        location = ?, 
        item = ?, 
        time_in = ?, 
        time_out = ?, 
        edited_by = ? 
    WHERE id = ?
  ");

  $stmt->bind_param("sssssssi", $date, $roll_number, $location, $item, $time_in, $time_out, $admin_username, $id);
  $stmt->execute();

  if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => '✅ Attendance record updated successfully.']);
  } else {
    echo json_encode(['success' => true, 'message' => '⚠️ No changes made or same data submitted.']);
  }

  $stmt->close();
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => '❌ Update failed: ' . $e->getMessage()]);
}

$conn->close();
