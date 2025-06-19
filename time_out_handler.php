<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
date_default_timezone_set('Africa/Accra');

include 'db.php';

$tag = trim($_POST['tag'] ?? '');
$roll_number = trim($_POST['roll_number'] ?? '');

if (empty($tag) || empty($roll_number)) {
    exit(json_encode([
        'status' => 'error',
        'message' => '❌ Tag number and roll number are required.'
    ]));
}

$stmt = $conn->prepare("SELECT item_name FROM items_tags WHERE tag_number = ?");
$stmt->bind_param("s", $tag);
$stmt->execute();
$result = $stmt->get_result();
$tagData = $result->fetch_assoc();
$stmt->close();

if (!$tagData) {
    exit(json_encode([
        'status' => 'error',
        'message' => '❌ Tag not found in the system.'
    ]));
}

$stmt = $conn->prepare("
  SELECT id FROM attendance
  WHERE tag_number = ? AND roll_number = ?
    AND time_out IS NULL
    AND (time_out_requested IS NULL OR time_out_requested = 0)
    AND time_out_approved = 0
  ORDER BY id DESC LIMIT 1
");
$stmt->bind_param("ss", $tag, $roll_number);
$stmt->execute();
$result = $stmt->get_result();
$attendance = $result->fetch_assoc();
$stmt->close();

if (!$attendance) {
    exit(json_encode([
        'status' => 'error',
        'message' => '⚠️ No active Time In record found for this tag and user, or Time Out already requested.'
    ]));
}

$conn->begin_transaction();

try {
    $attendance_id = $attendance['id'];
    $now = date('Y-m-d H:i:s');

    $update = $conn->prepare("UPDATE attendance SET time_out_requested = 1, time_out_requested_at = ? WHERE id = ?");
    $update->bind_param("si", $now, $attendance_id);
    $update->execute();

    if ($update->affected_rows === 0) {
        throw new Exception('⚠️ Failed to request Time Out. Please try again.');
    }

    $update->close();
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => '✅ Time Out request submitted. Awaiting admin approval.',
        'redirect' => 'scan.php'
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
