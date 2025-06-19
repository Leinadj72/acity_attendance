<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
date_default_timezone_set('Africa/Accra');

include 'db.php';

$tag = trim($_POST['tag'] ?? '');

if (empty($tag)) {
  exit(json_encode([
    'status' => 'error',
    'message' => '❌ Tag number is required.'
  ]));
}

// 🔍 Step 1: Check if tag exists in items_tags
$stmt = $conn->prepare("SELECT item_name FROM items_tags WHERE tag_number = ?");
$stmt->bind_param("s", $tag);
$stmt->execute();
$result = $stmt->get_result();
$tagData = $result->fetch_assoc();
$stmt->close();

if (!$tagData) {
  exit(json_encode([
    'status' => 'error',
    'message' => '❌ Tag number not found in the system.'
  ]));
}

// 🔍 Step 2: Find attendance record for this tag where Time Out is not yet done/requested
$stmt = $conn->prepare("
    SELECT id 
    FROM attendance 
    WHERE tag_number = ? 
      AND time_out IS NULL 
      AND (time_out_requested IS NULL OR time_out_requested = 0)
      AND time_out_approved = 0
    ORDER BY id DESC 
    LIMIT 1
");
$stmt->bind_param("s", $tag);
$stmt->execute();
$result = $stmt->get_result();
$attendance = $result->fetch_assoc();
$stmt->close();

if (!$attendance) {
  exit(json_encode([
    'status' => 'error',
    'message' => '⚠️ No active Time In record found or Time Out already requested.'
  ]));
}

// 🕒 Step 3: Request Time Out
$attendance_id = $attendance['id'];
$current_time = date('Y-m-d H:i:s');

$update = $conn->prepare("UPDATE attendance SET time_out_requested = 1, time_out_requested_at = ? WHERE id = ?");
$update->bind_param("si", $current_time, $attendance_id);
$update->execute();

if ($update->affected_rows === 0) {
  exit(json_encode([
    'status' => 'error',
    'message' => '⚠️ Failed to request Time Out. Please try again.'
  ]));
}

$update->close();

echo json_encode([
  'status' => 'success',
  'message' => '✅ Time Out request submitted. Awaiting admin approval.',
  'redirect' => 'scan.php'
]);
