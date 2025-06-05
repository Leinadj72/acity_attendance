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

$stmt = $conn->prepare("SELECT token_id, item FROM attendance WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
  echo json_encode(['success' => false, 'message' => 'Attendance not found']);
  exit;
}

$row = $result->fetch_assoc();
$token_id = $row['token_id'];
$item = $row['item'];
$stmt->close();

$stmt = $conn->prepare("
  UPDATE attendance 
  SET time_out_approved = 1, 
      time_out = IFNULL(time_out, NOW()) 
  WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("
  UPDATE qr_tokens 
  SET last_used_at = NOW(),
      time_out = IFNULL(time_out, NOW())
  WHERE id = ?
");
$stmt->bind_param("i", $token_id);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("UPDATE items_tags SET is_available = 1 WHERE item_name = ?");
$stmt->bind_param("s", $item);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Time Out approved and item returned']);
$conn->close();
