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

$stmt = $conn->prepare("SELECT token_id FROM attendance WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
  echo json_encode(['success' => false, 'message' => 'Attendance record not found']);
  exit;
}

$row = $result->fetch_assoc();
$token_id = $row['token_id'];
$stmt->close();

$stmt = $conn->prepare("UPDATE attendance SET time_out_requested = 0, time_out_approved = 0 WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("UPDATE qr_tokens SET status = 'active', usage_count = GREATEST(usage_count - 1, 0) WHERE id = ?");
$stmt->bind_param("i", $token_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Time Out request rejected']);
$conn->close();
