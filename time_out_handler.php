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

$stmt = $conn->prepare("SELECT item_name FROM items_tags WHERE tag_number = ?");
$stmt->bind_param("s", $tag);
$stmt->execute();
$result = $stmt->get_result();
$tagData = $result->fetch_assoc();
$stmt->close();

if (!$tagData) {
    exit(json_encode([
        'status' => 'error',
        'message' => '❌ Tag number not found.'
    ]));
}

$stmt = $conn->prepare("SELECT id FROM attendance WHERE tag_number = ? AND time_out_requested IS NULL AND time_out IS NULL ORDER BY id DESC LIMIT 1");
$stmt->bind_param("s", $tag);
$stmt->execute();
$result = $stmt->get_result();
$attendance = $result->fetch_assoc();
$stmt->close();

if (!$attendance) {
    exit(json_encode([
        'status' => 'error',
        'message' => '⚠️ No active attendance record found for this tag.'
    ]));
}

$attendance_id = $attendance['id'];
$time_out_requested = date('H:i:s');

$update = $conn->prepare("UPDATE attendance SET time_out_requested = ? WHERE id = ?");
$update->bind_param("si", $time_out_requested, $attendance_id);
$update->execute();
$update->close();

$markAvailable = $conn->prepare("UPDATE items_tags SET is_available = 1 WHERE tag_number = ?");
$markAvailable->bind_param("s", $tag);
$markAvailable->execute();
$markAvailable->close();

echo json_encode([
    'status' => 'success',
    'message' => "✅ Time Out requested at $time_out_requested. Item marked as available."
]);
