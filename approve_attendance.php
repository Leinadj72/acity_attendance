<?php
session_start();
file_put_contents("error_log.txt", print_r($_SESSION, true));
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_username'])) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => '❌ Unauthorized. Please log in as admin.']);
  exit;
}

$admin_username = $_SESSION['admin_username'];
$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
  echo json_encode(['success' => false, 'message' => '❌ Invalid record ID.']);
  exit;
}

$stmt = $conn->prepare("SELECT tag_number, item FROM attendance WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
  echo json_encode(['success' => false, 'message' => '❌ Attendance record not found.']);
  exit;
}

$row = $result->fetch_assoc();
$tag_number = $row['tag_number'];
$item = $row['item'];
$stmt->close();

$conn->begin_transaction();

try {
  $stmt = $conn->prepare("
    UPDATE attendance 
    SET time_out_approved = 1, 
        time_out = IFNULL(time_out, NOW()),
        approved_by = ?
    WHERE id = ?
  ");
  $stmt->bind_param("si", $admin_username, $id);
  $stmt->execute();
  $stmt->close();

  $stmt = $conn->prepare("
    UPDATE items_tags 
    SET is_available = 1 
    WHERE tag_number = ? AND item_name = ?
  ");
  $stmt->bind_param("ss", $tag_number, $item);
  $stmt->execute();
  $stmt->close();

  $conn->commit();

  echo json_encode([
    'success' => true,
    'message' => '✅ Time Out approved and item marked as available.',
    'time_out' => date('Y-m-d H:i:s')
  ]);
} catch (Exception $e) {
  $conn->rollback();
  echo json_encode(['success' => false, 'message' => '❌ Failed to approve Time Out.']);
}

$conn->close();
