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

$stmt = $conn->prepare("SELECT id FROM attendance WHERE tag_number = ? AND time_out IS NULL ORDER BY id DESC LIMIT 1");
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
$time_out = date('H:i:s');

$updateStmt = $conn->prepare("UPDATE attendance SET time_out = ? WHERE id = ?");
$updateStmt->bind_param("si", $time_out, $attendance_id);
$updateStmt->execute();
$updateStmt->close();

$updateItemStmt = $conn->prepare("UPDATE items_tags SET is_available = 1 WHERE tag_number = ?");
$updateItemStmt->bind_param("s", $tag);
$updateItemStmt->execute();
$updateItemStmt->close();

echo json_encode([
    'status' => 'success',
    'message' => "✅ Time Out recorded at $time_out. Item marked as available again."
]);
